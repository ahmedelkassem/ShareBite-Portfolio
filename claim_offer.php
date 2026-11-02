<?php
// הדף מטפל בבקשה של Volunteer להזמין תרומה.

require_once 'functions.php';


if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    redirect('index.php');
}

if (
    empty($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') != 'Volunteer'
) {
    redirect('index.php?error=Only volunteers can request donations.');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    redirect('index.php?error=Invalid request.');
}

$donationId = (int)($_POST['donation_id'] ?? 0);
$charityCenterId = (int)($_POST['charity_center_id'] ?? 0);
$volunteerId = (int)$_SESSION['user_id'];

if ($donationId <= 0) {
    redirect('index.php?error=Invalid donation.');
}

if ($charityCenterId <= 0) {
    redirect(
        'offer_details.php?donation_id=' .
        $donationId .
        '&error=Please select a charity center before requesting this donation.'
    );
}

try {
    $pdo->beginTransaction();

    // שליפת התרומה.
    $stmt = $pdo->prepare("
        SELECT donation_id, user_id, status
        FROM donations
        WHERE donation_id = ?
        LIMIT 1
    ");
    $stmt->execute([$donationId]);
    $donation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$donation) {
        $pdo->rollBack();
        redirect('index.php?error=Donation was not found.');
    }

    if ($donation['status'] != 'Available') {
        $pdo->rollBack();

        redirect(
            'offer_details.php?donation_id=' .
            $donationId .
            '&error=This donation is no longer available.'
        );
    }

    if ((int)$donation['user_id'] == $volunteerId) {
        $pdo->rollBack();

        redirect(
            'offer_details.php?donation_id=' .
            $donationId .
            '&error=You cannot request your own donation.'
        );
    }

    // בדיקת מרכז הצדקה.
    $centerStmt = $pdo->prepare("
        SELECT center_id
        FROM charity_centers
        WHERE center_id = ?
        AND status = 'Active'
        LIMIT 1
    ");
    $centerStmt->execute([$charityCenterId]);

    if (!$centerStmt->fetch(PDO::FETCH_ASSOC)) {
        $pdo->rollBack();

        redirect(
            'offer_details.php?donation_id=' .
            $donationId .
            '&error=Please select an active charity center.'
        );
    }

    // שינוי מצב התרומה ל-Pending.
    $updateDonation = $pdo->prepare("
        UPDATE donations
        SET status = 'Pending'
        WHERE donation_id = ?
        AND status = 'Available'
    ");
    $updateDonation->execute([$donationId]);

    if ($updateDonation->rowCount() != 1) {
        $pdo->rollBack();

        redirect(
            'offer_details.php?donation_id=' .
            $donationId .
            '&error=This donation is no longer available.'
        );
    }

    // יצירת Reservation.
    $reserve = $pdo->prepare("
        INSERT INTO reservations
        (
            donation_id,
            user_id,
            charity_center_id,
            reservation_time,
            status,
            delivery_status
        )
        VALUES (?, ?, ?, NOW(), 'Pending', 'Waiting Donor Approval')
    ");

    $reserve->execute([
        $donationId,
        $volunteerId,
        $charityCenterId
    ]);

    $reservationId = (int)$pdo->lastInsertId();

    // שמירת הפעולה ב-Log.
    $log = $pdo->prepare("
        INSERT INTO action_logs
        (user_id, donation_id, action, action_date)
        VALUES (?, ?, ?, NOW())
    ");

    $log->execute([
        $volunteerId,
        $donationId,
        'Volunteer requested the donation and selected a charity center'
    ]);

    $pdo->commit();

    redirect('track_order.php?id=' . $reservationId);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    redirect(
        'offer_details.php?donation_id=' .
        $donationId .
        '&error=Claim could not be completed.'
    );
}