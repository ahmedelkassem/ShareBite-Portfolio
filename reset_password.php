<?php
// סיכום הדף:
// הדף משלים את תהליך איפוס הסיסמה באמצעות אימייל וקוד אימות.
// הוא בודק שהקוד תקין ולא פג תוקף ואז שומר סיסמה חדשה בצורה מוצפנת.

require_once 'functions.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$message = '';
$messageType = 'info';
$prefillEmail = $_SESSION['reset_email'] ?? '';

if (isset($_GET['sent'])) {
    $message = 'If this email exists in ShareBite, a verification code was sent.';
    $messageType = 'success';
}

// טיפול בטופס איפוס הסיסמה.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request.';
        $messageType = 'danger';

    } else {
        $email = trim($_POST['email'] ?? '');
        $code = preg_replace('/\D/', '', $_POST['code'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');

        $prefillEmail = $email;

        if (
            $email === '' ||
            $code === '' ||
            $password === '' ||
            $confirmPassword === ''
        ) {
            $message = 'Please fill in all fields.';
            $messageType = 'warning';

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Enter a valid email address.';
            $messageType = 'warning';

        } elseif ($password !== $confirmPassword) {
            $message = 'Passwords do not match.';
            $messageType = 'warning';

        } else {

            // קבלת קוד האימות האחרון שלא נוצל עבור האימייל.
            $stmt = $pdo->prepare("
                SELECT reset_id, user_id, code_hash, expires_at, attempts
                FROM password_resets
                WHERE email = ?
                AND used_at IS NULL
                ORDER BY created_at DESC
                LIMIT 1
            ");

            $stmt->execute([$email]);
            $reset = $stmt->fetch();

            // בדיקה שהקוד קיים ועדיין בתוקף.
            if (
                !$reset ||
                strtotime($reset['expires_at']) < time()
            ) {
                $message = 'Verification code is invalid or expired.';
                $messageType = 'danger';

            } elseif ((int)$reset['attempts'] >= 5) {
                $message = 'Too many attempts. Please request a new code.';
                $messageType = 'danger';

            } elseif (!password_verify($code, $reset['code_hash'])) {

                // הגדלת מספר הניסיונות אם הקוד שגוי.
                $pdo->prepare("
                    UPDATE password_resets
                    SET attempts = attempts + 1
                    WHERE reset_id = ?
                ")->execute([$reset['reset_id']]);

                $message = 'Verification code is invalid or expired.';
                $messageType = 'danger';

            } else {

                // הצפנת הסיסמה החדשה.
                $hash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $pdo->beginTransaction();

                // שמירת הסיסמה החדשה.
                $pdo->prepare("
                    UPDATE users
                    SET password = ?
                    WHERE user_id = ?
                ")->execute([
                    $hash,
                    $reset['user_id']
                ]);

                // סימון קוד האימות כקוד שכבר נוצל.
                $pdo->prepare("
                    UPDATE password_resets
                    SET used_at = NOW()
                    WHERE reset_id = ?
                ")->execute([
                    $reset['reset_id']
                ]);

                $pdo->commit();

                unset($_SESSION['reset_email']);

                header('Location: login.php?password_reset=1');
                exit;
            }
        }
    }
}

include 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm p-4">

            <h2 class="mb-3">Reset Password</h2>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <?php echo csrfInputField(); ?>

                <input
                    type="email"
                    name="email"
                    class="form-control mb-3"
                    placeholder="Email address"
                    value="<?php echo escapeHtml($prefillEmail); ?>"
                    required
                >

                <input
                    type="text"
                    name="code"
                    class="form-control mb-3"
                    placeholder="Verification code"
                    maxlength="6"
                    required
                >

                <input
                    type="password"
                    name="password"
                    class="form-control mb-3"
                    placeholder="New password"
                    required
                >

                <input
                    type="password"
                    name="confirm_password"
                    class="form-control mb-3"
                    placeholder="Confirm new password"
                    required
                >

                <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                    <button type="submit" class="btn btn-primary">
                        Change password
                    </button>

                    <a
                        href="forgot_password.php"
                        class="text-decoration-none"
                    >
                        Send a new code
                    </a>
                </div>
            </form>

        </div>
    </div>
</div>

<?php include 'footer.php'; ?>