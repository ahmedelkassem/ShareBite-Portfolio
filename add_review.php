<?php
require_once 'functions.php';
checkRole('Volunteer');



$userId = (int)$_SESSION['user_id'];
$donationId = (int)($_GET['donation_id'] ?? $_POST['donation_id'] ?? 0);

$message = '';
$rating = (int)($_POST['rating'] ?? 5);
$comment = trim($_POST['comment'] ?? '');

if ($donationId <= 0) {
    redirect('history.php');
}

// בדיקה שהתרומה שייכת להזמנה של ה-Volunteer.
$reservation = dbFetchOne("
    SELECT
        r.reservation_id,
        r.status,
        r.delivery_status,
        d.donation_id,
        d.food_name
    FROM reservations r
    JOIN donations d
        ON r.donation_id = d.donation_id
    WHERE r.user_id = ?
    AND r.donation_id = ?
    LIMIT 1
", [
    $userId,
    $donationId
]);

// ניתן להוסיף Review רק לאחר סיום המשלוח.
$canReview =
    $reservation &&
    (
        ($reservation['status'] ?? '') == 'Completed' ||
        ($reservation['delivery_status'] ?? '') == 'Received'
    );


if (!$canReview) {
    redirect('history.php');
}

// בדיקה אם כבר קיים Review.
$existingReview = dbFetchOne("
    SELECT review_id
    FROM review
    WHERE user_id = ?
    AND donation_id = ?
    LIMIT 1
", [
    $userId,
    $donationId
]);

if ($existingReview) {
    redirect('history.php');
}

// שמירת ה-Review.
if (
    $_SERVER['REQUEST_METHOD'] == 'POST' &&
    isset($_POST['submit_review'])
) {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {

        $message =
            'Invalid request. Please refresh the page and try again.';

    } elseif (
        $rating < 1 ||
        $rating > 5 ||
        $comment == ''
    ) {

        $message =
            'Please choose a valid rating and write a comment.';

    } elseif (strlen($comment) > 2000) {

        $message =
            'Your comment is too long.';

    } else {

        $saved = dbExecute("
            INSERT INTO review
            (user_id, donation_id, rating, comment)
            VALUES (?, ?, ?, ?)
        ", [
            $userId,
            $donationId,
            $rating,
            $comment
        ]);


        if ($saved) {
            redirect('history.php?review=success');
        }

        $message =
            'Unable to submit your review. Please try again.';
    }
}

include 'header.php';
?>

<div class="row justify-content-center mt-5">

    <div class="col-md-6">

        <form
            method="post"
            class="border p-4 shadow bg-white rounded"
        >

            <h3 class="mb-2">
                Review the Experience
            </h3>

            <p class="text-muted">
                Donation:
                <strong>
                    <?= escapeHtml($reservation['food_name']) ?>
                </strong>
            </p>

            <?php if ($message != ''): ?>

                <div class="alert alert-danger">
                    <?= escapeHtml($message) ?>
                </div>

            <?php endif; ?>

            <?= csrfInputField() ?>

            <input
                type="hidden"
                name="donation_id"
                value="<?= $donationId ?>"
            >

            <div class="mb-3">

                <label class="form-label">
                    Rating
                </label>

                <select
                    name="rating"
                    class="form-select"
                    required
                >

                    <option value="5" <?= $rating == 5 ? 'selected' : '' ?>>
                        5 - Excellent
                    </option>

                    <option value="4" <?= $rating == 4 ? 'selected' : '' ?>>
                        4 - Very Good
                    </option>

                    <option value="3" <?= $rating == 3 ? 'selected' : '' ?>>
                        3 - Good
                    </option>

                    <option value="2" <?= $rating == 2 ? 'selected' : '' ?>>
                        2 - Fair
                    </option>

                    <option value="1" <?= $rating == 1 ? 'selected' : '' ?>>
                        1 - Poor
                    </option>

                </select>

            </div>

            <div class="mb-3">

                <label class="form-label">
                    Your comment
                </label>

                <textarea
                    name="comment"
                    class="form-control"
                    rows="4"
                    maxlength="2000"
                    placeholder="Share your feedback..."
                    required
                ><?= escapeHtml($comment) ?></textarea>

            </div>

            <button
                type="submit"
                name="submit_review"
                class="btn btn-success w-100"
            >
                Submit Review
            </button>

            <a
                href="history.php"
                class="btn btn-link w-100 mt-2 text-decoration-none"
            >
                Back
            </a>

        </form>

    </div>

</div>

<?php include 'footer.php'; ?>
