<?php
// סיכום הדף:

// הדף מסביר את שלבי העבודה של ShareBite,
// מפרסום התרומה ועד לקבלה ולמעקב.

// הוא מציג בצורה פשוטה את התפקידים של התורמים,
// המתנדבים וה-Admin.
require_once 'functions.php';
include 'header.php';
?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm p-4">
                <h1 class="mb-3">How ShareBite Works</h1>
                <p class="lead">A simple food donation platform for donors, volunteers, and administrators.</p>
                <h3>1. Donors list food offers</h3>
                <p>Donors create an account, describe the donation, set pickup location and times, then publish the offer. This helps local volunteers find available food fast.</p>
                <h3>2. Volunteers claim offers</h3>
                <p>Volunteers browse offers, save favorites, and claim donations they can pick up. Once claimed, the donation status updates so others know it is reserved.</p>
                <h3>3. Coordinated pickup</h3>
                <p>Volunteers coordinate with donors through the details in the offer. Clear pickup windows and locations help reduce waste and ensure safe transfer.</p>
                <h3>4. Admin oversight</h3>
                <p>Admins manage users, monitor donations, and keep the platform running safely. They can promote trusted users to admin and remove invalid accounts.</p>
                <h3>5. Community impact</h3>
                <p>ShareBite helps local kitchens and volunteers reduce food waste while supporting people who need fresh meals. Every claim helps the community.</p>
                <a href="register.php" class="btn btn-primary mt-3">Join ShareBite</a>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php';
