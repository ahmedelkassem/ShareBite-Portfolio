<?php
// Mark a donor-approved donation as picked up. Only its donor or Admin may do so.
require_once 'functions.php';
require_once 'donation_workflow.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    redirect('index.php');
}

$role = $_SESSION['role'] ?? '';
$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0 || !in_array($role, ['Donor', 'Admin'], true)) {
    redirect('index.php');
}

$donationId = (int)($_POST['donation_id'] ?? 0);
if ($donationId <= 0) {
    redirect('index.php');
}

$returnPage = $role === 'Donor' ? 'donor_dashboard.php' : 'admin_dashboard.php';

try {
    // Lock the donation before checking its owner and current state.
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("
        SELECT donation_id, user_id, status
        FROM donations
        WHERE donation_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$donationId]);
    $donation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$donation || !canMarkDonationPickedUp($donation, $role, $userId)) {
        $pdo->rollBack();
        redirect($returnPage);
    }

    // Only donor-approved reservations may transition to Collected.
    $updateReservations = $pdo->prepare("
        UPDATE reservations
        SET status = 'Collected'
        WHERE donation_id = ? AND status = 'Approved'
    ");
    $updateReservations->execute([$donationId]);

    if ($updateReservations->rowCount() < 1) {
        throw new RuntimeException('No approved reservation exists for pickup.');
    }

    $updateDonation = $pdo->prepare("
        UPDATE donations
        SET status = 'Collected'
        WHERE donation_id = ? AND status = 'Claimed'
    ");
    $updateDonation->execute([$donationId]);

    if ($updateDonation->rowCount() !== 1) {
        throw new RuntimeException('Donation state changed before pickup.');
    }

    $log = $pdo->prepare("
        INSERT INTO action_logs (user_id, donation_id, action, action_date)
        VALUES (?, ?, ?, NOW())
    ");
    $log->execute([$userId, $donationId, 'Offer marked as collected']);
    $pdo->commit();
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('ShareBite pickup failed: ' . $error->getMessage());
}

redirect($returnPage);
