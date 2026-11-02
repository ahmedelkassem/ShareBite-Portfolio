<?php
// סיכום הדף:
// הדף מוחק תרומה קיימת אם המשתמש הוא Admin או בעל התרומה.
// הוא בודק את מספר התרומה ואת ההרשאות לפני המחיקה.

require_once 'functions.php';

checkLogin();


// קבלת מספר התרומה מהקישור.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    exit('Invalid request.');
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    redirect('index.php');
}


// קבלת בעל התרומה כדי לבדוק הרשאה.
$offer = dbFetchOne(
    "SELECT donation_id, user_id
     FROM donations
     WHERE donation_id = ?",
    [$id]
);

if (!$offer) {
    redirect('index.php');
}


// רק Admin או בעל התרומה יכולים למחוק אותה.
if (
    ($_SESSION['role'] ?? '') != 'Admin' &&
    (int)$_SESSION['user_id'] != (int)$offer['user_id']
) {
    redirect('index.php');
}


// מחיקת התרומה ממסד הנתונים.
dbExecute(
    "DELETE FROM donations
     WHERE donation_id = ?",
    [$id]
);


// חזרה לדף הבית.
redirect('index.php');
?>