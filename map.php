<?php
// סיכום הדף:
// הדף מציג מפה עם מיקומי התרומות ומרכזי הצדקה.
// אם לתרומה נבחר מרכז צדקה, מוצג קו פשוט בין שני המקומות.
require_once 'functions.php';


// שליפת התרומות והפרטים הדרושים להצגה על המפה.
$donations = dbFetchAll("
    SELECT
        d.donation_id,
        d.food_name,
        d.food_type,
        d.quantity,
        d.location,
        d.latitude AS donor_latitude,
        d.longitude AS donor_longitude,
        d.expiry_time,
        d.status,

        donor.full_name AS donor_name,

        r.delivery_status,

        c.center_id,
        c.center_name,
        c.city AS charity_city,
        c.address AS charity_address,
        c.latitude AS charity_latitude,
        c.longitude AS charity_longitude

    FROM donations d

    JOIN users donor
        ON d.user_id = donor.user_id

    LEFT JOIN reservations r
        ON d.donation_id = r.donation_id
        AND r.status IN ('Pending', 'Approved', 'Completed')

    LEFT JOIN charity_centers c
        ON r.charity_center_id = c.center_id

    WHERE d.status IN
        ('Available', 'Pending', 'Claimed', 'Collected')

    ORDER BY d.expiry_time ASC
");

// קואורדינטות בסיסיות של הערים.
// משתמשים בהן אם לא נשמר מיקום מדויק.
$cityCoordinates = [
    'Tel Aviv' => [32.0853, 34.7818],
    'Haifa' => [32.8191, 34.9885],
    'Nazareth' => [32.6997, 35.2972],
    'Karmiel' => [32.9167, 35.3333],
    'Sakhnin' => [32.8536, 35.4203],
    'Jaffa' => [32.0505, 34.7613],
    'Jerusalem' => [31.7683, 35.2137],
    'Deir al-Asad' => [32.9361, 35.2687],
    'Nahariya' => [33.0068, 35.0977],
    'Acre' => [32.9281, 35.0827],
    'Rama' => [32.9370, 35.3670],
    'Majd al-Krum' => [32.9210, 35.2560]
];

// פונקציה שמחזירה קואורדינטות לפי שם העיר.
function fallbackCoordinates($text, $cities)
{
    foreach ($cities as $city => $coordinates) {

        if (stripos($text, $city) !== false) {

            return $coordinates;
        }
    }

    // מיקום ברירת מחדל באזור הצפון.
    return [32.85, 35.25];
}

// פונקציה להגנת טקסט לפני הצגתו ב-HTML.
// פונקציה להצגת התאריך בצורה ברורה.
function showDate($value)
{
    if (empty($value)) {

        return 'Not specified';
    }

    $time = strtotime($value);

    if ($time == false) {

        return 'Not specified';
    }

    return date(
        'M d, Y H:i',
        $time
    );
}

// הכנת הנתונים שיועברו ל-JavaScript.
$mapItems = [];

foreach ($donations as $donation) {

    // קביעת מיקום התורם.
    if (
        $donation['donor_latitude'] !== null &&
        $donation['donor_longitude'] !== null
    ) {

        $donorLatitude =
            (float)$donation['donor_latitude'];

        $donorLongitude =
            (float)$donation['donor_longitude'];

    } else {

        $donorCoordinates =
            fallbackCoordinates(
                $donation['location'],
                $cityCoordinates
            );

        $donorLatitude =
            $donorCoordinates[0];

        $donorLongitude =
            $donorCoordinates[1];
    }

    // בדיקה אם נבחר מרכז צדקה.
    $hasCharity = false;

    $charityLatitude = null;
    $charityLongitude = null;

    if (!empty($donation['center_id'])) {

        $hasCharity = true;

        // אם קיימות קואורדינטות מדויקות של המרכז.
        if (
            $donation['charity_latitude'] !== null &&
            $donation['charity_longitude'] !== null
        ) {

            $charityLatitude =
                (float)$donation['charity_latitude'];

            $charityLongitude =
                (float)$donation['charity_longitude'];

        } else {

            // אם אין קואורדינטות,
            // משתמשים בעיר ובכתובת.
            $charityText =
                ($donation['charity_city'] ?? '') .
                ' ' .
                ($donation['charity_address'] ?? '');

            $charityCoordinates =
                fallbackCoordinates(
                    $charityText,
                    $cityCoordinates
                );

            $charityLatitude =
                $charityCoordinates[0];

            $charityLongitude =
                $charityCoordinates[1];
        }
    }

    // הוספת התרומה לרשימת הנתונים של המפה.
    $mapItems[] = [

        'donation_id' =>
            (int)$donation['donation_id'],

        'food_name' =>
            $donation['food_name'],

        'food_type' =>
            $donation['food_type'] ?? '',

        'quantity' =>
            $donation['quantity'],

        'donor_name' =>
            $donation['donor_name'],

        'donor_location' =>
            $donation['location'],

        'donor_lat' =>
            $donorLatitude,

        'donor_lon' =>
            $donorLongitude,

        'charity_name' =>
            $donation['center_name'] ?? '',

        'charity_city' =>
            $donation['charity_city'] ?? '',

        'charity_address' =>
            $donation['charity_address'] ?? '',

        'charity_lat' =>
            $charityLatitude,

        'charity_lon' =>
            $charityLongitude,

        'has_charity' =>
            $hasCharity,

        'status' =>
            $donation['status'],

        'delivery_status' =>
            $donation['delivery_status'] ?? 'Waiting',

        'expiry_time' =>
            $donation['expiry_time']
    ];
}

// הצגת החלק העליון של האתר.
include 'header.php';
?>

<!-- קובץ העיצוב של Leaflet -->
<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
>

<style>

/* עיצוב המפה. */
#map {
    height: 650px;
    border-radius: 16px;
    box-shadow: 0 8px 25px rgba(0,0,0,.15);
}

