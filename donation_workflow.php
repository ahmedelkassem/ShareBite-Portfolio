<?php
// Shared, side-effect-free business rules for donation state transitions.
function canDonorDecideReservation(array $row, int $donorId): bool
{
    return (int)($row['donor_id'] ?? 0) === $donorId
        && ($row['reservation_status'] ?? '') === 'Pending'
        && ($row['donation_status'] ?? '') === 'Pending';
}

function canCharityConfirmDelivery(array $row): bool
{
    return in_array($row['reservation_status'] ?? '', ['Approved', 'Collected'], true)
        && ($row['delivery_status'] ?? '') !== 'Received'
        && in_array($row['donation_status'] ?? '', ['Claimed', 'Collected'], true);
}

/**
 * Donor can mark only their own Claimed donation as picked up.
 * Admin can perform the same action for any Claimed donation.
 */
function canMarkDonationPickedUp(array $donation, string $role, int $userId): bool
{
    return ($donation['status'] ?? '') === 'Claimed'
        && ($role === 'Admin' ||
            ($role === 'Donor' && (int)($donation['user_id'] ?? 0) === $userId));
}

/**
 * Being picked up is not the same as being received by a charity.
 */
function hasCharityReceivedDonation(array $reservation): bool
{
    return ($reservation['delivery_status'] ?? '') === 'Received'
        || ($reservation['reservation_status'] ?? '') === 'Completed';
}

/**
 * Restrict reservation tracking to the volunteer, owner donor,
 * assigned charity center, or administrator. Arbitrary reservation
 * IDs must not expose other users' coordination details.
 */
function canViewDonationTracking(
    array $order,
    string $role,
    int $userId,
    ?int $charityCenterId = null
): bool {
    if ($userId <= 0) {
        return false;
    }
    if ($role === 'Admin') {
        return true;
    }
    if ($role === 'Volunteer') {
        return (int)($order['volunteer_id'] ?? 0) === $userId;
    }
    if ($role === 'Donor') {
        return (int)($order['donor_id'] ?? 0) === $userId;
    }
    if ($role === 'Charity') {
        return $charityCenterId !== null
            && $charityCenterId > 0
            && (int)($order['center_id'] ?? 0) === $charityCenterId;
    }
    return false;
}
