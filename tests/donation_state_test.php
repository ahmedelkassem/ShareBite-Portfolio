<?php
require_once dirname(__DIR__) . '/donation_workflow.php';

function expect(bool $actual, bool $expected, string $name): void
{
    if ($actual !== $expected) {
        fwrite(STDERR, "FAILED: " . $name . PHP_EOL);
        exit(1);
    }
}

$pending = [
    'donor_id' => 7, 'reservation_status' => 'Pending',
    'donation_status' => 'Pending'
];
expect(canDonorDecideReservation($pending, 7), true, 'donor can decide pending request');
expect(canDonorDecideReservation($pending, 8), false, 'different donor rejected');
expect(canDonorDecideReservation(array_merge($pending, ['reservation_status'=>'Approved']), 7), false, 'repeat action rejected');
expect(canDonorDecideReservation(array_merge($pending, ['donation_status'=>'Expired']), 7), false, 'expired request rejected');

$approved = [
    'reservation_status' => 'Approved',
    'delivery_status' => 'Approved By Donor',
    'donation_status' => 'Claimed'
];
expect(canCharityConfirmDelivery($approved), true, 'approved request can be received');
expect(canCharityConfirmDelivery(array_merge($approved, ['reservation_status'=>'Collected', 'donation_status'=>'Collected'])), true, 'collected pickup can be received');
expect(canCharityConfirmDelivery(array_merge($approved, ['reservation_status'=>'Pending'])), false, 'pending request rejected');
expect(canCharityConfirmDelivery(array_merge($approved, ['reservation_status'=>'Rejected'])), false, 'rejected request rejected');
expect(canCharityConfirmDelivery(array_merge($approved, ['donation_status'=>'Expired'])), false, 'expired donation rejected');
expect(canCharityConfirmDelivery(array_merge($approved, ['delivery_status'=>'Received'])), false, 'duplicate receipt rejected');


$claimedOwn = ['user_id' => 7, 'status' => 'Claimed'];
expect(canMarkDonationPickedUp($claimedOwn, 'Donor', 7), true, 'donor can mark own approved pickup');
expect(canMarkDonationPickedUp($claimedOwn, 'Donor', 8), false, 'different donor cannot mark pickup');
expect(canMarkDonationPickedUp($claimedOwn, 'Volunteer', 7), false, 'volunteer cannot mark pickup');
expect(canMarkDonationPickedUp($claimedOwn, 'Admin', 3), true, 'admin can mark approved pickup');
expect(canMarkDonationPickedUp(['user_id' => 7, 'status' => 'Pending'], 'Donor', 7), false, 'unapproved pickup rejected');
expect(canMarkDonationPickedUp(['user_id' => 7, 'status' => 'Collected'], 'Admin', 3), false, 'duplicate pickup rejected');

expect(hasCharityReceivedDonation([
    'reservation_status' => 'Collected',
    'delivery_status' => 'Approved By Donor',
]), false, 'pickup alone is not charity receipt');
expect(hasCharityReceivedDonation([
    'reservation_status' => 'Approved',
    'delivery_status' => 'Received',
]), true, 'explicit charity receipt accepted');
expect(hasCharityReceivedDonation([
    'reservation_status' => 'Completed',
    'delivery_status' => 'Received',
]), true, 'completed received request accepted');

echo "Donation transition tests passed." . PHP_EOL;