/* עיצוב הקופסאות ליד המפה. */
.map-box {
    background: #fff;
    border-radius: 14px;
    padding: 18px;
    box-shadow: 0 4px 16px rgba(0,0,0,.08);
    margin-bottom: 18px;
}

/* עיצוב שורת מקרא. */
.legend-item {
    display: flex;
    align-items: center;
    gap: 9px;
    margin-bottom: 10px;
}

/* עיגול במקרא. */
.legend-dot {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    display: inline-block;
}

/* צבע התורם. */
.donor-dot {
    background: #dc3545;
}

/* צבע מרכז הצדקה. */
.charity-dot {
    background: #198754;
}

/* קו בין התורם למרכז הצדקה. */
.route-line {
    width: 28px;
    height: 4px;
    background: #0d6efd;
    border-radius: 4px;
    display: inline-block;
}

/* עיצוב כרטיס תרומה. */
.route-card {
    border-left: 5px solid #0d6efd;
    background: #fff;
    padding: 13px;
    border-radius: 10px;
    margin-bottom: 11px;
    cursor: pointer;
}

/* עיצוב מידע קטן בכרטיס. */
.route-info {
    font-size: .88rem;
    color: #555;
}
</style>

<div class="container mt-5">

    <!-- כותרת הדף -->
    <div class="map-box">
        <h2 class="mb-1">
            Food Donation Map
        </h2>

        <p class="text-muted mb-0">
            Donation and charity center locations.
        </p>

    </div>

    <div class="row g-4">

        <!-- המפה -->
        <div class="col-lg-8">
            <div id="map"></div>

        </div>

        <!-- מידע ליד המפה -->
        <div class="col-lg-4">

            <!-- מקרא -->
            <div class="map-box">

                <h5>
                    Legend
                </h5>

                <div class="legend-item">
                    <span class="legend-dot donor-dot"></span>

                    Donor / Restaurant

                </div>

                <div class="legend-item">

                    <span class="legend-dot charity-dot"></span>

                    Charity Center

                </div>

                <div class="legend-item">

                    <span class="route-line"></span>

                    Connection

                </div>

            </div>

            <!-- רשימת התרומות -->
            <div
                class="map-box"
                style="max-height:520px;overflow:auto;"
            >
                <h5>
                    Active Donations
                </h5>

                <?php if (count($mapItems) == 0): ?>

                    <p class="text-muted">
                        No active donations.
                    </p>

                <?php endif; ?>

                <?php foreach ($mapItems as $index => $item): ?>
                    <div
                        class="route-card"
                        onclick="focusDonation(<?= $index ?>)"
                    >
                        <!-- שם המזון -->
                        <strong>
                            <?= escapeHtml($item['food_name']) ?>
                        </strong>

                        <!-- סוג המזון -->
                        <div class="route-info">

                            Type:

                            <?php if ($item['food_type'] != ''): ?>

                                <?= escapeHtml($item['food_type']) ?>

                            <?php else: ?>

                                Not specified

                            <?php endif; ?>

                        </div>

                        <!-- כמות -->
                        <div class="route-info">

                            Quantity:

                            <?= escapeHtml($item['quantity']) ?>

                        </div>

                        <!-- שם התורם -->
                        <div class="route-info">

                            Donor:

                            <?= escapeHtml($item['donor_name']) ?>

                        </div>

                        <!-- מיקום -->
                        <div class="route-info">

                            Pickup:

                            <?= escapeHtml($item['donor_location']) ?>

                        </div>

                        <!-- מרכז צדקה -->
                        <div class="route-info">

                            Charity:

                            <?php if ($item['has_charity']): ?>

                                <?= escapeHtml($item['charity_name']) ?>

                            <?php else: ?>

                                Not selected yet

                            <?php endif; ?>

                        </div>

                        <!-- זמן תפוגה -->
                        <div class="route-info">

                            Expires:

                            <?= showDate($item['expiry_time']) ?>

                        </div>

                        <!-- סטטוס -->
                        <div class="route-info">

                            Status:

                            <?= escapeHtml($item['status']) ?>

                        </div>

                        <!-- מעבר לפרטי התרומה -->
                        <a
                            href="offer_details.php?donation_id=<?= (int)$item['donation_id'] ?>"
                            class="btn btn-sm btn-outline-primary mt-2"
                            onclick="event.stopPropagation();"
                        >
                            View Offer
                        </a>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>

    </div>

