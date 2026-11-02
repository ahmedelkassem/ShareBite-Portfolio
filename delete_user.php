<?php
// הדף מאפשר ל-Admin למחוק משתמש מהמערכת.

require_once 'functions.php';

checkRole('Admin');


// הדף מקבל בקשות POST בלבד.
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    redirect('manage_users.php');
}


// קבלת מספר המשתמש.
$userId = (int)($_POST['id'] ?? 0);


// בדיקת CSRF.
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    redirect('manage_users.php');
}


// ה-Admin לא יכול למחוק את החשבון של עצמו.
if (
    $userId > 0 &&
    $userId != (int)$_SESSION['user_id']
) {

    dbExecute(
        "DELETE FROM users WHERE user_id = ?",
        [$userId]
    );
}


// חזרה לדף ניהול המשתמשים.
redirect('manage_users.php');
?>