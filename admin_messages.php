<?php
// סיכום הדף:
// הדף מציג שיחות תמיכה שנשלחו מהמשתמשים ל-Admin.
// ה-Admin יכול לסגור שיחות ולעקוב אחרי הודעות פתוחות.

require_once 'functions.php';

checkLogin();

if (($_SESSION['role'] ?? '') !== 'Admin') {
    redirect('index.php');
}


// סגירת שיחת התמיכה כאשר ה-Admin לוחץ על כפתור הסגירה.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_conversation'])) {

    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {

        dbExecute(
            "UPDATE contact_conversations
             SET status = 'CLOSED',
                 updated_at = NOW()
             WHERE conversation_id = ?",
            [(int)$_POST['conversation_id']]
        );
    }

    redirect('admin_messages.php');
}


// קבלת כל שיחות התמיכה והצגת השיחות הפתוחות ראשונות.
$conversations = dbFetchAll("
    SELECT *
    FROM contact_conversations
    ORDER BY status ASC, updated_at DESC
");


include 'header.php';
?>

<h2 class="mb-4">
    Admin Messages
</h2>


<?php if (!$conversations): ?>

    <div class="alert alert-info">
        No messages yet.
    </div>

<?php endif; ?>


<?php foreach ($conversations as $conv): ?>

    <?php

    // קבלת ההודעות של השיחה הנוכחית לפי הסדר הכרונולוגי.
    $messages = dbFetchAll("
        SELECT sender_role, message, created_at
        FROM contact_messages
        WHERE conversation_id = ?
        ORDER BY created_at ASC
    ", [
        $conv['conversation_id']
    ]);

    ?>


    <div class="card mb-4 p-3">

        <div class="d-flex justify-content-between gap-3">

            <div>

                <h5>
                    <?php echo escapeHtml($conv['subject']); ?>
                </h5>

                <p class="mb-1">
                    <strong>From:</strong>

                    <?php echo escapeHtml($conv['name']); ?>

                    (<?php echo escapeHtml($conv['email']); ?>)
                </p>

                <span class="badge bg-<?php echo $conv['status'] === 'OPEN' ? 'success' : 'secondary'; ?>">

                    <?php echo escapeHtml($conv['status']); ?>

                </span>

            </div>


            <?php if ($conv['status'] === 'OPEN'): ?>

                <form method="post">

                    <?php echo csrfInputField(); ?>

                    <input
                        type="hidden"
                        name="conversation_id"
                        value="<?php echo (int)$conv['conversation_id']; ?>"
                    >

                    <button
                        name="close_conversation"
                        class="btn btn-sm btn-danger"
                    >
                        Close
                    </button>

                </form>

            <?php endif; ?>

        </div>


        <hr>


        <div class="border rounded p-3 mb-3 bg-light">

            <?php foreach ($messages as $msg): ?>

                <p class="mb-2">

                    <strong>
                        <?php echo $msg['sender_role'] === 'ADMIN'
                            ? 'Admin'
                            : 'User'; ?>:
                    </strong>

                    <?php echo nl2br(escapeHtml($msg['message'])); ?>

                    <br>

                    <small class="text-muted">
                        <?php echo escapeHtml($msg['created_at']); ?>
                    </small>

                </p>

            <?php endforeach; ?>

        </div>


        <?php if ($conv['status'] === 'OPEN'): ?>

            <form
                method="post"
                action="reply_contact.php"
            >

                <?php echo csrfInputField(); ?>

                <input
                    type="hidden"
                    name="conversation_id"
                    value="<?php echo (int)$conv['conversation_id']; ?>"
                >

                <textarea
                    name="message"
                    class="form-control mb-2"
                    rows="2"
                    required
                ></textarea>

                <button class="btn btn-sm btn-primary">
                    Send Reply
                </button>

            </form>

        <?php else: ?>

            <div class="alert alert-secondary mb-0">
                Conversation closed.
            </div>

        <?php endif; ?>

    </div>

<?php endforeach; ?>


<?php include 'footer.php'; ?>