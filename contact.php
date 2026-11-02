<?php
// סיכום הדף:
// הדף מאפשר למשתמש ליצור שיחת תמיכה ולראות את ההודעות שלו.

require_once 'functions.php';

checkLogin();

$msg = '';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    verifyCsrfToken($_POST['csrf_token'] ?? '')
) {
    $subject = sanitize($_POST['subject'] ?? '');
    $text = sanitize($_POST['message'] ?? '');

    if ($subject && $text) {
        $userName = $_SESSION['full_name'] ?? 'User';
        $userEmail = trim($_SESSION['email'] ?? '');

        // יצירת שיחה חדשה בטבלת contact_conversations.
        dbExecute(
            "INSERT INTO contact_conversations (user_id, name, email, subject)
             VALUES (?, ?, ?, ?)",
            [
                $_SESSION['user_id'],
                $userName,
                $userEmail,
                $subject
            ]
        );

        // קבלת ה-ID של השיחה החדשה.
        $cid = dbFetchOne(
            "SELECT LAST_INSERT_ID() AS id"
        )['id'];

        // שמירת ההודעה הראשונה של המשתמש בשיחה.
        dbExecute(
            "INSERT INTO contact_messages (conversation_id, sender_role, message)
             VALUES (?, 'USER', ?)",
            [$cid, $text]
        );

        // שליחת הודעה במייל ל-Admin.
        $adminEmail = getAdminEmail();

        if ($adminEmail !== null) {
            $adminBody =
                "You received a new contact message from {$userName}.\n\n" .
                "Subject: {$subject}\n\n" .
                "Message:\n{$text}";

            sendEmailNotification(
                $adminEmail,
                'New contact message from ShareBite',
                $adminBody
            );
        }

        // שליחת אישור במייל למשתמש.
        if (
            $userEmail !== '' &&
            filter_var($userEmail, FILTER_VALIDATE_EMAIL)
        ) {
            $userBody =
                "Hello {$userName},\n\n" .
                "We received your message and will get back to you soon.\n\n" .
                "Subject: {$subject}\n\n" .
                "Message:\n{$text}";

            sendEmailNotification(
                $userEmail,
                'We received your message',
                $userBody
            );
        }

        $msg = 'Message sent.';
    }
}

// קבלת כל שיחות התמיכה של המשתמש.
$convs = dbFetchAll("
    SELECT *
    FROM contact_conversations
    WHERE user_id = ?
    ORDER BY updated_at DESC
", [$_SESSION['user_id']]);

include 'header.php';
?>

<h2>Contact Admin</h2>

<?php if ($msg): ?>
    <div class="alert alert-success">
        <?php echo $msg; ?>
    </div>
<?php endif; ?>

<form method="post" class="card p-3 mb-4">
    <?php echo csrfInputField(); ?>

    <input
        name="subject"
        class="form-control mb-2"
        placeholder="Subject"
        required
    >

    <textarea
        name="message"
        class="form-control mb-2"
        rows="3"
        placeholder="Message"
        required
    ></textarea>

    <button class="btn btn-primary">Send</button>
</form>

<h4>My Messages</h4>

<?php if (!$convs): ?>
    <div class="alert alert-info">
        No messages yet.
    </div>
<?php endif; ?>

<?php foreach ($convs as $c): ?>

    <div class="card p-3 mb-3">

        <strong>
            <?php echo escapeHtml($c['subject']); ?>
        </strong>

        <small>
            Status:
            <?php echo escapeHtml($c['status']); ?>
        </small>

        <?php
        // קבלת כל ההודעות של השיחה לפי הסדר.
        $messages = dbFetchAll("
            SELECT sender_role, message, created_at
            FROM contact_messages
            WHERE conversation_id = ?
            ORDER BY created_at ASC
        ", [$c['conversation_id']]);
        ?>

        <div class="bg-light border rounded p-2 my-2">

            <?php foreach ($messages as $m): ?>

                <p class="mb-2">
                    <b>
                        <?php echo $m['sender_role'] === 'ADMIN'
                            ? 'Admin'
                            : 'You'; ?>:
                    </b>

                    <?php echo nl2br(escapeHtml($m['message'])); ?>
                    <br>

                    <small>
                        <?php echo escapeHtml($m['created_at']); ?>
                    </small>
                </p>

            <?php endforeach; ?>

        </div>

        <?php if ($c['status'] === 'OPEN'): ?>

            <form method="post" action="reply_contact.php">
                <?php echo csrfInputField(); ?>

                <input
                    type="hidden"
                    name="conversation_id"
                    value="<?php echo (int)$c['conversation_id']; ?>"
                >

                <textarea
                    name="message"
                    class="form-control mb-2"
                    rows="2"
                    required
                ></textarea>

                <button class="btn btn-sm btn-primary">
                    Reply
                </button>
            </form>

        <?php endif; ?>

    </div>

<?php endforeach; ?>

<?php include 'footer.php'; ?>