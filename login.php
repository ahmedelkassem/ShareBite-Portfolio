<?php
// סיכום הדף:
// הדף מאפשר למשתמש להתחבר באמצעות אימייל וסיסמה.
// הוא בודק את ה-CSRF Token ואת פרטי החשבון לפני יצירת ה-Session.

require_once 'functions.php';
require_once 'login_throttle.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$message = '';

// מתחילים ניסיון התחברות כאשר הטופס נשלח.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        $message = 'Invalid request.';
    } else {
        $email = is_string($_POST['email'] ?? null)
            ? strtolower(trim($_POST['email'])) : '';
        $password = is_string($_POST['password'] ?? null)
            ? $_POST['password'] : '';
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        if ($email === '' || $password === '') {
            $message = 'Please enter your email and password.';
        } elseif (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Email or password is invalid.';
        } elseif (!sharebiteLoginThrottle($email, $clientIp, 'check')) {
            http_response_code(429);
            $message = 'Too many login attempts. Please try again in 15 minutes.';
        } else {
            // קבלת המשתמש לפי האימייל ובדיקת הסיסמה המוצפנת.
            $stmt = $pdo->prepare('SELECT user_id, full_name, email, password, role FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                sharebiteLoginThrottle($email, $clientIp, 'success');
                session_regenerate_id(true); // הגנה נוספת על ה-Session

                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                header('Location: index.php');
                exit;
            }

            if (!sharebiteLoginThrottle($email, $clientIp, 'failure')) {
                error_log('Unable to record ShareBite login failure.');
            }
            $message = 'Email or password is invalid.';
        }
    }
}

include 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm p-4">
            <h2 class="mb-3">Login to ShareBite</h2>

            <?php if (isset($_GET['registered'])): ?>
                <div class="alert alert-success">Registration successful. Please login.</div>
            <?php endif; ?>

            <?php if (isset($_GET['password_reset'])): ?>
                <div class="alert alert-success">Password changed successfully. Please login.</div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="alert alert-danger"><?php echo $message; ?></div>
            <?php endif; ?>

            <form method="post" novalidate>
                <?php echo csrfInputField(); ?>

                <div class="mb-3">
                    <label class="form-label">Email address</label>
                    <input type="email" name="email" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                    <button type="submit" class="btn btn-primary">Login</button>
                    <a href="forgot_password.php" class="text-decoration-none">Forgot password?</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>