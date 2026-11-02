<?php
// הדף מציג את התרומות של ה-Donor ואת הבקשות שהתקבלו עליהן.

require_once 'functions.php';

checkRole('Donor');
ensureVolunteerProfileFields();

$donorId = (int)$_SESSION['user_id'];

$donations = dbFetchAll("
    SELECT *
    FROM donations
    WHERE user_id = ?
    ORDER BY available_time DESC
", [$donorId]);

$monthStats = dbFetchOne("
    SELECT
        COUNT(*) AS total_month,
        SUM(CASE WHEN status = 'Collected' THEN 1 ELSE 0 END) AS collected_month,
        SUM(CASE WHEN status = 'Expired' THEN 1 ELSE 0 END) AS expired_month
    FROM donations
    WHERE user_id = ?
    AND available_time >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
", [$donorId]);

include 'header.php';
?>

<div class="container mt-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Restaurant Dashboard</h2>

        <a href="add_offer.php" class="btn btn-success">
            Add Donation
        </a>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm p-4 bg-primary text-white">
                <h5>Offers This Month</h5>
                <h2><?= (int)$monthStats['total_month'] ?></h2>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm p-4 bg-success text-white">
                <h5>Collected This Month</h5>
                <h2><?= (int)$monthStats['collected_month'] ?></h2>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm p-4 bg-danger text-white">
                <h5>Expired This Month</h5>
                <h2><?= (int)$monthStats['expired_month'] ?></h2>
            </div>
        </div>
    </div>

    <?php if (count($donations) == 0): ?>
        <div class="alert alert-info">
            You have not posted any donations yet.
        </div>
    <?php endif; ?>


    <?php foreach ($donations as $donation): ?>

        <?php
        // קבלת הבקשות של התרומה.
        $requests = dbFetchAll("
            SELECT
                r.*,
                u.full_name,
                u.email,
                u.phone,
                u.address,
                u.city AS volunteer_city,
                c.center_name,
                c.city
            FROM reservations r
            JOIN users u ON r.user_id = u.user_id
            LEFT JOIN charity_centers c
                ON r.charity_center_id = c.center_id
            WHERE r.donation_id = ?
            ORDER BY r.reservation_time DESC
        ", [$donation['donation_id']]);
        ?>

        <div class="card mb-3">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-start">

                    <div>
                        <h5>
                            <?= escapeHtml($donation['food_name']) ?>

                            <small class="text-muted">
                                (<?= escapeHtml($donation['location']) ?>)
                            </small>
                        </h5>

                        <p>
                            <?= escapeHtml(substr($donation['description'] ?? '', 0, 120)) ?>
                        </p>

                        <p>
                            <strong>Status:</strong>
                            <?= escapeHtml($donation['status']) ?>
                        </p>
                    </div>

                    <a
                        href="edit_donation.php?id=<?= (int)$donation['donation_id'] ?>"
                        class="btn btn-warning"
                    >
                        Edit Donation
                    </a>

                </div>

                <hr>


                <?php if (count($requests) == 0): ?>

                    <div class="alert alert-secondary mb-0">
                        No requests for this donation.
                    </div>

                <?php else: ?>

                    <div class="table-responsive">

                        <table class="table">

                            <thead>
                                <tr>
                                    <th>Volunteer</th>
                                    <th>Charity</th>
                                    <th>Status</th>
                                    <th>Delivery</th>
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody>

                            <?php foreach ($requests as $row): ?>

                                <tr>

                                    <td>
                                        <?= escapeHtml($row['full_name']) ?><br>
                                        <small><?= escapeHtml($row['email']) ?></small>
                                        <br>
                                        <small>Phone: <?= escapeHtml($row['phone'] ?? 'Not set') ?></small>
                                        <br>
                                        <small>
                                            Location:
                                            <?= escapeHtml($row['volunteer_city'] ?? 'Not set') ?>
                                            <?php if (!empty($row['address'])): ?>
                                                - <?= escapeHtml($row['address']) ?>
                                            <?php endif; ?>
                                        </small>
                                    </td>

                                    <td>
                                        <?= escapeHtml($row['center_name'] ?? 'Not selected') ?>

                                        <?php if (!empty($row['city'])): ?>
                                            <br>
                                            <small><?= escapeHtml($row['city']) ?></small>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= escapeHtml($row['status']) ?>
                                    </td>

                                    <td>
                                        <?= escapeHtml($row['delivery_status'] ?? '') ?>
                                    </td>

                                    <td>

                                        <!-- אישור או דחיית בקשה -->
                                        <?php if ($row['status'] == 'Pending'): ?>

                                            <form
                                                method="post"
                                                action="donor_action.php"
                                                class="d-inline-block"
                                            >
                                                <?= csrfInputField() ?>

                                                <input
                                                    type="hidden"
                                                    name="reservation_id"
                                                    value="<?= (int)$row['reservation_id'] ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="approve"
                                                >

                                                <button class="btn btn-sm btn-success">
                                                    Approve
                                                </button>
                                            </form>


                                            <form
                                                method="post"
                                                action="donor_action.php"
                                                class="d-inline-block"
                                            >
                                                <?= csrfInputField() ?>

                                                <input
                                                    type="hidden"
                                                    name="reservation_id"
                                                    value="<?= (int)$row['reservation_id'] ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="reject"
                                                >

                                                <button class="btn btn-sm btn-danger">
                                                    Reject
                                                </button>
                                            </form>


                                        <?php elseif (($row['delivery_status'] ?? '') == 'Received'): ?>

                                            <span class="badge bg-success">
                                                Received
                                            </span>

                                        <?php else: ?>

                                            <span class="text-muted">
                                                Waiting charity
                                            </span>

                                        <?php endif; ?>


                                        <a
                                            href="track_order.php?id=<?= (int)$row['reservation_id'] ?>"
                                            class="btn btn-sm btn-outline-secondary"
                                        >
                                            Track
                                        </a>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            </tbody>
                        </table>

                    </div>

                <?php endif; ?>

            </div>
        </div>

    <?php endforeach; ?>

</div>

<?php include 'footer.php'; ?>
