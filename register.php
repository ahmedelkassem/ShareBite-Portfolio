<?php
// הדף מאפשר למשתמש ליצור חשבון חדש ולבחור תפקיד ועיר.

require_once 'functions.php';
ensureVolunteerProfileFields();

if (!empty($_SESSION['user_id'])) {
    redirect('index.php');
}

$message = '';

$cities = [
    'Sakhnin', 'Rama', 'Karmiel', 'Haifa', 'Nazareth',
    'Acre', 'Nahariya', 'Tel Aviv', 'Jerusalem',
    'Majd al-Krum', 'Deir al-Asad'
];

// ערכים התחלתיים.
$fullName = '';
$email = '';
$city = '';
$userPhone = '';
$userAddress = '';
$role = 'Volunteer';
$centerName = '';
$centerCity = '';
$address = '';
$phone = '';
$description = '';
$centerLatitude = '';
$centerLongitude = '';


// טיפול בטופס ההרשמה.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $userPhone = trim($_POST['user_phone'] ?? '');
        $userAddress = trim($_POST['user_address'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $role = $_POST['role'] ?? 'Volunteer';

        $centerName = trim($_POST['center_name'] ?? '');
        $centerCity = trim($_POST['center_city'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $centerLatitude = trim($_POST['center_latitude'] ?? '');
        $centerLongitude = trim($_POST['center_longitude'] ?? '');

        // בדיקת התפקיד.
        if (
            $role != 'Volunteer' &&
            $role != 'Donor' &&
            $role != 'Charity'
        ) {
            $role = 'Volunteer';
        }

        // בדיקת שדות חובה.
        if (
            $fullName == '' ||
            $email == '' ||
            $password == '' ||
            $confirmPassword == '' ||
            $city == ''
        ) {
            $message = 'Please fill in all required fields.';

        } elseif (!in_array($city, $cities)) {
            $message = 'Please select a valid city.';

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Enter a valid email address.';

        } elseif ($password != $confirmPassword) {
            $message = 'Passwords do not match.';

        } elseif (
            $role == 'Charity' &&
            (
                $centerName == '' ||
                $centerCity == '' ||
                $address == '' ||
                $phone == '' ||
                $centerLatitude == '' ||
                $centerLongitude == ''
            )
        ) {
            $message = 'Please fill in all charity center details.';

        } elseif (
            $role == 'Charity' &&
            !in_array($centerCity, $cities)
        ) {
            $message = 'Please select a valid charity center city.';

        } else {

            // בדיקה אם האימייל כבר קיים.
            $checkEmail = $pdo->prepare("
                SELECT user_id
                FROM users
                WHERE email = ?
            ");
            $checkEmail->execute([$email]);

            if ($checkEmail->fetch()) {
                $message = 'This email is already registered.';
            } else {
                try {
                    $pdo->beginTransaction();

                    // יצירת המשתמש.
                    $hashedPassword = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                    $insertUser = $pdo->prepare("
                        INSERT INTO users
                        (full_name, email, city, phone, address, password, role)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");

                    $insertUser->execute([
                        $fullName,
                        $email,
                        $city,
                        $userPhone != '' ? $userPhone : null,
                        $userAddress != '' ? $userAddress : null,
                        $hashedPassword,
                        $role
                    ]);

                    $userId = (int)$pdo->lastInsertId();

                    // יצירת מרכז Charity אם נבחר תפקיד Charity.
                    if ($role == 'Charity') {
                        $insertCenter = $pdo->prepare("
                            INSERT INTO charity_centers
                            (
                                user_id, center_name, city, address,
                                latitude, longitude, phone,
                                description, status
                            )
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Active')
                        ");

                        $insertCenter->execute([
                            $userId,
                            $centerName,
                            $centerCity,
                            $address,
                            $centerLatitude,
                            $centerLongitude,
                            $phone,
                            $description
                        ]);
                    }

                    $pdo->commit();
                    redirect('login.php?registered=1');

                } catch (Exception $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    $message = 'Registration failed.';
                }
            }
        }
    }
}


// הגנת טקסט לפני הצגתו.
include 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm p-4">

            <h2 class="mb-3">Create Account</h2>

            <?php if ($message != ''): ?>
                <div class="alert alert-warning">
                    <?= escapeHtml($message) ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <?= csrfInputField() ?>

                <input
                    type="text"
                    name="full_name"
                    class="form-control mb-3"
                    placeholder="Full Name"
                    value="<?= escapeHtml($fullName) ?>"
                    required
                >

                <input
                    type="email"
                    name="email"
                    class="form-control mb-3"
                    placeholder="Email"
                    value="<?= escapeHtml($email) ?>"
                    required
                >

                <input
                    type="password"
                    name="password"
                    class="form-control mb-3"
                    placeholder="Password"
                    required
                >

                <input
                    type="password"
                    name="confirm_password"
                    class="form-control mb-3"
                    placeholder="Confirm Password"
                    required
                >

                <select name="city" class="form-select mb-3" required>
                    <option value="">Select your city / area</option>

                    <?php foreach ($cities as $cityName): ?>
                        <option
                            value="<?= escapeHtml($cityName) ?>"
                            <?= $city == $cityName ? 'selected' : '' ?>
                        >
                            <?= escapeHtml($cityName) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <input
                    type="text"
                    name="user_phone"
                    class="form-control mb-3"
                    placeholder="Phone"
                    value="<?= escapeHtml($userPhone) ?>"
                >

                <input
                    type="text"
                    name="user_address"
                    class="form-control mb-3"
                    placeholder="Address / neighborhood"
                    value="<?= escapeHtml($userAddress) ?>"
                >

                <select
                    name="role"
                    id="roleSelect"
                    class="form-select mb-3"
                >
                    <option
                        value="Volunteer"
                        <?= $role == 'Volunteer' ? 'selected' : '' ?>
                    >
                        Volunteer
                    </option>

                    <option
                        value="Donor"
                        <?= $role == 'Donor' ? 'selected' : '' ?>
                    >
                        Restaurant
                    </option>

                    <option
                        value="Charity"
                        <?= $role == 'Charity' ? 'selected' : '' ?>
                    >
                        Charity Center
                    </option>
                </select>

                <div id="charityFields" style="display:none;">
                    <hr>
                    <h5>Charity Center Details</h5>

                    <input
                        name="center_name"
                        class="form-control mb-3"
                        placeholder="Center Name"
                        value="<?= escapeHtml($centerName) ?>"
                    >

                    <select
                        name="center_city"
                        id="centerCity"
                        class="form-select mb-3"
                    >
                        <option value="">Select charity city</option>

                        <?php foreach ($cities as $cityName): ?>
                            <option
                                value="<?= escapeHtml($cityName) ?>"
                                <?= $centerCity == $cityName ? 'selected' : '' ?>
                            >
                                <?= escapeHtml($cityName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <input
                        name="address"
                        class="form-control mb-3"
                        placeholder="Address"
                        value="<?= escapeHtml($address) ?>"
                    >

                    <div class="alert alert-info py-2">
                        Click the exact charity center location on the map.
                    </div>

                    <div
                        id="charityMap"
                        style="height:320px;border-radius:12px;margin-bottom:12px;"
                    ></div>

                    <input
                        type="hidden"
                        name="center_latitude"
                        id="centerLatitude"
                        value="<?= escapeHtml($centerLatitude) ?>"
                    >

                    <input
                        type="hidden"
                        name="center_longitude"
                        id="centerLongitude"
                        value="<?= escapeHtml($centerLongitude) ?>"
                    >

                    <div
                        id="centerLocationText"
                        class="small text-muted mb-3"
                    >
                        No point selected yet.
                    </div>

                    <input
                        name="phone"
                        class="form-control mb-3"
                        placeholder="Phone"
                        value="<?= escapeHtml($phone) ?>"
                    >

                    <textarea
                        name="description"
                        class="form-control mb-3"
                        rows="3"
                        placeholder="Description"
                    ><?= escapeHtml($description) ?></textarea>
                </div>

                <p class="text-muted small mb-3">
                    By creating an account, you agree to the
                    <a href="terms.php">Terms & Conditions</a>
                    and
                    <a href="privacy.php">Privacy Policy</a>.
                </p>

                <button class="btn btn-primary">
                    Register
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
// אלמנטים מהדף.
var roleSelect = document.getElementById('roleSelect');
var charityFields = document.getElementById('charityFields');
var centerCity = document.getElementById('centerCity');
var centerLatitude = document.getElementById('centerLatitude');
var centerLongitude = document.getElementById('centerLongitude');
var centerLocationText = document.getElementById('centerLocationText');


// הצגת שדות Charity.
function toggleCharityFields()
{
    charityFields.style.display =
        roleSelect.value == 'Charity'
        ? 'block'
        : 'none';
}


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


// יצירת המפה.
var charityMap = L.map('charityMap')
    .setView([32.85, 35.25], 9);

L.tileLayer(
    'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }
).addTo(charityMap);

var charityMarker = null;


// בחירת מיקום על המפה.
function choosePoint(lat, lon)
{
    centerLatitude.value = lat.toFixed(7);
    centerLongitude.value = lon.toFixed(7);

    centerLocationText.textContent =
        'Selected: ' +
        lat.toFixed(5) +
        ', ' +
        lon.toFixed(5);

    if (charityMarker != null) {
        charityMarker.setLatLng([lat, lon]);
    } else {
        charityMarker = L.marker([lat, lon])
            .addTo(charityMap);
    }
}


// לחיצה על המפה.
charityMap.on('click', function (event) {
    choosePoint(
        event.latlng.lat,
        event.latlng.lng
    );
});


// מעבר לעיר שנבחרה.
centerCity.addEventListener('change', function () {
    var point = cityPoints[this.value];

    if (point) {
        charityMap.setView(point, 14);
    }
});


// שינוי Role.
roleSelect.addEventListener('change', function () {
    toggleCharityFields();

    setTimeout(function () {
        charityMap.invalidateSize();
    }, 150);
});


// אם כבר נבחר מיקום אחרי שגיאה בטופס.
var savedLat = parseFloat(centerLatitude.value);
var savedLon = parseFloat(centerLongitude.value);

if (!isNaN(savedLat) && !isNaN(savedLon)) {
    choosePoint(savedLat, savedLon);
    charityMap.setView([savedLat, savedLon], 15);
}

toggleCharityFields();
</script>

<?php include 'footer.php'; ?>
