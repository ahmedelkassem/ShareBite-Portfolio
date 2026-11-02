<?php
// הדף מאפשר ל-Admin להפוך משתמש אחר ל-Admin.

require_once 'functions.php';

checkRole('Admin');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    redirect('manage_users.php');
}

$userId = (int)($_POST['id'] ?? 0);
$returnUrl = $_POST['return'] ?? 'manage_users.php';

$allowedReturns = [
    'manage_users.php',
    'admin_dashboard.php'
];

// בדיקה שדף החזרה תקין.
if (!in_array($returnUrl, $allowedReturns)) {
    $returnUrl = 'manage_users.php';
}

// בדיקת CSRF.
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    redirect($returnUrl);
}

// לא ניתן לשנות את ה-Admin המחובר.
if (
    $userId > 0 &&
    $userId != (int)$_SESSION['user_id']
) {
    dbExecute("
        UPDATE users
        SET role = 'Admin'
        WHERE user_id = ?
    ", [$userId]);
}

redirect($returnUrl);