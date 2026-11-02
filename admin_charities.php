<?php
// סיכום הדף:
// הדף מאפשר ל-Admin לבדוק מרכזי Charity ולשנות את הסטטוס שלהם.
// הוא משתמש ב-CSRF Protection ומציג הודעה לאחר העדכון.

require_once 'functions.php';
checkLogin();

if ($_SESSION['role'] !== 'Admin') {
    redirect('index.php');
}

$message = '';

// טיפול בשינוי סטטוס של מרכז Charity.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request.';
    } else {
        $centerId = (int)($_POST['center_id'] ?? 0);
        $action = $_POST['action'] ?? '';

        // ניתן לשנות רק ל-Active או Inactive.
        if (
            $centerId > 0 &&
            in_array($action, ['Active', 'Inactive'])
        ) {
            dbExecute(
                "UPDATE charity_centers
                 SET status = ?
                 WHERE center_id = ?",
                [$action, $centerId]
            );

            $message =
                'Charity center status updated successfully.';
        }
    }
}

// קבלת כל מרכזי ה-Charity עם פרטי המשתמש שלהם.
$centers = dbFetchAll("
    SELECT 
        c.*,
        u.full_name,
        u.email
    FROM charity_centers c
    JOIN users u
        ON c.user_id = u.user_id
    ORDER BY c.created_at DESC
");

include 'header.php';
?>

<h2 class="mb-4">Manage Charity Centers</h2>

<?php if ($message): ?>
    <div class="alert alert-success">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<?php if (count($centers) === 0): ?>

    <div class="alert alert-info">
        No charity centers registered yet.
    </div>

<?php else: ?>

    <div class="table-responsive">

        <table class="table table-bordered align-middle">

            <thead>
                <tr>
                    <th>Center</th>
                    <th>Account Owner</th>
                    <th>Location</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($centers as $center): ?>

                    <tr>

                        <td>
                            <strong>
                                <?php echo escapeHtml($center['center_name']); ?>
                            </strong>
                            <br>

                            <small class="text-muted">
                                <?php echo nl2br(escapeHtml($center['description'] ?? '')); ?>
                            </small>
                        </td>

                        <td>
                            <?php echo escapeHtml($center['full_name']); ?>
                            <br>

                            <small class="text-muted">
                                <?php echo escapeHtml($center['email']); ?>
                            </small>
                        </td>

                        <td>
                            <?php echo escapeHtml($center['city']); ?>
                            <br>

                            <small>
                                <?php echo escapeHtml($center['address']); ?>
                            </small>
                        </td>

                        <td>
                            <?php echo escapeHtml($center['phone']); ?>
                        </td>

                        <td>
                            <span class="badge bg-<?php echo $center['status'] === 'Active' ? 'success' : 'secondary'; ?>">
                                <?php echo escapeHtml($center['status']); ?>
                            </span>
                        </td>

                        <td>
                            <?php echo date(
                                'M d, Y',
                                strtotime($center['created_at'])
                            ); ?>
                        </td>

                        <td>

                            <?php if ($center['status'] === 'Active'): ?>

                                <form method="POST">
                                    <?php echo csrfInputField(); ?>

                                    <input
                                        type="hidden"
                                        name="center_id"
                                        value="<?php echo (int)$center['center_id']; ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="Inactive"
                                    >

                                    <button class="btn btn-warning btn-sm">
                                        Deactivate
                                    </button>
                                </form>

                            <?php else: ?>

                                <form method="POST">
                                    <?php echo csrfInputField(); ?>

                                    <input
                                        type="hidden"
                                        name="center_id"
                                        value="<?php echo (int)$center['center_id']; ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="Active"
                                    >

                                    <button class="btn btn-success btn-sm">
                                        Activate
                                    </button>
                                </form>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

<?php endif; ?>

<?php include 'footer.php'; ?>
