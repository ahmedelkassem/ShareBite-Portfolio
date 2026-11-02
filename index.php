<?php
// סיכום הדף:
// הדף מציג את דף הבית ואת רשימת תרומות המזון הזמינות עם אפשרויות סינון.
// הוא מעדכן תרומות שפג תוקפן ומכין את תנאי החיפוש לפני שליפת התוצאות.

require_once 'functions.php';


// קבלת ערכי הסינון מהקישור.
$location = trim($_GET['location'] ?? '');
$category = trim($_GET['category'] ?? 'All');
$status = trim($_GET['status'] ?? 'All');

$conditions = [];
$params = [];

// Non-expired offers are shown by default. An explicit status filter,
// including Expired, overrides that default.
if ($status === 'All' || $status === '') {
    $conditions[] = "donations.status <> 'Expired'";
}

// בניית תנאי החיפוש לפי הסינונים שנבחרו.
if ($location !== '') {
    $conditions[] = 'donations.location LIKE ?';
    $params[] = "%$location%";
}

$categoryWords = [
    'Bakery' => ['%bread%', '%bakery%'],
    'Meals' => ['%meal%', '%sandwich%'],
    'Produce' => ['%fruit%', '%vegetable%'],
    'Dessert' => ['%cake%', '%dessert%']
];

if (isset($categoryWords[$category])) {
    $conditions[] = '(donations.food_name LIKE ? OR donations.description LIKE ?)';
    [$params[], $params[]] = $categoryWords[$category];
}

if ($status !== 'All' && $status !== '') {
    $conditions[] = 'donations.status = ?';
    $params[] = $status;
}

$sql = 'SELECT donations.*, users.full_name 
        FROM donations 
        JOIN users ON donations.user_id = users.user_id 
        WHERE ' . implode(' AND ', $conditions) . ' 
        ORDER BY donations.available_time ASC';

// ביצוע השאילתה לאחר בניית כל התנאים.
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$offers = $stmt->fetchAll();

