<?php
// סיכום הקובץ:
// הקובץ פותח Session ומתחבר למסד הנתונים ShareBite.
// בנוסף הוא מכיל פונקציות משותפות לעבודה עם PDO והמשתמש המחובר.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// פרטי החיבור למסד הנתונים.
$host = getenv('DB_HOST') ?: 'localhost';
$db = getenv('DB_NAME') ?: 'sharebite';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD');
$password = ($password === false) ? '' : $password;
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
];


// יצירת החיבור למסד הנתונים.
try {
    $pdo = new PDO(
        $dsn,
        $username,
        $password,
        $options
    );
} catch (PDOException $e) {
    error_log(
        'Database connection failed: ' .
        $e->getMessage()
    );

    exit('Database connection failed.');
}


// Refresh account permissions against the database on every request.
// A deleted or deactivated account must not keep a previously issued role.
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/session_authorization.php';
    $accountId = filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);
    $account = null;

    if ($accountId !== false) {
        $accountStmt = $pdo->prepare(
            'SELECT user_id, role, full_name, email FROM users WHERE user_id = ? LIMIT 1'
        );
        $accountStmt->execute([$accountId]);
        $account = $accountStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    $principal = sharebiteCurrentAccount($_SESSION['user_id'], $account);
    if ($principal === null) {
        unset(
            $_SESSION['user_id'],
            $_SESSION['role'],
            $_SESSION['full_name'],
            $_SESSION['email']
        );
        session_regenerate_id(true);
    } else {
        if (($_SESSION['role'] ?? '') !== $principal['role']) {
            // Session identifiers should change when privileges change.
            session_regenerate_id(true);
        }
        foreach ($principal as $key => $value) {
            $_SESSION[$key] = $value;
        }
    }
}

// חלק מהדפים הישנים משתמשים ב-$conn.
$conn = $pdo;


// הגנת טקסט לפני הצגתו ב-HTML.
function cleanInput($data)
{
    return trim((string)$data);
}


// Escape untrusted text before inserting it into HTML content or quoted attributes.
function escapeHtml($value)
{
    return htmlspecialchars(trim((string)$value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}


// ניקוי פשוט של קלט.
function sanitize($data)
{
    return trim((string)$data);
}


// בדיקה אם המשתמש מחובר.
function isLoggedIn()
{
    return !empty($_SESSION['user_id']);
}


// אם המשתמש לא מחובר, מעבירים אותו ל-Login.
function checkLogin()
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}


// בדיקת Role של המשתמש.
function checkRole($role)
{
    if (
        !isLoggedIn() ||
        ($_SESSION['role'] ?? '') != $role
    ) {
        header('Location: index.php');
        exit;
    }
}


// ביצוע שאילתה עם Prepared Statement.
function dbQuery($sql, $params = [])
{
    global $pdo;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt;
}


// קבלת שורה אחת מהשאילתה.
function dbFetchOne($sql, $params = [])
{
    $stmt = dbQuery($sql, $params);
    $result = $stmt->fetch();

    if ($result) {
        return $result;
    }

    return null;
}


// קבלת כל השורות מהשאילתה.
function dbFetchAll($sql, $params = [])
{
    $stmt = dbQuery($sql, $params);

    return $stmt->fetchAll();
}


// ביצוע INSERT, UPDATE או DELETE.
function dbExecute($sql, $params = [])
{
    global $pdo;

    try {
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);

    } catch (PDOException $e) {
        error_log(
            'Database execute failed: ' .
            $e->getMessage()
        );

        return false;
    }
}


?>
