<?php
// פונקציות משותפות: מעבר דפים, אבטחה, התראות ואימייל.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_connection.php';

require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';
require_once 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


// מעבר לדף אחר.
function redirect($page)
{
    header('Location: ' . $page);
    exit;
}


// יצירת CSRF Token.
function ensureCsrfToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}


// יצירת שדה CSRF מוסתר בטופס.
function csrfInputField()
{
    $token = ensureCsrfToken();

    return '<input type="hidden" name="csrf_token" value="' .
        $token .
        '">';
}


// בדיקת CSRF Token.
function verifyCsrfToken($token)
{
    if (empty($_SESSION['csrf_token']) || $token == '') {
        return false;
    }

    return is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}





// קבלת ID של מנהל המערכת.
function getAdminId()
{
    $admin = dbFetchOne("
        SELECT user_id
        FROM users
        WHERE role = 'Admin'
        ORDER BY user_id ASC
        LIMIT 1
    ");

    if (!$admin) {
        return null;
    }

    return (int)$admin['user_id'];
}


// קבלת האימייל של מנהל המערכת.
function getAdminEmail()
{
    $admin = dbFetchOne("
        SELECT email
        FROM users
        WHERE role = 'Admin'
        ORDER BY user_id ASC
        LIMIT 1
    ");

    if (!$admin) {
        return null;
    }

    $email = $admin['email'];

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $email;
    }

    return null;
}


// ספירת הודעות פתוחות למנהל.
function getPendingAdminMessageCount()
{
    $row = dbFetchOne("
        SELECT COUNT(*) AS pending_count
        FROM contact_conversations
        WHERE status = 'OPEN'
    ");

    if (!$row) {
        return 0;
    }

    return (int)$row['pending_count'];
}


// קריאת הגדרות האימייל.
function getMailConfig()
{
    $file = 'mail_config.php';

    if (!file_exists($file)) {
        return null;
    }

    $config = require $file;

    if (!is_array($config)) {
        return null;
    }

    return $config;
}


// שליחת אימייל.
function sendEmailNotification($to, $subject, $body)
{
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $config = getMailConfig();

    if ($config == null) {
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();

        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = $config['encryption'];
        $mail->Port = (int)$config['port'];
        $mail->CharSet = 'UTF-8';

        $mail->setFrom(
            $config['username'],
            $config['from_name']
        );

        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->isHTML(false);

        $mail->send();

        return true;

    } catch (Exception $e) {
        return false;
    }
}


// שליחת התראה למנהל.
function notifyAdmin(
    $message,
    $subject = 'ShareBite Request Notification'
) {
   

    $adminId = getAdminId();

    if ($adminId == null) {
        return false;
    }

    $saved = dbExecute(
        "INSERT INTO notification (user_id, message)
         VALUES (?, ?)",
        [$adminId, $message]
    );

    if (!$saved) {
        return false;
    }

    $adminEmail = getAdminEmail();

    if ($adminEmail != null) {
        sendEmailNotification(
            $adminEmail,
            $subject,
            $message
        );
    }

    return true;
}


// שליחת התראה למשתמש.
function notifyUser(
    $userId,
    $message,
    $subject = 'ShareBite Notification'
) {

    if ($userId <= 0) {
        return false;
    }

    $saved = dbExecute(
        "INSERT INTO notification (user_id, message)
         VALUES (?, ?)",
        [$userId, $message]
    );

    if (!$saved) {
        return false;
    }

    $user = dbFetchOne(
        "SELECT email
         FROM users
         WHERE user_id = ?
         LIMIT 1",
        [$userId]
    );

    if ($user) {
        $email = $user['email'];

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendEmailNotification(
                $email,
                $subject,
                $message
            );
        }
    }

    return true;
}


function ensureVolunteerProfileFields()
{
    static $checked = false;

    if ($checked) {
        return;
    }

    $checked = true;

    $columns = dbFetchAll("
        SELECT COLUMN_NAME
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'users'
        AND COLUMN_NAME IN ('phone', 'address')
    ");

    $existing = [];

    foreach ($columns as $column) {
        $existing[] = $column['COLUMN_NAME'];
    }

    if (!in_array('phone', $existing)) {
        dbExecute("
            ALTER TABLE users
            ADD COLUMN phone VARCHAR(50) NULL
        ");
    }

    if (!in_array('address', $existing)) {
        dbExecute("
            ALTER TABLE users
            ADD COLUMN address VARCHAR(255) NULL
        ");
    }
}
