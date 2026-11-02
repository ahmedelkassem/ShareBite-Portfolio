// منع إرسال النماذج إذا كانت الحقول فارغة
document.addEventListener("DOMContentLoaded", function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                alert("يرجى ملء جميع الحقول المطلوبة بشكل صحيح.");
            }
        });
    });
});
