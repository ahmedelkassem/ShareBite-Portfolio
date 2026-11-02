<?php
// הדף מאפשר לחפש תרומות לפי מיקום, סוג מזון, סטטוס ומילת חיפוש.
require_once 'functions.php';

// קבלת הערכים מהטופס.
$location = trim($_GET['location'] ?? '');
$foodType = trim($_GET['food_type'] ?? '');
$status = trim($_GET['status'] ?? 'All');
$keyword = trim($_GET['keyword'] ?? '');
$sort = trim($_GET['sort'] ?? 'available');
$closeToMe = ($_GET['close_to_me'] ?? '') == '1';
$isVolunteer =
    isLoggedIn() &&
    ($_SESSION['role'] ?? '') == 'Volunteer';
$volunteerCity = '';

if ($isVolunteer) {
    ensureVolunteerProfileFields();

    $volunteer = dbFetchOne("
        SELECT city
        FROM users
        WHERE user_id = ?
        LIMIT 1
    ", [(int)$_SESSION['user_id']]);

    $volunteerCity = trim($volunteer['city'] ?? '');
}


// סוגי המזון.
$foodTypes = [
    'Bakery',
    'Meals',
    'Produce',
    'Dessert',
    'Dairy',
    'Drinks',
    'Other'
];


// ערכי חיפוש בסיסיים.
if ($location == '') {
    $locationSearch = '%';
} else {
    $locationSearch = $location;
}

if ($status == 'All' || $status == '') {
    $statusSearch = '%';
} else {
    $statusSearch = $status;
}

if ($closeToMe && $isVolunteer) {
    $location = $volunteerCity;
    $locationSearch = $volunteerCity != '' ? $volunteerCity : '__location_not_set__';
    $status = 'Pending';
    $statusSearch = 'Pending';
}

$keywordSearch = '%' . $keyword . '%';


// שאילתת החיפוש.
$sql = "
    SELECT
        d.*,
        u.full_name
    FROM donations d
    JOIN users u
        ON d.user_id = u.user_id
    WHERE d.location LIKE ?
    AND d.status LIKE ?
    AND d.status NOT IN ('Expired', 'Collected')
    AND (
        d.food_name LIKE ?
        OR d.description LIKE ?
        OR d.location LIKE ?
        OR u.full_name LIKE ?
    )
";

$params = [
    $locationSearch,
    $statusSearch,
    $keywordSearch,
    $keywordSearch,
    $keywordSearch,
    $keywordSearch
];


// אם נבחר סוג מזון, מוסיפים אותו לחיפוש.
if ($foodType != '') {
    $sql .= " AND d.food_type = ?";
    $params[] = $foodType;
}


// מיון התוצאות.
if ($sort == 'expiry') {
    $sql .= " ORDER BY d.expiry_time ASC";
} else {
    $sql .= " ORDER BY d.available_time ASC";
}


// ביצוע החיפוש.
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$offers = $stmt->fetchAll(PDO::FETCH_ASSOC);


// קבלת המיקומים הקיימים.
$locations = $pdo->query("
    SELECT DISTINCT location
    FROM donations
    WHERE location IS NOT NULL
    AND location != ''
    ORDER BY location ASC
")->fetchAll(PDO::FETCH_ASSOC);


// צבע לפי סטטוס.
function badgeClass($status)
{
    if ($status == 'Available') {
        return 'success';
    }

    if ($status == 'Pending') {
        return 'info';
    }

    if ($status == 'Claimed') {
        return 'warning';
    }

    return 'secondary';
}


// הצגת תאריך.
function showDate($value)
{
    $time = strtotime($value);

    if ($time == false) {
        return 'Not specified';
    }

    return date('M d, Y H:i', $time);
}


include 'header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card p-4 shadow-sm">

            <h2 class="mb-3">
                Search Donations
            </h2>

            <form method="get" action="search_offers.php" class="row g-3">

                <!-- מיקום -->
                <div class="col-md-3">
                    <label class="form-label">
                        Location
                    </label>

                    <select name="location" class="form-select">
                        <option value="">
                            All locations
                        </option>

                        <?php foreach ($locations as $loc): ?>

                            <option
                                value="<?= escapeHtml($loc['location']) ?>"
                                <?php
                                if ($location == $loc['location']) {
                                    echo 'selected';
                                }
                                ?>
                            >
                                <?= escapeHtml($loc['location']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>


                <!-- סוג מזון -->
                <div class="col-md-3">
                    <label class="form-label">
                        Food Type
                    </label>

                    <select name="food_type" class="form-select">
                        <option value="">
                            All food types
                        </option>

                        <?php foreach ($foodTypes as $type): ?>

                            <option
                                value="<?= escapeHtml($type) ?>"
                                <?php
                                if ($foodType == $type) {
                                    echo 'selected';
                                }
                                ?>
                            >
                                <?= escapeHtml($type) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>


                <!-- מילת חיפוש -->
                <div class="col-md-3">
                    <label class="form-label">
                        Keyword
                    </label>

                    <input
                        type="text"
                        name="keyword"
                        class="form-control"
                        placeholder="Food, donor or location"
                        value="<?= escapeHtml($keyword) ?>"
                    >
                </div>


                <!-- סטטוס -->
                <div class="col-md-3">
                    <label class="form-label">
                        Status
                    </label>

                    <select name="status" class="form-select">

                        <option
                            value="All"
                            <?php if ($status == 'All') echo 'selected'; ?>
                        >
                            All statuses
                        </option>

                        <option
                            value="Available"
                            <?php if ($status == 'Available') echo 'selected'; ?>
                        >
                            Available
                        </option>

                        <option
                            value="Pending"
                            <?php if ($status == 'Pending') echo 'selected'; ?>
                        >
                            Pending
                        </option>

                        <option
                            value="Claimed"
                            <?php if ($status == 'Claimed') echo 'selected'; ?>
                        >
                            Claimed
                        </option>

                    </select>
                </div>


                <!-- מיון -->
                <div class="col-md-4">
                    <label class="form-label">
                        Sort By
                    </label>

                    <select name="sort" class="form-select">

                        <option
                            value="available"
                            <?php if ($sort == 'available') echo 'selected'; ?>
                        >
                            Available Soonest
                        </option>

                        <option
                            value="expiry"
                            <?php if ($sort == 'expiry') echo 'selected'; ?>
                        >
                            Expiring Soonest
                        </option>

                    </select>
                </div>


                <div class="col-md-4 d-flex align-items-end">
                    <button
                        type="submit"
                        class="btn btn-success w-100"
                    >
                        Search
                    </button>
                </div>


                <div class="col-md-4 d-flex align-items-end">
                    <a
                        href="search_offers.php"
                        class="btn btn-outline-secondary w-100"
                    >
                        Reset Filters
                    </a>
                </div>

                <?php if ($isVolunteer): ?>
                    <div class="col-md-4 d-flex align-items-end">
                        <a
                            href="search_offers.php?close_to_me=1"
                            class="btn btn-outline-success w-100"
                        >
                            Offers close to me
                        </a>
                    </div>
                <?php endif; ?>

            </form>

            <?php if ($closeToMe && $isVolunteer): ?>
                <div class="alert alert-info mt-3 mb-0">
                    Showing pending offers in your location:
                    <?= escapeHtml($volunteerCity != '' ? $volunteerCity : 'not set') ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>


<!-- תוצאות החיפוש -->
<div class="row g-4">

    <?php if (count($offers) == 0): ?>

        <div class="col-12">
            <div class="alert alert-info">
                No donations found for these criteria.
            </div>
        </div>

    <?php endif; ?>


    <?php foreach ($offers as $offer): ?>

        <?php
        $statusValue = $offer['status'];
        ?>

        <div class="col-lg-4">

            <div class="card shadow-sm h-100">

                <div class="card-body d-flex flex-column">

                    <div class="d-flex justify-content-between mb-2">

                        <h5 class="card-title">
                            <?= escapeHtml($offer['food_name']) ?>
                        </h5>

                        <span class="badge bg-<?= badgeClass($statusValue) ?>">
                            <?= escapeHtml($statusValue) ?>
                        </span>

                    </div>


                    <p class="mb-1">
                        <strong>Food Type:</strong>

                        <?php if (!empty($offer['food_type'])): ?>
                            <?= escapeHtml($offer['food_type']) ?>
                        <?php else: ?>
                            Not specified
                        <?php endif; ?>
                    </p>


                    <p class="mb-1">
                        <strong>Donor:</strong>
                        <?= escapeHtml($offer['full_name']) ?>
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


                    <?php if (!empty($offer['allergens'])): ?>

                        <div class="alert alert-warning py-2">
                            <strong>Allergens:</strong>
                            <?= escapeHtml($offer['allergens']) ?>
                        </div>

                    <?php endif; ?>


                    <div class="mt-auto">

                        <a
                            href="offer_details.php?donation_id=<?= (int)$offer['donation_id'] ?>"
                            class="btn btn-outline-primary w-100"
                        >
                            View Details
                        </a>

                    </div>

                </div>

            </div>

        </div>

    <?php endforeach; ?>

</div>

<?php include 'footer.php'; ?>
