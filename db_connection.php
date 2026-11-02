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
