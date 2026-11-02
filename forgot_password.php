<?php
// סיכום הדף:
// הדף מתחיל תהליך איפוס סיסמה באמצעות שליחת קוד אימות לאימייל.
// הקוד נשמר בצורה מוצפנת ולזמן מוגבל.

require_once 'functions.php';
require_once 'password_reset_throttle.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$message = '';
$messageType = 'info';




// טיפול בבקשת איפוס סיסמה.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($token)) {
        $message = 'Invalid request.';
        $messageType = 'danger';
    } else {
        $email = trim($_POST['email'] ?? '');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Enter a valid email address.';
            $messageType = 'warning';
        } else {

            // Apply the same generic result to known/unknown or rate-limited emails.
            $clientIp = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
            if (sharebitePasswordResetThrottle($email, $clientIp, 'record')) {
                // חיפוש המשתמש לפי האימייל.
                $stmt = $pdo->prepare(
                    'SELECT user_id, full_name FROM users WHERE email = ? LIMIT 1'
                );
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user) {

                    // ביטול קודים ישנים שעדיין לא נוצלו.
                    $pdo->prepare(
                        'UPDATE password_resets
                         SET used_at = NOW()
                         WHERE user_id = ?
                         AND used_at IS NULL'
                    )->execute([$user['user_id']]);

                    // יצירת קוד חדש בן 6 ספרות.
                    $code = (string) random_int(100000, 999999);

                    // שמירת הקוד בצורה מוצפנת.
                    $codeHash = password_hash(
                        $code,
                        PASSWORD_DEFAULT
                    );

                    // הקוד תקף למשך 15 דקות.
                    $expiresAt = (new DateTime('+15 minutes'))
                        ->format('Y-m-d H:i:s');

                    $insert = $pdo->prepare('
                        INSERT INTO password_resets
                        (user_id, email, code_hash, expires_at)
                        VALUES (?, ?, ?, ?)
                    ');

                    $insert->execute([
                        $user['user_id'],
                        $email,
                        $codeHash,
                        $expiresAt
                    ]);

                    // שליחת קוד האימות למשתמש.
                    $body =
                        "Hello {$user['full_name']},\n\n" .
                        "Your ShareBite password reset verification code is: {$code}\n" .
                        "This code expires in 15 minutes.\n\n" .
                        "If you did not request this, you can ignore this email.";

                    sendEmailNotification(
                        $email,
                        'ShareBite password reset code',
                        $body
                    );
                }

            } // End allowed reset requests; rate-limited requests send no email.

            // שמירת האימייל ב-Session עבור דף reset_password.php.
            $_SESSION['reset_email'] = $email;

            header('Location: reset_password.php?sent=1');
            exit;
        }
    }
}

include 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm p-4">

            <h2 class="mb-3">Forgot Password</h2>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo escapeHtml($message); ?>
                </div>
            <?php endif; ?>

            <form method="post" novalidate>
                <?php echo csrfInputField(); ?>

                <div class="mb-3">
                    <label class="form-label">Email address</label>

                    <input
                        type="email"
                        name="email"
                        class="form-control"
                        value="<?php echo escapeHtml($_POST['email'] ?? ''); ?>"
                        required
                    >
                </div>

                <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                    <button type="submit" class="btn btn-primary">
                        Send verification code
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
