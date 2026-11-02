<?php
require_once 'functions.php';

checkRole('Admin');

// סופר את כל רשומות התרומות במערכת.
$totalDonations = dbFetchOne("
    SELECT COUNT(*) AS count
    FROM donations
");

// סופר את התרומות שעדיין זמינות למתנדבים.
$activeDonations = dbFetchOne("
    SELECT COUNT(*) AS count
    FROM donations
    WHERE status = 'Available'
");

// סופר בקשות הזמנה שממתינות לאישור התורם.
$pendingReservations = dbFetchOne("
    SELECT COUNT(*) AS count
    FROM reservations
    WHERE status = 'Pending'
");

// שולף את כמות התרומות לפי חודש עבור הגרף החודשי.
$monthlyRows = dbFetchAll("
    SELECT
        DATE_FORMAT(available_time, '%Y-%m') AS donation_month,
        COUNT(*) AS donation_count
    FROM donations
    WHERE available_time IS NOT NULL
    GROUP BY donation_month
    ORDER BY donation_month ASC
");

// שולף את כמות התרומות לפי עיר עבור גרף הערים.
$cityRows = dbFetchAll("
    SELECT
        COALESCE(NULLIF(location, ''), 'Unknown') AS city,
        COUNT(*) AS donation_count
    FROM donations
    GROUP BY city
    ORDER BY donation_count DESC
");

$monthLabels = [];
$monthCounts = [];

// מפריד את נתוני החודשים לשמות וערכים ש-Chart.js יכול להשתמש בהם.
foreach ($monthlyRows as $row) {
    $monthLabels[] = $row['donation_month'];
    $monthCounts[] = (int)$row['donation_count'];
}

$cityLabels = [];
$cityCounts = [];

// מפריד את נתוני הערים לשמות וערכים ש-Chart.js יכול להשתמש בהם.
foreach ($cityRows as $row) {
    $cityLabels[] = $row['city'];
    $cityCounts[] = (int)$row['donation_count'];
}

include 'header.php';
?>

<div class="container mt-5">

    <h2 class="mb-4">Admin Dashboard</h2>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm p-4 bg-primary text-white">
                <h5>Total donations</h5>
                <h2><?= (int)$totalDonations['count'] ?></h2>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm p-4 bg-info text-dark">
                <h5>Available donations</h5>
                <h2><?= (int)$activeDonations['count'] ?></h2>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm p-4 bg-warning text-dark">
                <h5>Pending reservations</h5>
                <h2><?= (int)$pendingReservations['count'] ?></h2>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm p-4">
                <h5 class="mb-3">Monthly Donations</h5>
                <canvas id="monthlyDonationsChart" height="260"></canvas>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm p-4">
                <h5 class="mb-3">Donations by City</h5>
                <canvas id="cityDonationsChart" height="260"></canvas>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
var monthLabels = <?= json_encode($monthLabels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
var monthCounts = <?= json_encode($monthCounts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
var cityLabels = <?= json_encode($cityLabels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
var cityCounts = <?= json_encode($cityCounts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

// יוצר גרף עמודות של התרומות לפי חודשים.
new Chart(document.getElementById('monthlyDonationsChart'), {
    type: 'bar',
    data: {
        labels: monthLabels,
        datasets: [{
            label: 'Donations',
            data: monthCounts,
            backgroundColor: '#198754'
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            x: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Donation quantity'
                }
            },
            y: {
                title: {
                    display: true,
                    text: 'Month'
                }
            }
        }
    }
});

// יוצר גרף עוגה של התרומות לפי ערים.
new Chart(document.getElementById('cityDonationsChart'), {
    type: 'doughnut',
    data: {
        labels: cityLabels,
        datasets: [{
            data: cityCounts,
            backgroundColor: [
                '#198754',
                '#0d6efd',
                '#ffc107',
                '#dc3545',
                '#20c997',
                '#6f42c1',
                '#fd7e14',
                '#6c757d'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            tooltip: {
                callbacks: {
                    // מציג בכל tooltip את מספר התרומות ואת האחוז שלהן.
                    label: function (context) {
                        // מחבר את כל ערכי הגרף כדי לחשב את האחוז.
                        var total = context.dataset.data.reduce(function (sum, value) {
                            return sum + value;
                        }, 0);
                        var value = context.parsed;
                        var percent = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                        return context.label + ': ' + value + ' (' + percent + '%)';
                    }
                }
            }
        }
    }
});
</script>

<?php include 'footer.php'; ?>
