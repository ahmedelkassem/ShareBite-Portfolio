<?php// סיכום הדף:

// הדף מציג שאלות נפוצות על השימוש ב-ShareBite,
// הזמנת תרומות וניהולן.

// הוא עוזר למשתמשים להבין את השלבים הבסיסיים
// בלי לשמור נתונים חדשים.
require_once 'functions.php';
include 'header.php';
?>
<div class="container mt-5">
    <h2 class="mb-3">FAQ — Frequently Asked Questions</h2>
    <div class="accordion" id="faqAccordion">
        <div class="accordion-item">
            <h2 class="accordion-header" id="q1">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#a1" aria-expanded="true" aria-controls="a1">
                    How do I claim an offer?
                </button>
            </h2>
            <div id="a1" class="accordion-collapse collapse show" aria-labelledby="q1" data-bs-parent="#faqAccordion">
                <div class="accordion-body">Log in as a Volunteer, open the offer details and click "Claim" to reserve pickup.</div>
            </div>
        </div>
        <div class="accordion-item">
            <h2 class="accordion-header" id="q2">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a2" aria-expanded="false" aria-controls="a2">
                    Can I list food anonymously?
                </button>
            </h2>
            <div id="a2" class="accordion-collapse collapse" aria-labelledby="q2" data-bs-parent="#faqAccordion">
                <div class="accordion-body">No — accounts are required so volunteers can coordinate pickups. Use a business or organization email if preferred.</div>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php';
