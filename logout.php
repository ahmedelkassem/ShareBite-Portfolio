<?php
// סיכום הדף:
// הדף מנתק את המשתמש מהמערכת ומסיים את ה-Session.
// לאחר ההתנתקות המשתמש חוזר לדף הבית.

require_once 'functions.php';

// מחיקת הנתונים של ה-Session וסיום החיבור.
session_unset();
session_destroy();

// מחיקת ה-Cookie של ה-Session מהדפדפן.
setcookie(session_name(), '', time() - 3600, '/');

// חזרה לדף הבית.
header('Location: index.php');
exit;
?>