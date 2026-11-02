<?php
// סיכום הדף:
// הדף מטפל בהוספת תגובה חדשה לשיחת תמיכה קיימת.
// הוא בודק את המשתמש ואת CSRF Token,
// שומר את ההודעה ומעדכן את זמן השיחה.

require_once 'functions.php';


// בדיקה שהמשתמש מחובר.
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}


// הדף מקבל רק בקשות POST עם CSRF Token תקין.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Location: contact.php');
    exit;
}


// קבלת מספר השיחה וההודעה מהטופס.
$conversationId = (int)($_POST['conversation_id'] ?? 0);
$message = sanitize($_POST['message'] ?? '');


// בדיקה שמספר השיחה תקין ושההודעה לא ריקה.
if ($conversationId <= 0 || $message === '') {
    header('Location: contact.php');
    exit;
}


// קבלת פרטי השיחה ממסד הנתונים.
$conversation = dbFetchOne(
    "SELECT * FROM contact_conversations WHERE conversation_id = ?",
    [$conversationId]
);


// לא ניתן להגיב אם השיחה לא קיימת או כבר נסגרה.
if (!$conversation || $conversation['status'] === 'CLOSED') {
    header('Location: contact.php');
    exit;
}


// בדיקה אם המשתמש הוא Admin.
$isAdmin = $_SESSION['role'] === 'Admin';


// בדיקה אם המשתמש הוא בעל השיחה.
$isOwner = !empty($conversation['user_id']) &&
    $conversation['user_id'] == $_SESSION['user_id'];


// רק Admin או בעל השיחה יכולים לשלוח תגובה.
if (!$isAdmin && !$isOwner) {
    header('Location: contact.php');
    exit;
}


// קביעת סוג השולח.
$senderRole = $isAdmin ? 'ADMIN' : 'USER';


// שמירת ההודעה החדשה בטבלת contact_messages.
dbExecute(
    "INSERT INTO contact_messages (conversation_id, sender_role, message) VALUES (?, ?, ?)",
    [$conversationId, $senderRole, $message]
);


// עדכון זמן השיחה לאחר הוספת הודעה חדשה.
dbExecute(
    "UPDATE contact_conversations SET updated_at = NOW() WHERE conversation_id = ?",
    [$conversationId]
);


// אם השולח הוא Admin חוזרים לדף הודעות המנהל.
if ($isAdmin) {
    header('Location: admin_messages.php');
} else {

    // אם השולח הוא משתמש רגיל חוזרים לדף Contact.
    header('Location: contact.php');
}

exit;