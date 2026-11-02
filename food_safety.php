<?php
// סיכום הדף:

// הדף מציג הנחיות לבטיחות מזון עבור תורמים ומתנדבים.

// הוא מסביר איך לארוז את המזון, לציין מרכיבים ולשמור עליו בזמן ההובלה.
require_once 'functions.php';
include 'header.php';
?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm p-4">
                <h1 class="mb-3">Food Safety Guidelines</h1>
                <p class="lead">Keep donations safe for pickup and delivery with simple best practices.</p>
                <h5>Wash and protect food</h5>
                <p>Keep prepared food covered and use clean containers to avoid contamination. Donors should package items securely for transport.</p>
                <h5>Label and describe</h5>
                <p>Include clear information about contents, allergens, storage instructions, and pick-up timing.</p>
                <h5>Keep it cool or hot</h5>
                <p>Perishable items should stay at safe temperatures. Use coolers for cold foods and insulated containers for hot meals.</p>
                <h5>Respect pickup windows</h5>
                <p>Volunteers should arrive within the stated pickup window to ensure food stays fresh and safe.</p>
                <h5>Communicate clearly</h5>
                <p>If there are special handling instructions, note them in the offer so volunteers can prepare accordingly.</p>
                <a href="contact.php" class="btn btn-primary mt-3">Contact Support</a>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php';