</div>

<!-- ספריית Leaflet -->
<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
></script>

<script>

// קבלת נתוני התרומות מ-PHP.
// json_encode הופך מערך PHP לנתונים של JavaScript.
var mapItems =
    <?= json_encode($mapItems, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

// יצירת המפה.
var map =
    L.map('map')
     .setView(
        [32.85, 35.25],
        9
     );

// הוספת OpenStreetMap.
L.tileLayer(
    'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    {
        maxZoom: 19,
        attribution:
            '&copy; OpenStreetMap contributors'
    }
).addTo(map);

// יצירת אייקון פשוט למפה.
function makeIcon(color)
{
    return L.divIcon({

        html:
            '<div style="' +
            'width:25px;' +
            'height:25px;' +
            'background:' + color + ';' +
            'border-radius:50%;' +
            'border:4px solid white;' +
            '"></div>',

        iconSize: [33, 33],

        className: ''
    });
}

// אייקון אדום לתורם.
var donorIcon =
    makeIcon('#dc3545');

// אייקון ירוק למרכז צדקה.
var charityIcon =
    makeIcon('#198754');

// רשימת כל הנקודות במפה.
var allPoints = [];

// פונקציה להגנת טקסט לפני הצגתו ב-Popup.
function safeText(value)
{
    var div =
        document.createElement('div');

    div.textContent =
        value == null ? '' : value;

    return div.innerHTML;
}

// מעבר על כל התרומות והצגתן במפה.
for (
    var i = 0;
    i < mapItems.length;
    i++
) {

    var item =
        mapItems[i];

    // יצירת Marker של התורם.
    var donorPoint = [
        item.donor_lat,
        item.donor_lon
    ];

    allPoints.push(
        donorPoint
    );

    L.marker(
        donorPoint,
        {
            icon: donorIcon
        }
    )
    .addTo(map)
    .bindPopup(

        '<strong>' +
        safeText(item.food_name) +
        '</strong><br>' +

        'Donor: ' +
        safeText(item.donor_name) +
        '<br>' +

        'Location: ' +
        safeText(item.donor_location) +
        '<br>' +

        'Quantity: ' +
        safeText(item.quantity)

    );

    // אם קיימת Charity,
    // מציגים גם אותה ומציירים קו.
    if (item.has_charity == true) {
        var charityPoint = [
            item.charity_lat,
            item.charity_lon
        ];

        allPoints.push(
            charityPoint
        );

        // Marker של מרכז הצדקה.
        L.marker(
            charityPoint,
            {
                icon: charityIcon
            }
        )
        .addTo(map)
        .bindPopup(

            '<strong>' +
            safeText(item.charity_name) +
            '</strong><br>' +

            'City: ' +
            safeText(item.charity_city) +
            '<br>' +

            'Address: ' +
            safeText(item.charity_address)

        );

        // קו פשוט בין התורם למרכז.
        L.polyline(
            [
                donorPoint,
                charityPoint
            ],
            {
                weight: 4
            }
        ).addTo(map);
    }
}

// מיקוד המפה בתרומה שנבחרה מהרשימה.
function focusDonation(index)
{
    var item =
        mapItems[index];


    if (!item) {
        return;
    }

    // אם יש Charity,
    // מציגים את שתי הנקודות.
    if (item.has_charity == true) {

        map.fitBounds(
            [
                [
                    item.donor_lat,
                    item.donor_lon
                ],
                [
                    item.charity_lat,
                    item.charity_lon
                ]
            ],
            {
                padding: [50, 50]
            }
        );

    } else {

        // אם אין Charity,
        // מתמקדים רק בתורם.
        map.setView(
            [
                item.donor_lat,
                item.donor_lon
            ],
            14
        );
    }
}

// התאמת המפה כך שכל הנקודות יוצגו.

if (allPoints.length > 0) {

    map.fitBounds(
        allPoints,
        {
            padding: [45, 45]
        }
    );
}

</script>

<?php
// הצגת החלק התחתון של האתר.
include 'footer.php';
?>
