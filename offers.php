<?php
require_once 'functions.php';

$isVolunteer =
    isLoggedIn() &&
    ($_SESSION['role'] ?? '') == 'Volunteer';

$isDonor =
    isLoggedIn() &&
    ($_SESSION['role'] ?? '') == 'Donor';

$donorStats = null;

if ($isDonor) {
    $donorStats = dbFetchOne("
        SELECT
            COUNT(*) AS total_month,
            SUM(CASE WHEN status = 'Collected' THEN 1 ELSE 0 END) AS collected_month,
            SUM(CASE WHEN status = 'Expired' THEN 1 ELSE 0 END) AS expired_month
        FROM donations
        WHERE user_id = ?
        AND available_time >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
    ", [(int)$_SESSION['user_id']]);
}

$savedIds = [];

if ($isVolunteer) {
    $savedRows = dbFetchAll(
        "SELECT donation_id
         FROM saved_offers
         WHERE user_id = ?",
        [(int)$_SESSION['user_id']]
    );

    foreach ($savedRows as $row) {
        $savedIds[] = (int)$row['donation_id'];
    }
}

$foodTypes = [
    'Bakery', 'Meals', 'Produce', 'Dessert',
    'Dairy', 'Drinks', 'Other'
];

$foodType = trim($_GET['food_type'] ?? '');
$sort = trim($_GET['sort'] ?? 'available');

if ($foodType != '' && !in_array($foodType, $foodTypes)) {
    $foodType = '';
}

if ($sort != 'available' && $sort != 'expiry') {
    $sort = 'available';
}

$orderBy = 'd.available_time ASC';

if ($sort == 'expiry') {
    $orderBy = 'd.expiry_time ASC';
}

// שליפת התרומות.
if ($isDonor) {
    if ($foodType == '') {
        $result = dbFetchAll("
            SELECT d.*, u.full_name AS donor_name
            FROM donations d
            JOIN users u ON d.user_id = u.user_id
            WHERE d.user_id = ?
            ORDER BY $orderBy
        ", [(int)$_SESSION['user_id']]);
    } else {
        $result = dbFetchAll("
            SELECT d.*, u.full_name AS donor_name
            FROM donations d
            JOIN users u ON d.user_id = u.user_id
            WHERE d.user_id = ?
            AND d.food_type = ?
            ORDER BY $orderBy
        ", [(int)$_SESSION['user_id'], $foodType]);
    }
} elseif ($foodType == '') {
    $result = dbFetchAll("
        SELECT d.*, u.full_name AS donor_name
        FROM donations d
        JOIN users u ON d.user_id = u.user_id
        WHERE d.status NOT IN ('Expired', 'Collected')
        ORDER BY $orderBy
    ");
} else {
    $result = dbFetchAll("
        SELECT d.*, u.full_name AS donor_name
        FROM donations d
        JOIN users u ON d.user_id = u.user_id
        WHERE d.status NOT IN ('Expired', 'Collected')
        AND d.food_type = ?
        ORDER BY $orderBy
    ", [$foodType]);
}

// בחירת תמונה לתרומה.
function offerImage($offer)
{
    if (!empty($offer['food_image'])) {
        $file = 'Imges/' . $offer['food_image'];

        if (is_file($file)) {
            return './Imges/' . $offer['food_image'];
        }
    }

    return '';
}

// צבע Badge לפי מצב התרומה.
function badgeClass($status)
{
    if ($status == 'Available') return 'success';
    if ($status == 'Pending') return 'info';
    if ($status == 'Claimed') return 'warning';

    return 'secondary';
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

<div class="container mt-5">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h2 class="mb-1">Available Food Offers</h2>
            <p class="text-muted mb-0">
                <?= $isDonor
                    ? 'Your restaurant offers are shown here.'
                    : 'Browse available food donations.' ?>
            </p>
        </div>

        <a href="map.php" class="btn btn-outline-success">
            View Offers Map
        </a>
    </div>

    <?php if ($isDonor && $donorStats): ?>
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm p-4 bg-primary text-white">
                    <h5>Offers This Month</h5>
                    <h2><?= (int)$donorStats['total_month'] ?></h2>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm p-4 bg-success text-white">
                    <h5>Collected This Month</h5>
                    <h2><?= (int)$donorStats['collected_month'] ?></h2>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm p-4 bg-danger text-white">
                    <h5>Expired This Month</h5>
                    <h2><?= (int)$donorStats['expired_month'] ?></h2>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- סינון ומיון -->
    <div class="card shadow-sm p-3 mb-4">
        <form method="get" action="offers.php" class="row g-3 align-items-end">

            <div class="col-md-4">
                <label class="form-label">Food Type</label>

                <select name="food_type" class="form-select">
                    <option value="">All food types</option>

                    <?php foreach ($foodTypes as $type): ?>
                        <option
                            value="<?= escapeHtml($type) ?>"
                            <?= $foodType == $type ? 'selected' : '' ?>
                        >
                            <?= escapeHtml($type) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Sort By</label>

                <select name="sort" class="form-select">
                    <option
                        value="available"
                        <?= $sort == 'available' ? 'selected' : '' ?>
                    >
                        Available Soonest
                    </option>

                    <option
                        value="expiry"
                        <?= $sort == 'expiry' ? 'selected' : '' ?>
                    >
                        Expiring Soonest
                    </option>
                </select>
            </div>

            <div class="col-md-2">
                <button type="submit" class="btn btn-success w-100">
                    Filter
                </button>
            </div>

            <div class="col-md-2">
                <a href="offers.php" class="btn btn-outline-secondary w-100">
                    Reset
                </a>
            </div>

        </form>
    </div>

    <!-- הצגת התרומות -->
    <div class="row g-4">

        <?php if (count($result) > 0): ?>

            <?php foreach ($result as $offer): ?>

                <?php
                $donationId = (int)$offer['donation_id'];
                $status = $offer['status'] ?? '';

                $saved = in_array(
                    $donationId,
                    $savedIds
                );

                $saveAction = $saved
                    ? 'remove_saved_offer.php'
                    : 'save_offer.php';

                $saveButtonClass = $saved
                    ? 'btn-outline-danger'
                    : 'btn-outline-primary';

                $saveButtonText = $saved
                    ? 'Remove from Favorites'
                    : 'Save as Favorite';
                ?>

                <div class="col-md-6">
                    <div class="card shadow-sm h-100 overflow-hidden">

                        <?php $imagePath = offerImage($offer); ?>
                        <?php if ($imagePath !== ''): ?>
                            <img
                                src="<?= escapeHtml($imagePath) ?>"
                                class="card-img-top"
                                alt="<?= escapeHtml($offer['food_name']) ?>"
                                style="height:230px;width:100%;object-fit:cover;"
                            >
                        <?php else: ?>
                            <div class="bg-light text-muted d-flex align-items-center justify-content-center" style="height:230px;">
                                No image uploaded
                            </div>
                        <?php endif; ?>

                        <div class="card-body d-flex flex-column">

                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">

                                <h5 class="card-title mb-0">
                                    <?= escapeHtml($offer['food_name']) ?>
                                </h5>

                                <span class="badge bg-<?= badgeClass($status) ?>">
                                    <?= escapeHtml($status) ?>
                                </span>

                            </div>

                            <p class="mb-1">
                                <strong>Food Type:</strong>

                                <?= !empty($offer['food_type'])
                                    ? escapeHtml($offer['food_type'])
                                    : 'Not specified' ?>
                            </p>

                            <p class="mb-1">
                                <strong>Donor:</strong>
                                <?= escapeHtml($offer['donor_name']) ?>
                            </p>

                            <p class="mb-1">
                                <strong>Quantity:</strong>
                                <?= escapeHtml($offer['quantity']) ?>
                            </p>

                            <p class="mb-1">
                                <strong>Location:</strong>
                                <?= escapeHtml($offer['location']) ?>
                            </p>

                            <p class="mb-1">
                                <strong>Available:</strong>
                                <?= showDate($offer['available_time']) ?>
                            </p>

                            <p class="mb-3">
                                <strong>Expires:</strong>
                                <?= showDate($offer['expiry_time']) ?>
                            </p>

                            <div class="mt-auto">

                                <?php if ($isVolunteer): ?>

                                    <form method="post" action="<?= escapeHtml($saveAction) ?>">
                                        <?= csrfInputField() ?>

                                        <input
                                            type="hidden"
                                            name="donation_id"
                                            value="<?= $donationId ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="return"
                                            value="offers.php"
                                        >

                                        <button
                                            type="submit"
                                            class="btn <?= escapeHtml($saveButtonClass) ?> w-100 mb-2"
                                        >
                                            <?= escapeHtml($saveButtonText) ?>
                                        </button>
                                    </form>

                                <?php endif; ?>

                                <a
                                    href="offer_details.php?donation_id=<?= $donationId ?>"
                                    class="btn btn-outline-success w-100"
                                >
                                    View Details
                                </a>

                            </div>

                        </div>
                    </div>
                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="col-12">
                <div class="alert alert-info">
                    No offers available right now.
                </div>
            </div>

        <?php endif; ?>

    </div>
</div>

<?php include 'footer.php'; ?>
