<?php
// הדף מאפשר ל-Restaurant או Admin להוסיף תרומת מזון חדשה.
require_once 'functions.php';

if (
    empty($_SESSION['user_id']) ||
    (
        ($_SESSION['role'] ?? '') != 'Donor' &&
        ($_SESSION['role'] ?? '') != 'Admin'
    )
) {
    redirect('login.php');
}

$cities = [
    'Sakhnin', 'Rama', 'Karmiel', 'Haifa', 'Nazareth',
    'Acre', 'Nahariya', 'Tel Aviv', 'Jerusalem',
    'Majd al-Krum', 'Deir al-Asad'
];

$foodTypes = [
    'Bakery', 'Meals', 'Produce', 'Dessert',
    'Dairy', 'Drinks', 'Other'
];

$foodName = '';
$foodType = '';
$quantity = '';
$description = '';
$ingredients = '';
$allergens = '';
$storageInstructions = '';
$location = '';
$availableTime = '';
$expiryTime = '';
$latitude = '';
$longitude = '';
$message = '';


// Validate the actual contents and dimensions of every uploaded image.
function uploadFoodImage(&$error)
{
    if (!isset($_FILES['food_image']) ||
        $_FILES['food_image']['error'] == UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES['food_image'];
    if ($file['error'] != UPLOAD_ERR_OK) {
        $error = 'An error occurred while uploading the image.';
        return null;
    }
    if ($file['size'] <= 0 || $file['size'] > 2 * 1024 * 1024) {
        $error = 'Image size must be between 1 byte and 2MB.';
        return null;
    }
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        $error = 'Invalid upload.';
        return null;
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp'
    ];
    if (!isset($allowed[$extension])) {
        $error = 'Only JPG, PNG, and WEBP images are allowed.';
        return null;
    }

    $image = @getimagesize($file['tmp_name']);
    if ($image === false ||
        $image[0] <= 0 || $image[1] <= 0 ||
        $image[0] > 4096 || $image[1] > 4096) {
        $error = 'Invalid image or dimensions exceed 4096 pixels.';
        return null;
    }

    if (!class_exists('finfo')) {
        $error = 'Image validation is unavailable on this server.';
        return null;
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if ($mimeType !== $allowed[$extension] ||
        ($image['mime'] ?? '') !== $allowed[$extension]) {
        $error = 'Image contents do not match its file extension.';
        return null;
    }

    $folder = __DIR__ . DIRECTORY_SEPARATOR . 'Imges' . DIRECTORY_SEPARATOR;
    if (!is_dir($folder) && !@mkdir($folder, 0755, true) && !is_dir($folder)) {
        $error = 'Image storage folder is not available.';
        return null;
    }

    $safeExtension = $extension === 'jpeg' ? 'jpg' : $extension;
    $name = 'food_' . bin2hex(random_bytes(16)) . '.' . $safeExtension;
    if (move_uploaded_file($file['tmp_name'], $folder . $name)) {
        return $name;
    }

    $error = 'Failed to upload image.';
    return null;
}


// טיפול בטופס.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $foodName = trim($_POST['food_name'] ?? '');
    $foodType = trim($_POST['food_type'] ?? '');
    $quantity = trim($_POST['quantity'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $ingredients = trim($_POST['ingredients'] ?? '');
    $allergens = trim($_POST['allergens'] ?? '');
    $storageInstructions = trim($_POST['storage_instructions'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $availableTime = trim($_POST['available_time'] ?? '');
    $expiryTime = trim($_POST['expiry_time'] ?? '');
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request. Please refresh the page and try again.';

    } elseif (
        $foodName == '' ||
        $foodType == '' ||
        $quantity == '' ||
        $location == '' ||
        $availableTime == '' ||
        $expiryTime == '' ||
        $latitude == '' ||
        $longitude == ''
    ) {
        $message = 'Please complete all required fields.';

    } elseif (!in_array($foodType, $foodTypes)) {
        $message = 'Please select a valid food type.';

    } elseif (!in_array($location, $cities)) {
        $message = 'Please select a valid city.';

    } elseif (
        !is_numeric($latitude) ||
        !is_numeric($longitude) ||
        $latitude < -90 ||
        $latitude > 90 ||
        $longitude < -180 ||
        $longitude > 180
    ) {
        $message = 'Please select a valid pickup point on the map.';

    } elseif (
        strtotime($availableTime) == false ||
        strtotime($expiryTime) == false
    ) {
        $message = 'Please enter valid date and time values.';

    } elseif (
        strtotime($expiryTime) <=
        strtotime($availableTime)
    ) {
        $message = 'Expiry time must be after available time.';

    } elseif (strtotime($expiryTime) <= time()) {
        $message = 'Expiry time must be in the future.';

    } elseif (strlen($foodName) > 150) {
        $message = 'Food name is too long.';

    } elseif (strlen($quantity) > 100) {
        $message = 'Quantity is too long.';

    } elseif (strlen($description) > 2000) {
        $message = 'Description is too long.';

    } elseif (strlen($ingredients) > 2000) {
        $message = 'Ingredients text is too long.';

    } elseif (strlen($allergens) > 1000) {
        $message = 'Allergens text is too long.';

    } elseif (strlen($storageInstructions) > 2000) {
        $message = 'Storage instructions are too long.';

    } else {

        $foodImage = uploadFoodImage($message);

        if ($message == '') {

            $stmt = $pdo->prepare("
                INSERT INTO donations
                (
                    user_id,
                    food_name,
                    food_type,
                    quantity,
                    description,
                    ingredients,
                    allergens,
                    storage_instructions,
                    location,
                    latitude,
                    longitude,
                    available_time,
                    expiry_time,
                    food_image
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $saved = $stmt->execute([
                (int)$_SESSION['user_id'],
                $foodName,
                $foodType,
                $quantity,
                $description != '' ? $description : null,
                $ingredients != '' ? $ingredients : null,
                $allergens != '' ? $allergens : null,
                $storageInstructions != '' ? $storageInstructions : null,
                $location,
                (float)$latitude,
                (float)$longitude,
                $availableTime,
                $expiryTime,
                $foodImage
            ]);

            if ($saved) {
                redirect('donor_dashboard.php');
            }

            $message =
                'The food offer could not be saved. Please try again.';
        }
    }
}

include 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-9">
        <div class="card shadow-sm p-4">

            <h2 class="mb-3">List a New Food Offer</h2>

            <p class="text-muted">
                Add food details, location and expiry time.
            </p>

            <?php if ($message != ''): ?>
                <div class="alert alert-warning">
                    <?= escapeHtml($message) ?>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <?= csrfInputField() ?>

                <label class="form-label">
                    Food name <span class="text-danger">*</span>
                </label>

                <input
                    type="text"
                    name="food_name"
                    class="form-control mb-3"
                    maxlength="150"
                    value="<?= escapeHtml($foodName) ?>"
                    required
                >

                <label class="form-label">
                    Food type <span class="text-danger">*</span>
                </label>

                <select
                    name="food_type"
                    class="form-select mb-3"
                    required
                >
                    <option value="">Select food type</option>

                    <?php foreach ($foodTypes as $type): ?>
                        <option
                            value="<?= escapeHtml($type) ?>"
                            <?= $foodType == $type ? 'selected' : '' ?>
                        >
                            <?= escapeHtml($type) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label class="form-label">Food image</label>

                <input
                    type="file"
                    name="food_image"
                    class="form-control mb-1"
                    accept=".jpg,.jpeg,.png,.webp"
                >

                <div class="form-text mb-3">
                    JPG, PNG or WEBP. Maximum size: 2MB.
                </div>

                <label class="form-label">
                    Quantity <span class="text-danger">*</span>
                </label>

                <input
                    type="text"
                    name="quantity"
                    class="form-control mb-3"
                    maxlength="100"
                    value="<?= escapeHtml($quantity) ?>"
                    required
                >

                <label class="form-label">Ingredients</label>

                <textarea
                    name="ingredients"
                    class="form-control mb-3"
                    rows="3"
                    maxlength="2000"
                ><?= escapeHtml($ingredients) ?></textarea>

                <label class="form-label">Allergens</label>

                <textarea
                    name="allergens"
                    class="form-control mb-3"
                    rows="3"
                    maxlength="1000"
                ><?= escapeHtml($allergens) ?></textarea>

                <label class="form-label">Storage instructions</label>

                <textarea
                    name="storage_instructions"
                    class="form-control mb-3"
                    rows="3"
                    maxlength="2000"
                ><?= escapeHtml($storageInstructions) ?></textarea>

                <label class="form-label">
                    Pickup city <span class="text-danger">*</span>
                </label>

                <select
                    name="location"
                    id="pickupCity"
                    class="form-select mb-3"
                    required
                >
                    <option value="">Select pickup city</option>

                    <?php foreach ($cities as $city): ?>
                        <option
                            value="<?= escapeHtml($city) ?>"
                            <?= $location == $city ? 'selected' : '' ?>
                        >
                            <?= escapeHtml($city) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="alert alert-info py-2">
                    Select the pickup point on the map.
                </div>

                <div
                    id="pickupMap"
                    style="height:340px;border-radius:12px;margin-bottom:12px;"
                ></div>

                <input
                    type="hidden"
                    name="latitude"
                    id="latitude"
                    value="<?= escapeHtml($latitude) ?>"
                >

                <input
                    type="hidden"
                    name="longitude"
                    id="longitude"
                    value="<?= escapeHtml($longitude) ?>"
                >

                <div
                    id="selectedLocation"
                    class="small text-muted mb-3"
                >
                    <?php if ($latitude != '' && $longitude != ''): ?>
                        Selected:
                        <?= escapeHtml($latitude) ?>,
                        <?= escapeHtml($longitude) ?>
                    <?php else: ?>
                        No point selected yet.
                    <?php endif; ?>
                </div>

                <label class="form-label">
                    Available time <span class="text-danger">*</span>
                </label>

                <input
                    type="datetime-local"
                    name="available_time"
                    id="availableTime"
                    class="form-control mb-3"
                    value="<?= escapeHtml($availableTime) ?>"
                    required
                >

                <label class="form-label">
                    Expiry time <span class="text-danger">*</span>
                </label>

                <input
                    type="datetime-local"
                    name="expiry_time"
                    id="expiryTime"
                    class="form-control mb-3"
                    value="<?= escapeHtml($expiryTime) ?>"
                    required
                >

                <label class="form-label">
                    Additional description
                </label>

                <textarea
                    name="description"
                    class="form-control mb-3"
                    rows="4"
                    maxlength="2000"
                ><?= escapeHtml($description) ?></textarea>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Submit Offer
                </button>

            </form>

        </div>
    </div>
</div>

<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
// מיקום בסיסי של הערים.
var cityPoints = {
    'Sakhnin': [32.8536, 35.4203],
    'Rama': [32.9370, 35.3670],
    'Karmiel': [32.9167, 35.3333],
    'Haifa': [32.8191, 34.9885],
    'Nazareth': [32.6997, 35.2972],
    'Acre': [32.9281, 35.0827],
    'Nahariya': [33.0068, 35.0977],
    'Tel Aviv': [32.0853, 34.7818],
    'Jerusalem': [31.7683, 35.2137],
    'Majd al-Krum': [32.9210, 35.2560],
    'Deir al-Asad': [32.9361, 35.2687]
};

var latitudeInput = document.getElementById('latitude');
var longitudeInput = document.getElementById('longitude');
var selectedLocation = document.getElementById('selectedLocation');
var pickupCity = document.getElementById('pickupCity');


// יצירת המפה.
var pickupMap = L.map('pickupMap')
    .setView([32.85, 35.25], 9);

L.tileLayer(
    'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }
).addTo(pickupMap);

var pickupMarker = null;


// שמירת נקודה שנבחרה.
function choosePoint(lat, lon)
{
    latitudeInput.value = lat.toFixed(7);
    longitudeInput.value = lon.toFixed(7);

    selectedLocation.textContent =
        'Selected: ' +
        lat.toFixed(5) +
        ', ' +
        lon.toFixed(5);

    if (pickupMarker != null) {
        pickupMarker.setLatLng([lat, lon]);
    } else {
        pickupMarker = L.marker([lat, lon])
            .addTo(pickupMap);
    }
}


// לחיצה על המפה.
pickupMap.on('click', function (event) {
    choosePoint(
        event.latlng.lat,
        event.latlng.lng
    );
});


// מעבר לעיר שנבחרה.
pickupCity.addEventListener('change', function () {
    var point = cityPoints[this.value];

    if (point) {
        pickupMap.setView(point, 14);
    }
});


// שמירת נקודה לאחר שגיאה בטופס.
var savedLat = parseFloat(latitudeInput.value);
var savedLon = parseFloat(longitudeInput.value);

if (!isNaN(savedLat) && !isNaN(savedLon)) {
    choosePoint(savedLat, savedLon);
    pickupMap.setView([savedLat, savedLon], 15);
}


// זמן התפוגה חייב להיות אחרי זמן הזמינות.
var availableTimeInput =
    document.getElementById('availableTime');

var expiryTimeInput =
    document.getElementById('expiryTime');

availableTimeInput.addEventListener(
    'change',
    function () {
        if (availableTimeInput.value == '') {
            return;
        }

        expiryTimeInput.min =
            availableTimeInput.value;

        if (
            expiryTimeInput.value != '' &&
            expiryTimeInput.value <= availableTimeInput.value
        ) {
            expiryTimeInput.value = '';
        }
    }
);
</script>

<?php include 'footer.php'; ?>
