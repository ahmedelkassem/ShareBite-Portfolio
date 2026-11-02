<?php
require_once 'functions.php';
checkLogin();

// קבלת התרומות השמורות של המשתמש המחובר.
$result = dbFetchAll("
    SELECT
        d.*,
        u.full_name AS donor_name,
        s.saved_offer_id
    FROM saved_offers s
    JOIN donations d
        ON s.donation_id = d.donation_id
    JOIN users u
        ON d.user_id = u.user_id
    WHERE s.user_id = ?
    ORDER BY s.created_at DESC
", [
    $_SESSION['user_id']
]);

include 'header.php';
?>

<div class="container mt-5">

    <h2 class="text-center">
        My Favorites
    </h2>

    <p class="text-center text-muted">
        Saved donations appear here for you to claim later.
    </p>

    <?php if (count($result) > 0): ?>

        <div class="row g-4">

            <?php foreach ($result as $offer): ?>

                <div class="col-md-6">

                    <div class="card shadow-sm h-100">

                        <div class="card-body">

                            <h5 class="card-title">
                                <?php echo escapeHtml($offer['food_name']); ?>
                            </h5>

                            <p class="mb-1">
                                <strong>Donor:</strong>
                                <?php echo escapeHtml($offer['donor_name']); ?>
                            </p>

                            <p class="mb-1">
                                <strong>Quantity:</strong>
                                <?php echo escapeHtml($offer['quantity']); ?>
                            </p>

                            <p class="mb-1">
                                <strong>Location:</strong>
                                <?php echo escapeHtml($offer['location']); ?>
                            </p>

                            <p class="mb-1">
                                <strong>Status:</strong>
                                <?php echo escapeHtml($offer['status']); ?>
                            </p>

                            <a
                                href="offer_details.php?donation_id=<?php echo $offer['donation_id']; ?>"
                                class="btn btn-outline-success w-100 mb-2"
                            >
                                View Details
                            </a>

                            <!-- הסרת התרומה מהמועדפים. -->
                            <form
                                method="post"
                                action="remove_saved_offer.php"
                            >

                                <?php echo csrfInputField(); ?>

                                <input
                                    type="hidden"
                                    name="donation_id"
                                    value="<?php echo $offer['donation_id']; ?>"
                                >

                                <input
                                    type="hidden"
                                    name="return"
                                    value="saved_offers.php"
                                >

                                <button
                                    type="submit"
                                    class="btn btn-danger w-100"
                                >
                                    Remove from Favorites
                                </button>

                            </form>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="alert alert-info text-center">
            You have no saved offers yet.
        </div>

    <?php endif; ?>

</div>

<?php include 'footer.php'; ?>
