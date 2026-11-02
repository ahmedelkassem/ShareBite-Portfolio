<?php
// סיכום הדף:
// הדף מאפשר לבעל התרומה או למנהל לערוך תרומה קיימת.
// הדף בודק הרשאות, בודק את הנתונים ושומר את השינויים במסד הנתונים.

require_once 'functions.php';
checkLogin();

// קבלת מספר התרומה מהקישור.
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    redirect('index.php');
}

// שליפת התרומה ממסד הנתונים.
$offer = dbFetchOne(
    "
    SELECT *
    FROM donations
    WHERE donation_id = ?
    ",
    [$id]
);

if (!$offer) {
    redirect('index.php');
}

// רק בעל התרומה או Admin יכולים לערוך אותה.
$currentRole =
    $_SESSION['role'] ?? '';

$currentUserId =
    (int)($_SESSION['user_id'] ?? 0);


if (
    $currentRole != 'Admin' &&
    $currentUserId != (int)$offer['user_id']
) {
    redirect('index.php');
}

// פונקציה להגנת טקסט לפני הצגתו ב-HTML.
// סוגי המזון המותרים.
$foodTypes = [
    'Bakery',
    'Meals',
    'Produce',
    'Dessert',
    'Dairy',
    'Drinks',
    'Other'
];

// קבלת הנתונים הקיימים של התרומה.
$foodName =
    $offer['food_name'] ?? '';

$foodType =
    $offer['food_type'] ?? '';

$quantity =
    $offer['quantity'] ?? '';

$location =
    $offer['location'] ?? '';

$availableTime =
    $offer['available_time'] ?? '';

$expiryTime =
    $offer['expiry_time'] ?? '';

$description =
    $offer['description'] ?? '';

$ingredients =
    $offer['ingredients'] ?? '';

$allergens =
    $offer['allergens'] ?? '';

$storageInstructions =
    $offer['storage_instructions'] ?? '';

$message = '';

// טיפול בטופס כאשר המשתמש לוחץ Save Changes.
if (
    $_SERVER['REQUEST_METHOD'] == 'POST' &&
    isset($_POST['update'])
) {

    // בדיקת CSRF Token.
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {

        $message =
            'Invalid request. Please refresh the page and try again.';

    } else {

        // קריאת הנתונים החדשים מהטופס.
        $foodName =
            trim($_POST['food_name'] ?? '');

        $foodType =
            trim($_POST['food_type'] ?? '');

        $quantity =
            trim($_POST['quantity'] ?? '');

        $location =
            trim($_POST['location'] ?? '');

        $availableTime =
            trim($_POST['available_time'] ?? '');

        $expiryTime =
            trim($_POST['expiry_time'] ?? '');

        $description =
            trim($_POST['description'] ?? '');

        $ingredients =
            trim($_POST['ingredients'] ?? '');

        $allergens =
            trim($_POST['allergens'] ?? '');

        $storageInstructions =
            trim($_POST['storage_instructions'] ?? '');

        // בדיקת שדות חובה.
        if (
            $foodName == '' ||
            $foodType == '' ||
            $quantity == '' ||
            $location == '' ||
            $availableTime == '' ||
            $expiryTime == ''
        ) {

            $message =
                'Please fill in all required fields.';

        }

        // בדיקת סוג המזון.
        elseif (!in_array($foodType, $foodTypes)) {

            $message =
                'Please select a valid food type.';

        }

        // בדיקת התאריכים.
        elseif (
            strtotime($availableTime) == false ||
            strtotime($expiryTime) == false
        ) {

            $message =
                'Please enter valid date and time values.';

        }

        // זמן התפוגה חייב להיות אחרי זמן הזמינות.
        elseif (
            strtotime($expiryTime) <=
            strtotime($availableTime)
        ) {

            $message =
                'Expiry time must be after available time.';

        }

        // בדיקת אורך הטקסטים.
        elseif (strlen($foodName) > 150) {

            $message =
                'Food name must be 150 characters or less.';

        } elseif (strlen($quantity) > 100) {

            $message =
                'Quantity must be 100 characters or less.';

        } elseif (strlen($description) > 2000) {

            $message =
                'Description is too long.';

        } elseif (strlen($ingredients) > 2000) {

            $message =
                'Ingredients text is too long.';

        } elseif (strlen($allergens) > 1000) {

            $message =
                'Allergens text is too long.';

        } elseif (strlen($storageInstructions) > 2000) {

            $message =
                'Storage instructions are too long.';

        } else {

            // הכנת ערכים אופציונליים למסד הנתונים.
            $descriptionDb = null;
            $ingredientsDb = null;
            $allergensDb = null;
            $storageDb = null;

            if ($description != '') {
                $descriptionDb = $description;
            }

            if ($ingredients != '') {
                $ingredientsDb = $ingredients;
            }

            if ($allergens != '') {
                $allergensDb = $allergens;
            }

            if ($storageInstructions != '') {
                $storageDb = $storageInstructions;
            }

            // עדכון התרומה במסד הנתונים.
            $updated = dbExecute(
                "
                UPDATE donations

                SET
                    food_name = ?,
                    food_type = ?,
                    quantity = ?,
                    description = ?,
                    ingredients = ?,
                    allergens = ?,
                    storage_instructions = ?,
                    location = ?,
                    available_time = ?,
                    expiry_time = ?

                WHERE donation_id = ?
                ",
                [
                    $foodName,
                    $foodType,
                    $quantity,
                    $descriptionDb,
                    $ingredientsDb,
                    $allergensDb,
                    $storageDb,
                    $location,
                    $availableTime,
                    $expiryTime,
                    $id
                ]
            );

            // הודעה לאחר העדכון.
            if ($updated) {

                $message =
                    'Update successful!';

            } else {

                $message =
                    'Error updating donation. Please try again.';
            }
        }
    }
}

