<?php
// סיכום הדף:
// הדף מציג את פרופיל המשתמש ומאפשר לעדכן שם ואימייל.
// לפני השמירה נבדקים הנתונים וה-CSRF Token.

require_once 'functions.php';

checkLogin();
ensureVolunteerProfileFields();

$userId = $_SESSION['user_id'];
$message = '';
$error = '';


// קבלת פרטי המשתמש הנוכחי.
$user = dbFetchOne(
    "SELECT * FROM users WHERE user_id = ?",
    [$userId]
);


// טיפול בעדכון הפרופיל.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($token)) {

        $error = 'Invalid request.';

    } else {

        $fullName = cleanInput($_POST['full_name'] ?? '');
        $email = cleanInput($_POST['email'] ?? '');
        $phone = cleanInput($_POST['phone'] ?? '');
        $address = cleanInput($_POST['address'] ?? '');


        if ($fullName === '' || $email === '') {

            $error = 'Please fill in your name and email.';

        } else {

            // בדיקה שהאימייל לא שייך למשתמש אחר.
            $existingResult = dbFetchOne(
                "SELECT user_id
                 FROM users
                 WHERE email = ?
                 AND user_id <> ?",
                [$email, $userId]
            );


            if ($existingResult) {

                $error =
                    'This email is already taken by another account.';

            } else {

                // עדכון השם והאימייל של המשתמש.
                if (
                    dbExecute(
                        "UPDATE users
                         SET full_name = ?, email = ?, phone = ?, address = ?
                         WHERE user_id = ?",
                        [$fullName, $email, $phone, $address, $userId]
                    )
                ) {

                    $message =
                        'Your profile has been updated successfully.';

                    // עדכון השם גם בתוך ה-Session.
                    $_SESSION['full_name'] = $fullName;

                } else {

                    $error =
                        'Unable to update profile at this time.';
                }
            }
        }
    }
}


include 'header.php';
?>

<div class="container mt-5">

    <div class="row justify-content-center">

        <div class="col-md-8">

            <div class="card shadow-sm p-4 bg-white">

                <h2 class="mb-4">
                    My Profile
                </h2>


                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>


                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>


                <form method="POST" class="row g-3">

                    <?php echo csrfInputField(); ?>


                    <div class="col-md-6">

                        <label class="form-label">
                            Full Name
                        </label>

                        <input
                            type="text"
                            name="full_name"
                            class="form-control"
                            value="<?php echo escapeHtml($user['full_name']); ?>"
                            required
                        >

                    </div>


                    <div class="col-md-6">

                        <label class="form-label">
                            Email
                        </label>

                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            value="<?php echo escapeHtml($user['email']); ?>"
                            required
                        >

                    </div>

                    <div class="col-md-6">

                        <label class="form-label">
                            Phone
                        </label>

                        <input
                            type="text"
                            name="phone"
                            class="form-control"
                            value="<?php echo escapeHtml($user['phone'] ?? ''); ?>"
                        >

                    </div>


                    <div class="col-md-6">

                        <label class="form-label">
                            Address / neighborhood
                        </label>

                        <input
                            type="text"
                            name="address"
                            class="form-control"
                            value="<?php echo escapeHtml($user['address'] ?? ''); ?>"
                        >

                    </div>


                    <div class="col-12">

                        <button
                            type="submit"
                            class="btn btn-success w-100"
                        >
                            Update Profile
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>

<?php include 'footer.php'; ?>