$locationStmt = $pdo->query("
    SELECT DISTINCT location
    FROM donations
    WHERE status <> 'Expired'
    ORDER BY location
");
$locations = $locationStmt->fetchAll();

// הגנת טקסט לפני הצגתו ב-HTML.
include 'header.php';
?>

<div class="hero-section">
    <div class="row align-items-center gy-4">
        <div class="col-lg-7">
            <span class="hero-eyebrow">ShareBite</span>
            <h1 class="hero-title">Connect food donors with volunteers to reduce waste.</h1>
            <p class="hero-copy">
                Browse nearby offers, claim fresh meals, and help local kitchens share surplus food safely.
                A simple platform for donation coordination and pickup management.
            </p>

            <div class="hero-actions d-flex flex-wrap gap-2 mt-4">
                <a href="index.php" class="btn btn-primary btn-lg">Browse offers</a>

                <?php if (!empty($_SESSION['user_id']) && $_SESSION['role'] === 'Donor'): ?>
                    <a href="add_offer.php" class="btn btn-outline-primary btn-lg">List an offer</a>
                <?php else: ?>
                    <a href="register.php" class="btn btn-outline-primary btn-lg">Join ShareBite</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="row g-3">
                <?php foreach ([
                    ['Fast matching', 'Volunteers can view offers and choose the correct charity destination before claiming.'],
                    ['Safe coordination', 'Each donation includes pickup details, timing, and delivery destination.'],
                    ['Less waste', 'Help local kitchens redirect extra food to charity centers.']
                ] as [$title, $description]): ?>
                    <div class="col-12 feature-card p-4">
                        <h5><?= escapeHtml($title) ?></h5>
                        <p class="text-muted mb-0"><?= escapeHtml($description) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card filter-card p-4">
            <h2 class="mb-3">Food Offers</h2>

            <form class="row g-3" method="get" action="index.php">
                <div class="col-md-4">
                    <label class="form-label">Location</label>
                    <select name="location" class="form-select">
                        <option value="">All locations</option>

                        <?php foreach ($locations as $loc): ?>
                            <option value="<?= escapeHtml($loc['location']) ?>" <?= $loc['location'] === $location ? 'selected' : '' ?>>
                                <?= escapeHtml($loc['location']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="All">All categories</option>
                        <option value="Bakery" <?= $category === 'Bakery' ? 'selected' : '' ?>>Bakery</option>
                        <option value="Meals" <?= $category === 'Meals' ? 'selected' : '' ?>>Meals</option>
                        <option value="Produce" <?= $category === 'Produce' ? 'selected' : '' ?>>Produce</option>
                        <option value="Dessert" <?= $category === 'Dessert' ? 'selected' : '' ?>>Dessert</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="All">All non-expired statuses</option>
                        <option value="Available" <?= $status === 'Available' ? 'selected' : '' ?>>Available</option>
                        <option value="Pending" <?= $status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Claimed" <?= $status === 'Claimed' ? 'selected' : '' ?>>Claimed</option>
                        <option value="Collected" <?= $status === 'Collected' ? 'selected' : '' ?>>Collected</option>
                        <option value="Expired" <?= $status === 'Expired' ? 'selected' : '' ?>>Expired</option>
                    </select>
                </div>

                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-secondary">Apply filters</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="row g-4">
    <?php if (empty($offers)): ?>
        <div class="col-12">
            <div class="alert alert-info">No offers match your filter. Please try another selection.</div>
        </div>
    <?php endif; ?>

    <?php foreach ($offers as $offer): ?>

        <?php
        // בחירת תמונה מתאימה לתרומה.
        $imagePath = '';

        if (!empty($offer['food_image']) && file_exists('Imges/' . $offer['food_image'])) {
            $imagePath = './Imges/' . $offer['food_image'];
        }

        $offerStatus = $offer['status'];
        ?>

        <div class="col-lg-4">
            <div class="card card-offer shadow-sm h-100 overflow-hidden">

                <?php if ($imagePath !== ''): ?>
                    <img
                        src="<?= escapeHtml($imagePath) ?>"
                        class="card-img-top"
                        alt="<?= escapeHtml($offer['food_name']) ?>"
                        style="height:220px; width:100%; object-fit:cover; display:block;"
                    >
                <?php else: ?>
                    <div class="bg-light text-muted d-flex align-items-center justify-content-center" style="height:220px;">
                        No image uploaded
                    </div>
                <?php endif; ?>

                <div class="card-body d-flex flex-column">
                    <h5 class="card-title"><?= escapeHtml($offer['food_name']) ?></h5>

                    <p class="text-muted mb-1">
                        Quantity: <?= escapeHtml($offer['quantity']) ?>
                    </p>

                    <p class="mb-2">
                        <?= nl2br(escapeHtml(substr($offer['description'], 0, 120))) ?>
                    </p>

                    <p class="mb-1">
                        <strong>Location:</strong> <?= escapeHtml($offer['location']) ?>
                    </p>

                    <p class="mb-1">
                        <strong>Available:</strong> <?= date('M d, Y H:i', strtotime($offer['available_time'])) ?>
                    </p>

                    <p class="mb-1">
                        <strong>Expires:</strong> <?= date('M d, Y H:i', strtotime($offer['expiry_time'])) ?>
                    </p>

                    <div class="mb-3">
                        <span class="status-badge badge-<?= escapeHtml($offerStatus) ?>">
                            <?= escapeHtml($offerStatus) ?>
                        </span>
                    </div>

                    <div class="mt-auto">
                        <a href="offer_details.php?donation_id=<?= (int)$offer['donation_id'] ?>" class="btn btn-outline-primary btn-sm w-100">
                            View details
                        </a>
                    </div>
                </div>

            </div>
        </div>

    <?php endforeach; ?>
</div>

<?php include 'footer.php'; ?>
