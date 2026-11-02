<?php
// סיכום הדף:
// הדף מסיר תרומה מרשימת המועדפים של ה-Volunteer.
// הוא בודק את הבקשה ואת ה-CSRF Token ואז מוחק את התרומה מהמועדפים.

require_once 'functions.php';
checkRole('Volunteer');

// הסרת תרומה מתבצעת רק דרך POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('saved_offers.php');
}

// קבלת מספר התרומה ודף החזרה.
$donationId = isset($_POST['donation_id'])
    ? intval($_POST['donation_id'])
    : 0;

$returnUrl = isset($_POST['return'])
    ? sanitize($_POST['return'])
    : 'saved_offers.php';

// בדיקת CSRF Token.

// Only allow relative return URLs to known application pages.
if (!preg_match(
    '~^(?:index|offers|saved_offers|search_offers|offer_details)\.php(?:\?[a-zA-Z0-9_=&%+.-]*)?$~D',
    $returnUrl
)) {
    $returnUrl = 'saved_offers.php';
}

$token = $_POST['csrf_token'] ?? '';

if (!verifyCsrfToken($token)) {
    redirect($returnUrl);
}

// מחיקת התרומה מהמועדפים של המשתמש הנוכחי.
if ($donationId > 0) {

    dbExecute(
        "DELETE FROM saved_offers
         WHERE user_id = ?
         AND donation_id = ?",
        [
            $_SESSION['user_id'],
            $donationId
        ]
    );
}

// חזרה לדף שממנו המשתמש הגיע.
redirect($returnUrl);
?>