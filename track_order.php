<?php
// הדף מציג מעקב אחר הזמנה וסטטוס התהליך.

require_once 'functions.php';
require_once 'donation_workflow.php';
checkLogin();

$reservationId = (int)($_GET['id'] ?? 0);

if ($reservationId <= 0) {
    redirect('history.php');
}


// קבלת פרטי ההזמנה.
$order = dbFetchOne("
    SELECT
        r.reservation_id,
        r.user_id AS volunteer_id,
        r.reservation_time,
        r.status AS reservation_status,
        r.delivery_status,

        d.donation_id,
        d.user_id AS donor_id,
        d.food_name,
        d.quantity,
        d.location,
        d.available_time,
        d.expiry_time,
        d.status AS donation_status,

        donor.full_name AS donor_name,
        volunteer.full_name AS volunteer_name,

        c.center_id,
        c.center_name,
        c.city AS charity_city,
        c.address AS charity_address,
        c.phone AS charity_phone

    FROM reservations r
    JOIN donations d
        ON r.donation_id = d.donation_id
    JOIN users donor
        ON d.user_id = donor.user_id
    JOIN users volunteer
        ON r.user_id = volunteer.user_id
    LEFT JOIN charity_centers c
        ON r.charity_center_id = c.center_id

    WHERE r.reservation_id = ?
    LIMIT 1
", [$reservationId]);


if (!$order) {
    include 'header.php';

    echo '<div class="alert alert-warning">
        Tracking details were not found.
    </div>';

    include 'footer.php';
    exit;
}


$currentUserId = (int)$_SESSION['user_id'];
$currentRole = $_SESSION['role'] ?? '';


// Validate access to this specific reservation rather than trusting the URL.
$myCenterId = null;
if ($currentRole === 'Charity') {
    $myCenter = dbFetchOne("
        SELECT center_id
        FROM charity_centers
        WHERE user_id = ?
        LIMIT 1
    ", [$currentUserId]);

    $myCenterId = $myCenter ? (int)$myCenter['center_id'] : null;
}

$canView = canViewDonationTracking(
    $order,
    $currentRole,
    $currentUserId,
    $myCenterId
);

if (!$canView) {
    redirect('index.php');
}


// סטטוסים.
$reservationStatus = $order['reservation_status'];
$deliveryStatus = $order['delivery_status'] ?: 'Waiting Donor Approval';
$donationStatus = $order['donation_status'];

$isRejected = $reservationStatus == 'Rejected';
$isExpired = $donationStatus == 'Expired';

$isReceived = hasCharityReceivedDonation($order);
$isPickedUp = $reservationStatus === 'Collected' || $donationStatus === 'Collected';


// קביעת שלב המעקב.
$completedStep = 1;

if ($reservationStatus == 'Pending') {
    $completedStep = 2;
}

if ($reservationStatus == 'Approved' || $isPickedUp) {
    $completedStep = 3;
}

if ($isReceived) {
    $completedStep = 4;
}


// הודעה לפי מצב ההזמנה.
$statusLabel = $deliveryStatus;
$statusClass = 'info';
$statusMessage = 'The request is waiting for donor approval.';

if ($isRejected) {
    $statusLabel = 'Rejected';
    $statusClass = 'danger';
    $statusMessage = 'The donor rejected this request.';

} elseif ($isExpired) {
    $statusLabel = 'Expired';
    $statusClass = 'secondary';
    $statusMessage = 'This donation expired.';

} elseif ($isReceived) {
    $statusLabel = 'Received';
    $statusClass = 'success';
    $statusMessage =
        'The charity center confirmed receiving the donation.';

} elseif ($isPickedUp) {
    $statusLabel = 'Picked Up';
    $statusClass = 'warning';
    $statusMessage =
        'The food was marked as picked up. Waiting for the charity center to confirm delivery.';

} elseif ($reservationStatus == 'Approved') {
    $statusLabel = 'Approved By Donor';
    $statusClass = 'warning';
    $statusMessage =
        'The donor approved the request. The volunteer should deliver it to the charity center.';
}


// עיצוב שלבי המעקב.
$step1Class = '';
$step2Class = '';
$step3Class = '';
$step4Class = '';

if ($completedStep == 1) {
    $step1Class = 'current';
}

if ($completedStep == 2) {
    $step1Class = 'done';
    $step2Class = 'current';
}

if ($completedStep == 3) {
    $step1Class = 'done';
    $step2Class = 'done';
    $step3Class = 'current';
}

if ($completedStep == 4) {
    $step1Class = 'done';
    $step2Class = 'done';
    $step3Class = 'done';
    $step4Class = 'done';
}


// פעולות אחרונות.
$logs = dbFetchAll("
    SELECT action, action_date
    FROM action_logs
    WHERE donation_id = ?
    ORDER BY action_date DESC
    LIMIT 10
", [$order['donation_id']]);


function showDate($value)
{
    $time = strtotime((string)$value);

    if ($time == false) {
        return 'Not specified';
    }

    return date('M d, Y H:i', $time);
}


// דף חזרה לפי תפקיד.
$backPage = 'index.php';

if ($currentRole == 'Volunteer') {
    $backPage = 'history.php';

} elseif ($currentRole == 'Donor') {
    $backPage = 'donor_dashboard.php';

} elseif ($currentRole == 'Charity') {
    $backPage = 'charity_dashboard.php';

} elseif ($currentRole == 'Admin') {
    $backPage = 'admin_dashboard.php';
}

include 'header.php';
?>

<style>
.tracking-steps {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
}

.tracking-step {
    border: 1px solid #ddd;
    border-radius: 10px;
    padding: 16px;
    background: #fffdf8;
}

.tracking-step.done {
    border-color: #198754;
    background: #f1f8f4;
}

.tracking-step.current {
    border-color: #ffc107;
    background: #fff8e1;
}

.step-number {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #ddd;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-bottom: 10px;
}

.done .step-number {
    background: #198754;
    color: white;
}

.current .step-number {
    background: #ffc107;
}

@media (max-width: 768px) {
    .tracking-steps {
        grid-template-columns: 1fr;
    }
}
</style>


<div class="container mt-4">

    <div class="card shadow-sm p-4 mb-4">
        <div class="d-flex justify-content-between flex-wrap gap-3">

            <div>
                <h2>
                    Order Tracking #<?= (int)$order['reservation_id'] ?>
                </h2>

                <p class="text-muted mb-0">
                    <?= escapeHtml($order['food_name']) ?>
                    from
                    <?= escapeHtml($order['donor_name']) ?>
                </p>
            </div>

            <div class="text-end">
                <span class="badge bg-<?= $statusClass ?> fs-6">
                    <?= escapeHtml($statusLabel) ?>
                </span>

                <p class="text-muted mt-2 mb-0">
                    <?= escapeHtml($statusMessage) ?>
                </p>
            </div>

        </div>
    </div>


    <?php if ($isRejected || $isExpired): ?>

        <div class="alert alert-<?= $isRejected ? 'danger' : 'secondary' ?>">
            <?= escapeHtml($statusMessage) ?>
        </div>

    <?php else: ?>

        <div class="tracking-steps mb-4">

            <div class="tracking-step <?= $step1Class ?>">
                <div class="step-number">1</div>

                <h5>Requested</h5>

                <p class="text-muted">
                    Volunteer requested the donation
                    and selected a charity center.
                </p>

                <small class="text-muted">
                    <?= showDate($order['reservation_time']) ?>
                </small>
            </div>


            <div class="tracking-step <?= $step2Class ?>">
                <div class="step-number">2</div>

                <h5>Donor Review</h5>

                <p class="text-muted">
                    Restaurant reviews the volunteer request.
                </p>
            </div>


            <div class="tracking-step <?= $step3Class ?>">
                <div class="step-number">3</div>

                <h5>Approved</h5>

                <p class="text-muted">
                    Restaurant approved the request.
                </p>
            </div>


            <div class="tracking-step <?= $step4Class ?>">
                <div class="step-number">4</div>

                <h5>Received</h5>

                <p class="text-muted">
                    Charity center confirmed receiving the food.
                </p>
            </div>

        </div>

    <?php endif; ?>


    <div class="row g-4">

        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">

                    <h4>Donation Details</h4>

                    <p>
                        <strong>Food:</strong>
                        <?= escapeHtml($order['food_name']) ?>
                    </p>

                    <p>
                        <strong>Quantity:</strong>
                        <?= escapeHtml($order['quantity']) ?>
                    </p>

                    <p>
                        <strong>Pickup Location:</strong>
                        <?= escapeHtml($order['location']) ?>
                    </p>

                    <p>
                        <strong>Available:</strong>
                        <?= showDate($order['available_time']) ?>
                    </p>

                    <p>
                        <strong>Expires:</strong>
                        <?= showDate($order['expiry_time']) ?>
                    </p>

                    <p>
                        <strong>Donation Status:</strong>
                        <?= escapeHtml($donationStatus) ?>
                    </p>

                    <p>
                        <strong>Delivery Status:</strong>
                        <?= escapeHtml($deliveryStatus) ?>
                    </p>

                </div>
            </div>
        </div>


        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">

                    <h4>People</h4>

                    <p>
                        <strong>Restaurant:</strong>
                        <?= escapeHtml($order['donor_name']) ?>
                    </p>

                    <p>
                        <strong>Volunteer:</strong>
                        <?= escapeHtml($order['volunteer_name']) ?>
                    </p>

                    <hr>

                    <h5>Charity Center</h5>

                    <?php if (!empty($order['center_name'])): ?>

                        <p>
                            <strong>Name:</strong>
                            <?= escapeHtml($order['center_name']) ?>
                        </p>

                        <p>
                            <strong>City:</strong>
                            <?= escapeHtml($order['charity_city']) ?>
                        </p>

                        <p>
                            <strong>Address:</strong>
                            <?= escapeHtml($order['charity_address']) ?>
                        </p>

                        <p>
                            <strong>Phone:</strong>
                            <?= escapeHtml($order['charity_phone']) ?>
                        </p>

                    <?php else: ?>

                        <p class="text-muted">
                            No charity center selected.
                        </p>

                    <?php endif; ?>

                </div>
            </div>
        </div>

    </div>


    <div class="card shadow-sm mt-4">
        <div class="card-body">

            <h4>Activity</h4>

            <?php if (count($logs) > 0): ?>

                <ul class="list-group list-group-flush">

                    <?php foreach ($logs as $log): ?>

                        <li class="list-group-item d-flex justify-content-between gap-3 px-0">

                            <span>
                                <?= escapeHtml($log['action']) ?>
                            </span>

                            <small class="text-muted">
                                <?= showDate($log['action_date']) ?>
                            </small>

                        </li>

                    <?php endforeach; ?>

                </ul>

            <?php else: ?>

                <p class="text-muted mb-0">
                    No activity yet.
                </p>

            <?php endif; ?>

        </div>
    </div>


    <div class="mt-4 d-flex gap-2 flex-wrap">

        <a
            href="offer_details.php?donation_id=<?= (int)$order['donation_id'] ?>"
            class="btn btn-outline-success"
        >
            View Offer
        </a>

        <a
            href="map.php"
            class="btn btn-outline-primary"
        >
            View Map
        </a>

        <a
            href="<?= $backPage ?>"
            class="btn btn-link"
        >
            Back
        </a>

    </div>

</div>

<?php include 'footer.php'; ?>
