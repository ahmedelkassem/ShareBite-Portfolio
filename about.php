<?php
// סיכום הדף:
// הדף מציג מידע כללי על ShareBite ועל המטרה של הפחתת בזבוז מזון.
// הוא מסביר את התפקיד של התורמים, המתנדבים וה-Admin בתיאום תרומות המזון.

require_once 'functions.php';
include 'header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm p-4">
                <h1 class="mb-3">About ShareBite</h1>

                <p class="lead">
                    ShareBite is a local food donation platform that connects donors, volunteers, and community organizations to reduce waste and help people in need.
                </p>

                <p>
                    Our goal is to make it easy for food providers to share surplus meals and for volunteers to claim and deliver them safely. The platform supports donors, volunteers, and administrators with clear offer management, pickup coordination, and status tracking.
                </p>

                <p>
                    ShareBite was built to encourage community support, reduce edible food waste, and strengthen local food-sharing networks.
                </p>

                <a href="contact.php" class="btn btn-primary mt-3">
                    Contact Support
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>