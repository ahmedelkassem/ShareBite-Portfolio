<?php
// סיכום הדף:

// הדף מציג את מדיניות הפרטיות של פלטפורמת ShareBite.

// הוא מסביר אילו נתונים נאספים, איך משתמשים בהם ואיך מגנים עליהם.
require_once 'functions.php';
include 'header.php';
?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm p-4">
                <h1 class="mb-3">Privacy Policy</h1>
                <p class="lead">ShareBite values your privacy and handles your information responsibly.</p>
                <h5>Information we collect</h5>
                <p>We collect only the information required to create user accounts and manage donations, such as name, email, password hash, role, and donation details.</p>
                <h5>How we use data</h5>
                <p>Data is used to authenticate users, support offer matching, manage reservations, and send notifications about platform activity.</p>
                <h5>Security</h5>
                <p>Passwords are stored securely as hashes and sensitive operations use CSRF protections to keep your account safe.</p>
                <p class="mb-0">If you have questions about privacy, contact the ShareBite support team.</p>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php';
