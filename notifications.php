<?php
// הדף מציג את ההתראות של המשתמש ומסמן אותן כנקראו.

require_once 'functions.php';

checkLogin();

$userId = (int)$_SESSION['user_id'];


// קבלת ההתראות של המשתמש.
$notifications = dbFetchAll("
    SELECT Message, IsRead, CreatedAt
    FROM notification
    WHERE UserId = ?
    ORDER BY CreatedAt DESC
", [$userId]);


// סימון ההתראות כנקראו.
dbExecute("
    UPDATE notification
    SET IsRead = 1
    WHERE UserId = ?
    AND IsRead = 0
", [$userId]);


include 'header.php';
?>

<div class="container mt-5">

    <h3 class="mb-4">My Notifications</h3>

    <?php if (count($notifications) > 0): ?>

        <?php foreach ($notifications as $notification): ?>

            <?php
            $alertClass =
                $notification['IsRead'] == 0
                ? 'alert-info'
                : 'alert-light';
            ?>

            <div class="alert <?= $alertClass ?>">

                <?= escapeHtml($notification['Message']) ?>

                <br>

                <small class="text-muted">
                    <?= escapeHtml($notification['CreatedAt']) ?>
                </small>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <div class="alert alert-secondary">
            You have no notifications yet.
        </div>

    <?php endif; ?>

</div>

<?php include 'footer.php'; ?>
