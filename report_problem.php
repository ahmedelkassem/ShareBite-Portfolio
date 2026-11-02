<?php
require_once 'functions.php';

checkLogin();


$allowedIssues = [
    'The food did not match the description',
    'The donor was unavailable',
    'There was a problem with the quantity',
    'Food safety or allergen concern',
    'The food was expired or spoiled',
    'Pickup location was incorrect',
    'Other'
];

$message = '';
$messageType = 'danger';

$donationId = (int)($_GET['donation_id'] ?? 0);
$selectedIssue = '';
$details = '';

// טיפול בטופס הדיווח.
if (
    $_SERVER['REQUEST_METHOD'] == 'POST' &&
    isset($_POST['report'])
) {

    $userId = (int)$_SESSION['user_id'];
    $donationId = (int)($_POST['donation_id'] ?? 0);
    $selectedIssue = trim($_POST['issue'] ?? '');
    $details = trim($_POST['details'] ?? '');


    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {

        $message =
            'Invalid request. Please refresh the page and try again.';

    } elseif ($donationId <= 0) {

        $message = 'Invalid donation.';

    } elseif (!in_array($selectedIssue, $allowedIssues)) {

        $message =
            'Please select a valid problem type.';

    } elseif (strlen($details) > 2000) {

        $message =
            'Additional details are too long.';

    } else {

        // בדיקה שהתרומה קיימת.
        $donation = dbFetchOne("
            SELECT donation_id, food_name
            FROM donations
            WHERE donation_id = ?
            LIMIT 1
        ", [$donationId]);


        if (!$donation) {

            $message = 'Donation not found.';

        } else {

            // The existing problem_reports table stores one message field.
            // Preserve the selected issue and any additional information.
            $reportText = $selectedIssue;
            if ($details !== '') {
                $reportText .= "\n\nDetails: " . $details;
            }

            $saved = dbExecute("
                INSERT INTO problem_reports
                (UserId, OfferId, Message)
                VALUES (?, ?, ?)
            ", [
                $userId,
                $donationId,
                $reportText
            ]);


            if ($saved) {

                $adminMessage =
                    'Problem report from user #' .
                    $userId .
                    ' about donation #' .
                    $donationId .
                    ' (' .
                    $donation['food_name'] .
                    '): ' .
                    $selectedIssue;


                if ($details != '') {
                    $adminMessage .=
                        '. Details: ' .
                        $details;
                }


                // שליחת התראה למנהל.
                notifyAdmin(
                    $adminMessage,
                    'New Problem Report Received'
                );

                $message =
                    'Your report has been received. The admin team will review it soon.';

                $messageType = 'success';

                $selectedIssue = '';
                $details = '';

            } else {

                $message =
                    'Unable to submit the report. Please try again.';
            }
        }
    }
}

include 'header.php';
?>

<div class="row justify-content-center mt-5">

    <div class="col-md-7">

        <form
            method="post"
            class="border p-4 shadow bg-white rounded"
        >

            <h3 class="text-danger mb-3">
                Report a Problem
            </h3>

            <p class="text-muted">
                Report problems related to food quality,
                allergens, quantity, pickup or donor availability.
            </p>

            <?php if ($message != ''): ?>

                <div class="alert alert-<?= $messageType ?>">
                    <?= escapeHtml($message) ?>
                </div>

            <?php endif; ?>


            <?= csrfInputField() ?>

            <input
                type="hidden"
                name="donation_id"
                value="<?= $donationId ?>"
            >

            <?php if ($donationId <= 0): ?>

                <div class="alert alert-warning">
                    No valid donation was selected.
                </div>

            <?php endif; ?>

            <div class="mb-3">

                <label class="form-label">
                    What problem did you encounter?
                </label>

                <select
                    name="issue"
                    class="form-select"
                    required
                >

                    <option value="">
                        Select a problem
                    </option>


                    <?php foreach ($allowedIssues as $issue): ?>

                        <option
                            value="<?= $issue ?>"
                            <?= $selectedIssue == $issue ? 'selected' : '' ?>
                        >
                            <?= $issue ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="mb-3">

                <label class="form-label">
                    Additional details
                </label>

                <textarea
                    name="details"
                    class="form-control"
                    rows="5"
                    maxlength="2000"
                    placeholder="Tell us what happened in detail..."
                ><?= escapeHtml($details) ?></textarea>

                <div class="form-text">
                    Maximum 2000 characters.
                </div>

            </div>

            <button
                type="submit"
                name="report"
                class="btn btn-danger w-100"
                <?= $donationId <= 0 ? 'disabled' : '' ?>
            >
                Submit Report
            </button>

            <a
                href="history.php"
                class="btn btn-link w-100 mt-2 text-decoration-none"
            >
                Cancel
            </a>

        </form>

    </div>

</div>

<?php include 'footer.php'; ?>
