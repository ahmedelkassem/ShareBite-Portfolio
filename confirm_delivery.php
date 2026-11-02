<?php
// Record delivery only after donor approval or a completed pickup.
require_once 'functions.php';
require_once 'donation_workflow.php';

checkRole('Charity');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    redirect('charity_dashboard.php');
}

$reservationId = (int)($_POST['reservation_id'] ?? 0);
$userId = (int)$_SESSION['user_id'];

if ($reservationId <= 0) {
    redirect('charity_dashboard.php');
}

$center = dbFetchOne("
    SELECT center_id FROM charity_centers WHERE user_id = ? LIMIT 1
", [$userId]);
if (!$center) {
    redirect('charity_dashboard.php');
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("
        SELECT r.reservation_id, r.donation_id, r.status AS reservation_status,
               r.delivery_status, d.status AS donation_status
        FROM reservations r
        JOIN donations d ON d.donation_id = r.donation_id
        WHERE r.reservation_id = ? AND r.charity_center_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$reservationId, (int)$center['center_id']]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reservation || !canCharityConfirmDelivery($reservation)) {
        $pdo->rollBack();
        redirect('charity_dashboard.php');
    }

    $markReceived = $pdo->prepare("
        UPDATE reservations
        SET delivery_status = 'Received', status = 'Completed'
        WHERE reservation_id = ?
          AND status IN ('Approved', 'Collected')
          AND delivery_status <> 'Received'
    ");
    $markReceived->execute([$reservationId]);
    if ($markReceived->rowCount() !== 1) {
        throw new RuntimeException('Reservation delivery state changed.');
    }

    $markDonation = $pdo->prepare("
        UPDATE donations SET status = 'Collected'
        WHERE donation_id = ? AND status IN ('Claimed', 'Collected')
    ");
    $markDonation->execute([(int)$reservation['donation_id']]);

    $log = $pdo->prepare("
        INSERT INTO action_logs (user_id, donation_id, action, action_date)
        VALUES (?, ?, ?, NOW())
    ");
    $log->execute([
        $userId,
        (int)$reservation['donation_id'],
        'Charity center confirmed food delivery received'
    ]);
    $pdo->commit();
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('ShareBite delivery confirmation failed: ' . $error->getMessage());
}

redirect('charity_dashboard.php');
