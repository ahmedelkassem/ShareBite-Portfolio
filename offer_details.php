<?php
require_once 'functions.php';

$donationId = (int)($_GET['donation_id'] ?? 0);
$offer = null;
$charityCenters = [];
$reviews = [];
$saved = false;
$averageRating = 0;

// אם התקבל מזהה תרומה תקין, נטען את פרטי ההצעה ממסד הנתונים.
if ($donationId > 0) {
    $stmt = $pdo->prepare("
        SELECT d.*, u.full_name
        FROM donations d
        JOIN users u ON d.user_id = u.user_id
        WHERE d.donation_id = ?
          AND d.status <> 'Expired'
        LIMIT 1
    ");
    $stmt->execute([$donationId]);
    $offer = $stmt->fetch(PDO::FETCH_ASSOC);
}

// אם נמצאה הצעה, נטען מרכזי צדקה פעילים וביקורות הקשורות להצעה.
if ($offer) {
    $charityCenters = dbFetchAll("
        SELECT center_id, center_name, city, address
        FROM charity_centers
        WHERE status = 'Active'
        ORDER BY city ASC
    ");

    $reviews = dbFetchAll("
        SELECT r.rating, r.comment, r.created_at, u.full_name
        FROM review r
        JOIN users u ON r.user_id = u.user_id
        WHERE r.donation_id = ?
        ORDER BY r.created_at DESC
    ", [$donationId]);

    // אם קיימות ביקורות, נחשב את ממוצע הדירוגים.
    if (count($reviews) > 0) {
        $total = 0;

        foreach ($reviews as $review) {
            $total += (int)$review['rating'];
        }

        $averageRating = $total / count($reviews);
    }
}

$isVolunteer =
    !empty($_SESSION['user_id']) &&
    ($_SESSION['role'] ?? '') === 'Volunteer';

// אם המשתמש הוא מתנדב ויש הצעה, נבדוק אם ההצעה כבר שמורה אצלו.
if ($offer && $isVolunteer) {
    $saveStmt = $pdo->prepare("
        SELECT saved_offer_id
        FROM saved_offers
        WHERE user_id = ?
          AND donation_id = ?
        LIMIT 1
    ");
    $saveStmt->execute([
        (int)$_SESSION['user_id'],
        $donationId
    ]);

    $saved = (bool)$saveStmt->fetch();
}

// מציגה תאריך בפורמט קריא, ואם הערך לא תקין מציגה הודעת ברירת מחדל.
function showDate($value)
{
    $time = strtotime((string)$value);

    return $time !== false
        ? date('M d, Y H:i', $time)
        : 'Not specified';
}

// מחזירה את נתיב תמונת ההצעה אם הקובץ קיים, אחרת מחזירה מחרוזת ריקה.
function offerImage($offer)
{
    // אם קיימת תמונה להצעה, נבנה את הנתיב שלה.
    if (!empty($offer['food_image'])) {
        $file = 'Imges/' . $offer['food_image'];

        // אם קובץ התמונה באמת קיים בתיקייה, נחזיר את הנתיב להצגה.
        if (file_exists($file)) {
            return './Imges/' . $offer['food_image'];
        }
    }

    return '';
}

$canRequest =
    $isVolunteer &&
    $offer &&
    ($offer['status'] ?? '') === 'Available';

$favoriteAction = $saved
    ? 'remove_saved_offer.php'
    : 'save_offer.php';

$favoriteClass = $saved
    ? 'btn-outline-warning'
    : 'btn-outline-primary';

$favoriteText = $saved
    ? 'Remove from favorites'
    : 'Save to favorites';

include 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">

        <?php
        // אם קיימת הודעת שגיאה בכתובת, נציג אותה למשתמש.
        if (!empty($_GET['error'])):
        ?>
            <div class="alert alert-warning">
                <?= escapeHtml($_GET['error']) ?>
            </div>
        <?php endif; ?>

        <?php
        // אם לא נמצאה הצעה, נציג הודעה מתאימה במקום פרטי ההצעה.
        if (!$offer):
        ?>

            <div class="alert alert-warning">
                Offer not found or no longer available.
            </div>

        <?php else: ?>

            <?php
            $imagePath = offerImage($offer);
            $status = $offer['status'] ?? 'Unknown';
            $statusClass = $status === 'Available' ? 'success' : 'secondary';
            $allergens = trim($offer['allergens'] ?? '');

            $hasAllergens =
                $allergens !== '' &&
                strtolower($allergens) !== 'none known';
            ?>

            <div class="card shadow-sm overflow-hidden">

                <?php
                // אם קיימת תמונה להצעה, נציג אותה בראש הכרטיס.
                if ($imagePath !== ''):
                ?>
                    <img
                        src="<?= escapeHtml($imagePath) ?>"
                        alt="<?= escapeHtml($offer['food_name']) ?>"
                        style="height:300px;width:100%;object-fit:cover;"
                    >
                <?php else: ?>
                    <div class="bg-light text-muted d-flex align-items-center justify-content-center" style="height:300px;">
                        No image uploaded
                    </div>
                <?php endif; ?>

                <div class="p-4">

                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                        <div>
                            <h2><?= escapeHtml($offer['food_name']) ?></h2>

                            <p class="text-muted">
                                <strong>Donor:</strong>
                                <?= escapeHtml($offer['full_name']) ?>
                            </p>
                        </div>

                        <span class="badge bg-<?= $statusClass ?> fs-6">
                            <?= escapeHtml($status) ?>
                        </span>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <p>
                                <strong>Food type:</strong>
                                <?= !empty($offer['food_type'])
                                    ? escapeHtml($offer['food_type'])
                                    : 'Not specified' ?>
                            </p>

                            <p>
                                <strong>Quantity:</strong>
                                <?= escapeHtml($offer['quantity']) ?>
                            </p>

                            <p>
                                <strong>Pickup location:</strong>
                                <?= escapeHtml($offer['location']) ?>
                            </p>
                        </div>

                        <div class="col-md-6">
                            <p>
                                <strong>Available from:</strong>
                                <?= showDate($offer['available_time']) ?>
                            </p>

                            <p>
                                <strong>Expires:</strong>
                                <?= showDate($offer['expiry_time']) ?>
                            </p>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <strong>Donation Flow:</strong>
                        Restaurant → Volunteer → Charity Center → Received
                    </div>

                    <div class="card border-0 bg-light p-3 mb-3">
                        <h5>Food Safety Information</h5>

                        <p>
                            <strong>Ingredients:</strong><br>
                            <?= !empty($offer['ingredients'])
                                ? nl2br(escapeHtml($offer['ingredients']))
                                : 'No ingredients were provided.' ?>
                        </p>

                        <?php
                        // אם דווחו אלרגנים, נציג אזהרת אלרגנים.
                        if ($hasAllergens):
                        ?>
                            <div class="alert alert-danger">
                                <strong>Allergen warning:</strong><br>
                                <?= nl2br(escapeHtml($allergens)) ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <strong>Allergens:</strong>
                                No known allergens were reported by the donor.
                            </div>
                        <?php endif; ?>

                        <p>
                            <strong>Storage instructions:</strong><br>
                            <?= !empty($offer['storage_instructions'])
                                ? nl2br(escapeHtml($offer['storage_instructions']))
                                : 'No storage instructions were provided.' ?>
                        </p>
                    </div>

                    <p>
                        <strong>Description:</strong><br>
                        <?= !empty($offer['description'])
                            ? nl2br(escapeHtml($offer['description']))
                            : 'No additional description was provided.' ?>
                    </p>

                    <?php
                    // אם המתנדב יכול לבקש את התרומה, נציג טופס בחירת מרכז צדקה.
                    if ($canRequest):
                    ?>

                        <div class="card p-3 mb-3">
                            <h5>Choose Charity Center</h5>

                            <?php
                            // אם אין מרכזי צדקה פעילים, נציג הודעת אזהרה.
                            if (count($charityCenters) == 0):
                            ?>

                                <div class="alert alert-warning">
                                    No active charity centers are available.
                                </div>

                            <?php else: ?>

                                <form action="claim_offer.php" method="post">
                                    <?= csrfInputField() ?>

                                    <input
                                        type="hidden"
                                        name="donation_id"
                                        value="<?= (int)$offer['donation_id'] ?>"
                                    >

                                    <select
                                        name="charity_center_id"
                                        class="form-select mb-3"
                                        required
                                    >
                                        <option value="">
                                            Select charity center
                                        </option>

                                        <?php foreach ($charityCenters as $center): ?>
                                            <option value="<?= (int)$center['center_id'] ?>">
                                                <?= escapeHtml($center['center_name']) ?>
                                                -
                                                <?= escapeHtml($center['city']) ?>
                                                -
                                                <?= escapeHtml($center['address']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <button class="btn btn-success">
                                        Request Donation
                                    </button>
                                </form>

                            <?php endif; ?>
                        </div>

                    <?php
                    // אם המשתמש מתנדב אבל ההצעה לא זמינה, נציג הודעה מתאימה.
                    elseif ($isVolunteer && $status !== 'Available'):
                    ?>

                        <div class="alert alert-secondary">
                            This offer is not available for a new request.
                        </div>

                    <?php endif; ?>

                    <?php
                    // אם המשתמש מתנדב, נציג אפשרות לשמור או להסיר מהמועדפים.
                    if ($isVolunteer):
                    ?>

                        <form
                            method="post"
                            action="<?= escapeHtml($favoriteAction) ?>"
                            class="d-inline-block"
                        >
                            <?= csrfInputField() ?>

                            <input
                                type="hidden"
                                name="donation_id"
                                value="<?= (int)$offer['donation_id'] ?>"
                            >

                            <input
                                type="hidden"
                                name="return"
                                value="offer_details.php?donation_id=<?= (int)$offer['donation_id'] ?>"
                            >

                            <button class="btn <?= escapeHtml($favoriteClass) ?>">
                                <?= escapeHtml($favoriteText) ?>
                            </button>
                        </form>

                    <?php endif; ?>

                    <div class="mt-3">
                        <a href="offers.php" class="btn btn-link">
                            Back to offers
                        </a>

                        <a href="map.php" class="btn btn-outline-success">
                            View Delivery Map
                        </a>
                    </div>

                </div>
            </div>

            <!-- Reviews -->
            <div class="card shadow-sm mt-4">
                <div class="card-body p-4">

                    <div class="d-flex justify-content-between align-items-center">
                        <h4>Reviews</h4>

                        <?php
                        // אם קיימות ביקורות, נציג את ממוצע הדירוג.
                        if (count($reviews) > 0):
                        ?>
                            <span class="badge bg-warning text-dark">
                                <?= number_format($averageRating, 1) ?>/5
                            </span>
                        <?php endif; ?>
                    </div>

                    <hr>

                    <?php
                    // אם אין ביקורות, נציג הודעה שאין עדיין ביקורות.
                    if (count($reviews) == 0):
                    ?>

                        <div class="alert alert-light border">
                            No reviews yet.
                        </div>

                    <?php else: ?>

                        <?php foreach ($reviews as $review): ?>

                            <div class="border rounded p-3 mb-3">

                                <strong>
                                    <?= escapeHtml($review['full_name']) ?>
                                </strong>

                                <div class="text-warning">
                                    <?php
                                    $rating = (int)$review['rating'];

                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= $rating ? '★' : '☆';
                                    }
                                    ?>
                                </div>

                                <small class="text-muted">
                                    <?= showDate($review['created_at']) ?>
                                </small>

                                <p class="mt-2 mb-0">
                                    <?= nl2br(escapeHtml($review['comment'])) ?>
                                </p>

                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>
            </div>

        <?php endif; ?>

    </div>
</div>

<?php include 'footer.php'; ?>
