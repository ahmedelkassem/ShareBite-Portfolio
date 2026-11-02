<?php
// הדף מציג את מרכז הצדקה והתרומות שמגיעות אליו.

require_once 'functions.php';
require_once 'donation_workflow.php';
checkLogin();

if (($_SESSION['role'] ?? '') != 'Charity') {
    redirect('index.php');
}

$userId = (int)$_SESSION['user_id'];

$center = dbFetchOne("
    SELECT *
    FROM charity_centers
    WHERE user_id = ?
    LIMIT 1
", [$userId]);

if (!$center) {
    include 'header.php';

    echo '<div class="alert alert-warning">
        Charity center profile was not found.
    </div>';

    include 'footer.php';
    exit;
}

$centerId = (int)$center['center_id'];


// נתונים כלליים של המרכז.
$stats = dbFetchOne("
    SELECT
        COUNT(*) AS total,
        SUM(delivery_status = 'Waiting Donor Approval') AS waiting,
        SUM(status IN ('Approved', 'Collected') AND delivery_status <> 'Received') AS approved,
        SUM(delivery_status = 'Received') AS received
    FROM reservations
    WHERE charity_center_id = ?
", [$centerId]);


// תרומות שמגיעות למרכז.
$deliveries = dbFetchAll("
    SELECT
        r.reservation_id,
        r.reservation_time,
        r.status AS reservation_status,
        r.delivery_status,

        d.food_name,
        d.quantity,
        d.location AS pickup_location,
        d.available_time,
        d.expiry_time,
        d.status AS donation_status,

        volunteer.full_name AS volunteer_name,
        volunteer.email AS volunteer_email,
        donor.full_name AS donor_name

    FROM reservations r
    JOIN donations d
        ON r.donation_id = d.donation_id
    JOIN users volunteer
        ON r.user_id = volunteer.user_id
    JOIN users donor
        ON d.user_id = donor.user_id

    WHERE r.charity_center_id = ?
    ORDER BY r.reservation_time DESC
", [$centerId]);

function showDate($value)
{
    $time = strtotime((string)$value);

    if ($time == false) {
        return 'Not specified';
    }

    return date('M d, Y H:i', $time);
}

$statCards = [
    'Total' => $stats['total'] ?? 0,
    'Waiting Approval' => $stats['waiting'] ?? 0,
    'Approved' => $stats['approved'] ?? 0,
    'Received' => $stats['received'] ?? 0
];


include 'header.php';
?>

<h2 class="mb-4">Charity Dashboard</h2>


<?php if (!empty($_GET['success'])): ?>
    <div class="alert alert-success">
        <?= escapeHtml($_GET['success']) ?>
    </div>
<?php endif; ?>

<?php if (!empty($_GET['error'])): ?>
    <div class="alert alert-warning">
        <?= escapeHtml($_GET['error']) ?>
    </div>
<?php endif; ?>


<div class="card p-4 mb-4 shadow-sm">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">

        <div>
            <h4><?= escapeHtml($center['center_name']) ?></h4>

            <p class="mb-1">
                <strong>City:</strong>
                <?= escapeHtml($center['city']) ?>
            </p>

            <p class="mb-1">
                <strong>Address:</strong>
                <?= escapeHtml($center['address']) ?>
            </p>

            <p class="mb-1">
                <strong>Phone:</strong>
                <?= escapeHtml($center['phone']) ?>
            </p>

            <?php if (!empty($center['description'])): ?>
                <p class="mb-0">
                    <strong>Description:</strong>
                    <?= escapeHtml($center['description']) ?>
                </p>
            <?php endif; ?>
        </div>

        <span class="badge bg-<?= $center['status'] == 'Active' ? 'success' : 'secondary' ?> fs-6">
            <?= escapeHtml($center['status']) ?>
        </span>

    </div>
</div>

<div class="row g-4 mb-4">

    <?php foreach ($statCards as $title => $number): ?>

        <div class="col-md-3">
            <div class="card p-4 text-center shadow-sm">

                <h6><?= escapeHtml($title) ?></h6>
                <h2><?= (int)$number ?></h2>

            </div>
        </div>

    <?php endforeach; ?>

</div>

<h4 class="mb-3">Incoming Donations</h4>

<?php if (count($deliveries) == 0): ?>

    <div class="alert alert-info">
        No incoming donations yet.
    </div>

<?php else: ?>

    <div class="row g-4">

        <?php foreach ($deliveries as $delivery): ?>

            <?php
            $canConfirm = canCharityConfirmDelivery($delivery);

            $statusClass = 'secondary';

            if ($delivery['delivery_status'] == 'Received') {
                $statusClass = 'success';

            } elseif (in_array($delivery['reservation_status'], ['Approved', 'Collected'], true)) {
                $statusClass = 'warning';

            } elseif ($delivery['reservation_status'] == 'Rejected') {
                $statusClass = 'danger';

            } elseif ($delivery['reservation_status'] == 'Pending') {
                $statusClass = 'info';
            }
            ?>

            <div class="col-md-6">

                <div class="card h-100 p-3 shadow-sm">

                    <div class="d-flex justify-content-between gap-3">

                        <h5>
                            <?= escapeHtml($delivery['food_name']) ?>
                        </h5>

                        <span class="badge bg-<?= $statusClass ?>">
                            <?= escapeHtml($delivery['delivery_status']) ?>
                        </span>

                    </div>


                    <p class="mb-1">
                        <strong>Quantity:</strong>
                        <?= escapeHtml($delivery['quantity']) ?>
                    </p>

                    <p class="mb-1">
                        <strong>Donor:</strong>
                        <?= escapeHtml($delivery['donor_name']) ?>
                    </p>

                    <p class="mb-1">
                        <strong>Pickup From:</strong>
                        <?= escapeHtml($delivery['pickup_location']) ?>
                    </p>

                    <p class="mb-1">
                        <strong>Volunteer:</strong>
                        <?= escapeHtml($delivery['volunteer_name']) ?>

                        <small class="text-muted">
                            <?= escapeHtml($delivery['volunteer_email']) ?>
                        </small>
                    </p>

                    <p class="mb-1">
                        <strong>Reserved At:</strong>
                        <?= showDate($delivery['reservation_time']) ?>
                    </p>

                    <p class="mb-1">
                        <strong>Available:</strong>
                        <?= showDate($delivery['available_time']) ?>
                    </p>

                    <p class="mb-1">
                        <strong>Expires:</strong>
                        <?= showDate($delivery['expiry_time']) ?>
                    </p>

                    <p class="mb-1">
                        <strong>Request Status:</strong>
                        <?= escapeHtml($delivery['reservation_status']) ?>
                    </p>

                    <p class="mb-3">
                        <strong>Donation Status:</strong>
                        <?= escapeHtml($delivery['donation_status']) ?>
                    </p>


                    <div class="d-flex gap-2 flex-wrap">

                        <a
                            href="track_order.php?id=<?= (int)$delivery['reservation_id'] ?>"
                            class="btn btn-outline-primary"
                        >
                            Track
                        </a>


                        <?php if ($canConfirm): ?>

                            <form
                                method="post"
                                action="confirm_delivery.php"
                                class="flex-grow-1"
                            >
                                <?= csrfInputField() ?>

                                <input
                                    type="hidden"
                                    name="reservation_id"
                                    value="<?= (int)$delivery['reservation_id'] ?>"
                                >

                                <button
                                    type="submit"
                                    class="btn btn-success w-100"
                                >
                                    Confirm Received
                                </button>
                            </form>


                        <?php elseif ($delivery['delivery_status'] == 'Received'): ?>

                            <div class="alert alert-success mb-0 flex-grow-1 py-2">
                                Donation received.
                            </div>


                        <?php else: ?>

                            <div class="alert alert-secondary mb-0 flex-grow-1 py-2">
                                Waiting donor approval.
                            </div>

                        <?php endif; ?>

                    </div>

                </div>
            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

<?php include 'footer.php'; ?>
