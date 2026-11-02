<?php
// הדף מאפשר ל-Admin לראות הצעות של משתמשים ולעדכן את הסטטוס שלהן.

require_once 'functions.php';

checkRole('Admin');



$message = '';
$messageType = 'info';

$allowedStatuses = [
    'New',
    'In Review',
    'Resolved'
];


// טיפול בעדכון סטטוס.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {

        $message =
            'Invalid request. Please refresh the page and try again.';

        $messageType = 'danger';

    } else {

        $suggestionId =
            (int)($_POST['suggestion_id'] ?? 0);

        $newStatus =
            trim($_POST['status'] ?? '');


        if ($suggestionId <= 0) {

            $message = 'Invalid suggestion.';
            $messageType = 'danger';

        } elseif (!in_array($newStatus, $allowedStatuses)) {

            $message = 'Invalid suggestion status.';
            $messageType = 'danger';

        } else {

            $updated = dbExecute("
                UPDATE suggestions
                SET status = ?, updated_at = NOW()
                WHERE suggestion_id = ?
            ", [
                $newStatus,
                $suggestionId
            ]);


            if ($updated) {
                redirect('admin_suggestions.php?updated=1');
            }

            $message =
                'Unable to update the suggestion.';

            $messageType = 'danger';
        }
    }
}


// הודעת הצלחה.
if (isset($_GET['updated'])) {
    $message =
        'Suggestion status was updated successfully.';

    $messageType = 'success';
}


// ספירת ההצעות.
$counts = dbFetchOne("
    SELECT
        COUNT(*) AS total_count,
        SUM(status = 'New') AS new_count,
        SUM(status = 'In Review') AS review_count,
        SUM(status = 'Resolved') AS resolved_count
    FROM suggestions
");


// קבלת כל ההצעות.
$suggestions = dbFetchAll("
    SELECT
        s.suggestion_id,
        s.user_id,
        s.content,
        s.status,
        s.created_at,
        s.updated_at,
        u.full_name,
        u.email
    FROM suggestions s
    LEFT JOIN users u
        ON s.user_id = u.user_id
    ORDER BY s.created_at DESC
");


// הצגת תאריך.
function showDate($value)
{
    $time = strtotime((string)$value);

    if ($time == false) {
        return 'Not specified';
    }

    return date('M d, Y H:i', $time);
}


// צבע ה-Badge לפי סטטוס.
function badgeClass($status)
{
    if ($status == 'New') {
        return 'danger';
    }

    if ($status == 'In Review') {
        return 'warning';
    }

    if ($status == 'Resolved') {
        return 'success';
    }

    return 'secondary';
}


include 'header.php';
?>

<div class="container mt-4">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

        <div>
            <h2 class="mb-1">
                Manage Suggestions
            </h2>

            <p class="text-muted mb-0">
                Review user suggestions and update their status.
            </p>
        </div>

        <a
            href="admin_dashboard.php"
            class="btn btn-outline-secondary"
        >
            Back to Admin Panel
        </a>

    </div>


    <?php if ($message != ''): ?>

        <div class="alert alert-<?= escapeHtml($messageType) ?>">
            <?= escapeHtml($message) ?>
        </div>

    <?php endif; ?>


    <div class="row g-3 mb-4">

        <div class="col-md-3">
            <div class="card shadow-sm p-3">
                <div class="text-muted">All Suggestions</div>
                <h3><?= (int)($counts['total_count'] ?? 0) ?></h3>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm p-3">
                <div class="text-muted">New</div>
                <h3><?= (int)($counts['new_count'] ?? 0) ?></h3>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm p-3">
                <div class="text-muted">In Review</div>
                <h3><?= (int)($counts['review_count'] ?? 0) ?></h3>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm p-3">
                <div class="text-muted">Resolved</div>
                <h3><?= (int)($counts['resolved_count'] ?? 0) ?></h3>
            </div>
        </div>

    </div>


    <?php if (count($suggestions) > 0): ?>

        <div class="row g-4">

            <?php foreach ($suggestions as $suggestion): ?>

                <div class="col-lg-6">

                    <div class="card shadow-sm h-100">

                        <div class="card-body d-flex flex-column">

                            <div class="d-flex justify-content-between mb-3">

                                <div>
                                    <h5>
                                        Suggestion #
                                        <?= (int)$suggestion['suggestion_id'] ?>
                                    </h5>

                                    <small class="text-muted">
                                        <?= showDate($suggestion['created_at']) ?>
                                    </small>
                                </div>

                                <span class="badge bg-<?= badgeClass($suggestion['status']) ?>">
                                    <?= escapeHtml($suggestion['status']) ?>
                                </span>

                            </div>


                            <div class="alert alert-light border">
                                <?= nl2br(cleanInput($suggestion['content'])) ?>
                            </div>


                            <p>
                                <strong>Submitted by:</strong>

                                <?php if (!empty($suggestion['full_name'])): ?>

                                    <?= escapeHtml($suggestion['full_name']) ?>

                                    <?php if (!empty($suggestion['email'])): ?>
                                        <span class="text-muted">
                                            (<?= escapeHtml($suggestion['email']) ?>)
                                        </span>
                                    <?php endif; ?>

                                <?php else: ?>

                                    <span class="text-muted">
                                        Unknown or deleted user
                                    </span>

                                <?php endif; ?>
                            </p>


                            <form method="post" class="mt-auto">

                                <?= csrfInputField() ?>

                                <input
                                    type="hidden"
                                    name="suggestion_id"
                                    value="<?= (int)$suggestion['suggestion_id'] ?>"
                                >

                                <label class="form-label">
                                    Change Status
                                </label>

                                <select
                                    name="status"
                                    class="form-select mb-2"
                                    required
                                >

                                    <?php foreach ($allowedStatuses as $status): ?>

                                        <option
                                            value="<?= escapeHtml($status) ?>"
                                            <?= $suggestion['status'] == $status ? 'selected' : '' ?>
                                        >
                                            <?= escapeHtml($status) ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                >
                                    Update
                                </button>

                            </form>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="alert alert-info">
            No suggestions have been submitted yet.
        </div>

    <?php endif; ?>

</div>

<?php include 'footer.php'; ?>
