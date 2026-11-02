<?php
require_once 'functions.php';
checkRole('Volunteer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('offers.php');
}

$donationId = isset($_POST['donation_id'])
    ? intval($_POST['donation_id'])
    : 0;

$returnUrl = isset($_POST['return'])
    ? sanitize($_POST['return'])
    : 'saved_offers.php';


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

// INSERT IGNORE מונע שמירה כפולה של אותה תרומה.
if ($donationId > 0) {

    dbExecute(
        "INSERT IGNORE INTO saved_offers
         (user_id, donation_id)
         VALUES (?, ?)",
        [
            $_SESSION['user_id'],
            $donationId
        ]
    );
}

// חזרה לדף שממנו המשתמש הגיע.
redirect($returnUrl);
?>
