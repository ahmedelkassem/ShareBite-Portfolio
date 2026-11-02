<?php
// הדף מציג היסטוריה לפי תפקיד המשתמש.

require_once 'functions.php';

checkLogin();
ensureVolunteerProfileFields();

$userId = (int)$_SESSION['user_id'];
$role = $_SESSION['role'] ?? '';
$pageTitle = '';
$result = [];
$volunteerProfile = null;

// היסטוריה של Volunteer.
if ($role == 'Volunteer') {

    $pageTitle = 'My Reservations';
    $volunteerProfile = dbFetchOne("
        SELECT full_name, email, phone, city, address
        FROM users
        WHERE user_id = ?
        LIMIT 1
    ", [$userId]);

    $result = dbFetchAll("
        SELECT
            r.*,
            d.food_name,
            d.quantity,
            d.location,
            d.available_time,
            d.expiry_time,
            d.status AS donation_status,
            c.center_name,
            c.city AS charity_city,
            c.address AS charity_address,
            c.phone AS charity_phone
        FROM reservations r
        JOIN donations d
            ON r.donation_id = d.donation_id
        LEFT JOIN charity_centers c
            ON r.charity_center_id = c.center_id
        WHERE r.user_id = ?
        AND (
            r.status = 'Pending'
            OR r.status = 'Completed'
            OR r.delivery_status = 'Received'
        )
        AND d.status != 'Expired'
        ORDER BY r.reservation_time DESC
    ", [$userId]);

// היסטוריה של Donor.
} elseif ($role == 'Donor') {

    $pageTitle = 'Restaurant Request History';

    $result = dbFetchAll("
        SELECT
            r.*,
            d.food_name,
            d.quantity,
            d.location,
            d.available_time,
            d.expiry_time,
            d.status AS donation_status,
            u.full_name AS volunteer_name,
            u.email AS volunteer_email,
            c.center_name,
            c.city AS charity_city,
            c.address AS charity_address,
            c.phone AS charity_phone
        FROM reservations r
        JOIN donations d
            ON r.donation_id = d.donation_id
        JOIN users u
            ON r.user_id = u.user_id
        LEFT JOIN charity_centers c
            ON r.charity_center_id = c.center_id
        WHERE d.user_id = ?
        ORDER BY r.reservation_time DESC
    ", [$userId]);

// היסטוריה של Charity.
} elseif ($role == 'Charity') {

    $pageTitle = 'Incoming Deliveries';

    $result = dbFetchAll("
        SELECT
            r.*,
            d.food_name,
            d.quantity,
            d.location,
            d.available_time,
            d.expiry_time,
            d.status AS donation_status,
            u.full_name AS volunteer_name,
            u.email AS volunteer_email
        FROM charity_centers c
        JOIN reservations r
            ON c.center_id = r.charity_center_id
        JOIN donations d
            ON r.donation_id = d.donation_id
        JOIN users u
            ON r.user_id = u.user_id
        WHERE c.user_id = ?
        ORDER BY r.reservation_time DESC
    ", [$userId]);

} else {
    redirect('index.php');
}

// הצגת תאריך.
function showDate($value)
{
    $time = strtotime((string)$value);

    if ($time == false) {
        return 'Not specified';
    }

    return date('M d, Y H:i', $time);
}

include 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-12">

        <h2 class="mb-4">
            <?= escapeHtml($pageTitle) ?>
        </h2>

        <?php if (($_GET['review'] ?? '') == 'success'): ?>
            <div class="alert alert-success">
                Your review was submitted successfully.
            </div>
        <?php endif; ?>

        <?php if ($role == 'Volunteer' && $volunteerProfile): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="mb-3">Volunteer Details</h5>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <strong>Name:</strong><br>
                            <?= escapeHtml($volunteerProfile['full_name']) ?>
                        </div>

                        <div class="col-md-3">
                            <strong>Phone:</strong><br>
                            <?= escapeHtml($volunteerProfile['phone'] ?? 'Not set') ?>
                        </div>

                        <div class="col-md-3">
                            <strong>City:</strong><br>
                            <?= escapeHtml($volunteerProfile['city'] ?? 'Not set') ?>
                        </div>

                        <div class="col-md-3">
                            <strong>Address:</strong><br>
                            <?= escapeHtml($volunteerProfile['address'] ?? 'Not set') ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (count($result) > 0): ?>

            <div class="row g-4">

                <?php foreach ($result as $reservation): ?>

                    <?php
                    // Volunteer יכול להוסיף Review רק אחרי סיום.
                    $canReview =
                        $role == 'Volunteer' &&
                        (
                            ($reservation['status'] ?? '') == 'Completed' ||
                            ($reservation['delivery_status'] ?? '') == 'Received'
                        );
                    ?>

                    <div class="col-md-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-body d-flex flex-column">

                                <h5 class="card-title">
                                    <?= escapeHtml($reservation['food_name']) ?>
                                </h5>

                                <p class="mb-1">
                                    <strong>Quantity:</strong>
                                    <?= escapeHtml($reservation['quantity']) ?>
                                </p>

                                <p class="mb-1">
                                    <strong>Pickup Location:</strong>
                                    <?= escapeHtml($reservation['location']) ?>
                                </p>

                                <?php if (
                                    $role == 'Donor' ||
                                    $role == 'Charity'
                                ): ?>

                                    <p class="mb-1">
                                        <strong>Volunteer:</strong>
                                        <?= escapeHtml($reservation['volunteer_name']) ?>

                                        <small class="text-muted">
                                            <?= escapeHtml($reservation['volunteer_email']) ?>
                                        </small>
                                    </p>

                                <?php endif; ?>

                                <?php if ($role != 'Charity'): ?>

                                    <div class="alert alert-light border mt-3">
                                        <strong>Delivery Destination:</strong><br>

                                        <?php if (!empty($reservation['center_name'])): ?>

                                            <?= escapeHtml($reservation['center_name']) ?><br>

                                            <?= escapeHtml($reservation['charity_city']) ?>
                                            -
                                            <?= escapeHtml($reservation['charity_address']) ?><br>

                                            <small>
                                                Phone:
                                                <?= escapeHtml($reservation['charity_phone']) ?>
                                            </small>

                                        <?php else: ?>

                                            <span class="text-muted">
                                                No charity center selected.
                                            </span>

                                        <?php endif; ?>
                                    </div>

                                <?php endif; ?>

                                <p class="mb-1">
                                    <strong>Available:</strong>
                                    <?= showDate($reservation['available_time']) ?>
                                </p>

                                <p class="mb-1">
                                    <strong>Expires:</strong>
                                    <?= showDate($reservation['expiry_time']) ?>
                                </p>

                                <p class="mb-1">
                                    <strong>Reserved at:</strong>
                                    <?= showDate($reservation['reservation_time']) ?>
                                </p>

                                <p class="mt-3 mb-1">
                                    <strong>Request Status:</strong>

                                    <span class="badge bg-info">
                                        <?= escapeHtml($reservation['status']) ?>
                                    </span>
                                </p>

                                <p class="mb-3">
                                    <strong>Delivery Status:</strong>

                                    <span class="badge bg-secondary">
                                        <?= !empty($reservation['delivery_status'])
                                            ? escapeHtml($reservation['delivery_status'])
                                            : 'Pending' ?>
                                    </span>
                                </p>

                                <div class="d-grid gap-2 mt-auto">

                                    <a
                                        href="track_order.php?id=<?= (int)$reservation['reservation_id'] ?>"
                                        class="btn btn-success"
                                    >
                                        Track Order
                                    </a>

                                    <a
                                        href="offer_details.php?donation_id=<?= (int)$reservation['donation_id'] ?>"
                                        class="btn btn-outline-success"
                                    >
                                        View Offer
                                    </a>

                                    <?php if ($canReview): ?>
                                        <a
                                            href="add_review.php?donation_id=<?= (int)$reservation['donation_id'] ?>"
                                            class="btn btn-warning"
                                        >
                                            Add Review
                                        </a>
                                    <?php endif; ?>

                                </div>

                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="alert alert-info">
                No active or completed reservations right now.
            </div>

        <?php endif; ?>

    </div>
</div>

<?php include 'footer.php'; ?>