// הכנת התאריכים להצגה בתוך datetime-local.
$availableValue = '';

if ($availableTime != '') {

    $availableValue =
        date(
            'Y-m-d\TH:i',
            strtotime($availableTime)
        );
}

$expiryValue = '';

if ($expiryTime != '') {

    $expiryValue =
        date(
            'Y-m-d\TH:i',
            strtotime($expiryTime)
        );
}

// קביעת צבע הודעת התוצאה.
$messageClass = 'danger';

if ($message == 'Update successful!') {
    $messageClass = 'success';
}

// הצגת החלק העליון של האתר.
include 'header.php';
?>

<div class="container mt-5">

    <form
        method="POST"
        class="col-md-9 mx-auto p-4 border rounded bg-white shadow-sm"
    >

        <h3 class="mb-3">
            Edit Donation Details
        </h3>

        <!-- הצגת הודעת הצלחה או שגיאה -->
        <?php if ($message != ''): ?>

            <div class="alert alert-<?= $messageClass ?>">

                <?= escapeHtml($message) ?>

            </div>

        <?php endif; ?>

        <!-- CSRF Token -->
        <?= csrfInputField() ?>

        <div class="row gy-3">

            <!-- שם המזון -->
            <div class="col-md-6">

                <label class="form-label">

                    Food Name

                    <span class="text-danger">
                        *
                    </span>

                </label>

                <input
                    type="text"
                    name="food_name"
                    class="form-control"
                    maxlength="150"
                    value="<?= escapeHtml($foodName) ?>"
                    required
                >
            </div>

            <!-- סוג המזון -->
            <div class="col-md-6">

                <label class="form-label">

                    Food Type

                    <span class="text-danger">
                        *
                    </span>

                </label>

                <select
                    name="food_type"
                    class="form-select"
                    required
                >
                    <option value="">
                        Select food type
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

            <!-- כמות -->
            <div class="col-md-6">

                <label class="form-label">

                    Quantity

                    <span class="text-danger">
                        *
                    </span>

                </label>

                <input
                    type="text"
                    name="quantity"
                    class="form-control"
                    maxlength="100"
                    value="<?= escapeHtml($quantity) ?>"
                    required
                >

            </div>

            <!-- מיקום -->
            <div class="col-md-6">

                <label class="form-label">

                    Location

                    <span class="text-danger">
                        *
                    </span>

                </label>

                <input
                    type="text"
                    name="location"
                    class="form-control"
                    value="<?= escapeHtml($location) ?>"
                    required
                >

            </div>

            <!-- זמן זמינות -->
            <div class="col-md-6">

                <label class="form-label">

                    Available Time

                    <span class="text-danger">
                        *
                    </span>

                </label>

                <input
                    type="datetime-local"
                    name="available_time"
                    id="availableTime"
                    class="form-control"
                    value="<?= escapeHtml($availableValue) ?>"
                    required
                >

            </div>

            <!-- זמן תפוגה -->
            <div class="col-md-6">

                <label class="form-label">

                    Expiry Date & Time

                    <span class="text-danger">
                        *
                    </span>

                </label>

                <input
                    type="datetime-local"
                    name="expiry_time"
                    id="expiryTime"
                    class="form-control"
                    value="<?= escapeHtml($expiryValue) ?>"
                    required
                >

            </div>

            <!-- רכיבים -->
            <div class="col-12">

                <label class="form-label">
                    Ingredients
                </label>

                <textarea
                    name="ingredients"
                    class="form-control"
                    rows="3"
                    maxlength="2000"
                    placeholder="Example: Bread, chicken, cheese"
                ><?= escapeHtml($ingredients) ?></textarea>

            </div>

            <!-- אלרגנים -->
            <div class="col-12">

                <label class="form-label">
                    Allergens
                </label>

                <textarea
                    name="allergens"
                    class="form-control"
                    rows="3"
                    maxlength="1000"
                    placeholder="Example: Contains gluten, eggs or dairy"
                ><?= escapeHtml($allergens) ?></textarea>

                <div class="form-text">

                    Write “None known” if there are no known allergens.

                </div>

            </div>

            <!-- הוראות אחסון -->
            <div class="col-12">

                <label class="form-label">
                    Storage Instructions
                </label>

                <textarea
                    name="storage_instructions"
                    class="form-control"
                    rows="3"
                    maxlength="2000"
                    placeholder="Example: Keep refrigerated below 5°C"
                ><?= escapeHtml($storageInstructions) ?></textarea>

            </div>

            <!-- תיאור -->
            <div class="col-12">

                <label class="form-label">
                    Description
                </label>

                <textarea
                    name="description"
                    class="form-control"
                    rows="4"
                    maxlength="2000"
                ><?= escapeHtml($description) ?></textarea>

            </div>

        </div>

        <!-- שמירת השינויים -->
        <button
            type="submit"
            name="update"
            class="btn btn-warning w-100 mt-4"
        >
            Save Changes
        </button>

    </form>

</div>

<script>

// קבלת שדות התאריך.
var available =
    document.getElementById('availableTime');

var expiry =
    document.getElementById('expiryTime');

// כאשר זמן הזמינות משתנה,
// זמן התפוגה חייב להיות אחריו.
available.addEventListener(
    'change',
    function () {

        if (available.value == '') {
            return;
        }

        expiry.min =
            available.value;

        if (
            expiry.value != '' &&
            expiry.value <= available.value
        ) {

            expiry.value = '';
        }
    }
);

</script>
<?php
include 'footer.php';
?>
