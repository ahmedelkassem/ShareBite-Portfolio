<?php
require_once 'functions.php';

checkLogin();

$message = '';
$messageType = 'info';
$content = '';

// טיפול בשליחת הצעה חדשה.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // בדיקת CSRF Token כדי להגן על הטופס מבקשות מזויפות.
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request. Please refresh the page and try again.';
        $messageType = 'danger';
    } else {
        $content = trim($_POST['content'] ?? '');

        // בדיקה שההצעה אינה ריקה ושהאורך שלה תקין.
        if ($content === '') {
            $message = 'Please enter your suggestion.';
            $messageType = 'warning';
        } elseif (strlen($content) < 5) {
            $message = 'Please write a more detailed suggestion.';
            $messageType = 'warning';
        } elseif (strlen($content) > 2000) {
            $message = 'Suggestion must be 2000 characters or less.';
            $messageType = 'warning';
        } else {
            $userId = (int)($_SESSION['user_id'] ?? 0);
            $userName = trim($_SESSION['full_name'] ?? 'User');

            // שמירת ההצעה בטבלת suggestions בסטטוס New.
            $saved = dbExecute(
                "INSERT INTO suggestions (user_id, content, status)\n                 VALUES (?, ?, 'New')",
                [
                    $userId > 0 ? $userId : null,
                    $content
                ]
            );

            if ($saved) {
                // יצירת הודעה קצרה ל-Admin לאחר שמירת ההצעה בהצלחה.
                $adminMessage =
                    'New suggestion from ' . $userName .
                    ' (User #' . $userId . '): ' .
                    mb_substr($content, 0, 500);

                // שמירת התראה ל-Admin וניסיון לשלוח לו אימייל.
                // כשל בשליחת האימייל לא מוחק את ההצעה כי היא כבר נשמרה במסד הנתונים.
                notifyAdmin(
                    $adminMessage,
                    'New ShareBite Suggestion'
                );

                $message = 'Thank you — your suggestion was submitted successfully.';
                $messageType = 'success';
                $content = '';
            } else {
                $message = 'Unable to submit your suggestion. Please try again.';
                $messageType = 'danger';
            }
        }
    }
}

include 'header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card p-4 shadow-sm">
                <h2 class="mb-2">Submit a Suggestion</h2>

                <p class="text-muted">
                    Share an idea that could improve ShareBite,
                    food safety, pickup coordination or the user experience.
                </p>

                <?php if ($message !== ''): ?>
                    <div class="alert alert-<?= escapeHtml($messageType) ?>">
                        <?= escapeHtml($message) ?>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <?= csrfInputField() ?>

                    <div class="mb-3">
                        <label class="form-label">Your suggestion</label>

                        <textarea
                            name="content"
                            class="form-control"
                            rows="7"
                            maxlength="2000"
                            placeholder="Write your suggestion in detail..."
                            required
                        ><?= escapeHtml($content) ?></textarea>

                        <div class="form-text">
                            Minimum 5 characters, maximum 2000 characters.
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Send Suggestion
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
