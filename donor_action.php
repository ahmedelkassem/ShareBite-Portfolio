<?php
// Approve or reject a volunteer request belonging to the logged-in donor.
require_once 'functions.php';
require_once 'donation_workflow.php';

checkRole('Donor');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    redirect('donor_dashboard.php');
}

$reservationId = (int)($_POST['reservation_id'] ?? 0);
$action = $_POST['action'] ?? '';
$donorId = (int)$_SESSION['user_id'];

if ($reservationId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    redirect('donor_dashboard.php');
}

$volunteerId = null;
$donationId = null;

try {
    // Lock the reservation and its donation together before checking states.
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("
        SELECT r.reservation_id, r.user_id AS volunteer_id,
               r.donation_id, r.status AS reservation_status,
               d.user_id AS donor_id, d.status AS donation_status
        FROM reservations r
        INNER JOIN donations d ON d.donation_id = r.donation_id
        WHERE r.reservation_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$reservationId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !canDonorDecideReservation($row, $donorId)) {
        $pdo->rollBack();
        redirect('donor_dashboard.php');
    }

    $donationId = (int)$row['donation_id'];
    $volunteerId = (int)$row['volunteer_id'];
    $newReservationStatus = $action === 'approve' ? 'Approved' : 'Rejected';
    $newDeliveryStatus = $action === 'approve' ? 'Approved By Donor' : 'Rejected';
    $newDonationStatus = $action === 'approve' ? 'Claimed' : 'Available';

    $updateReservation = $pdo->prepare("
        UPDATE reservations
        SET status = ?, delivery_status = ?
        WHERE reservation_id = ? AND status = 'Pending'
    ");
    $updateReservation->execute([
        $newReservationStatus, $newDeliveryStatus, $reservationId
    ]);
    if ($updateReservation->rowCount() !== 1) {
        throw new RuntimeException('Reservation state changed while updating.');
    }

    $updateDonation = $pdo->prepare("
        UPDATE donations
        SET status = ?
        WHERE donation_id = ? AND status = 'Pending'
    ");
    $updateDonation->execute([$newDonationStatus, $donationId]);
    if ($updateDonation->rowCount() !== 1) {
        throw new RuntimeException('Donation state changed while updating.');
    }

    $actionText = $action === 'approve'
        ? 'Donor approved the volunteer request'
        : 'Donor rejected the volunteer request';
    $log = $pdo->prepare("
        INSERT INTO action_logs (user_id, donation_id, action, action_date)
        VALUES (?, ?, ?, NOW())
    ");
    $log->execute([$donorId, $donationId, $actionText]);

    $pdo->commit();
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('ShareBite donor action failed: ' . $error->getMessage());
    redirect('donor_dashboard.php');
}

// Notifications are intentionally sent after a committed database change.
if ($action === 'approve') {
    notifyUser(
        $volunteerId,
        'Your request for donation #' . $donationId . ' was approved.',
        'Claim Approved'
    );
} else {
    notifyUser(
        $volunteerId,
        'Your request for donation #' . $donationId . ' was rejected.',
        'Claim Rejected'
    );
}

redirect('donor_dashboard.php');
