// main.js — custom scripts
document.addEventListener('DOMContentLoaded', function () {
    // Auto-dismiss alerts after 5 seconds
    var alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (a) {
        setTimeout(function () {
            var bs = bootstrap.Alert.getOrCreateInstance(a);
            bs.close();
        }, 5000);
    });

    // Marital status toggle — show spouse name if married
    var maritalSelects = document.querySelectorAll('[data-marital-toggle]');
    maritalSelects.forEach(function (sel) {
        sel.addEventListener('change', function () {
            var target = document.getElementById(sel.getAttribute('data-marital-toggle'));
            if (target) {
                target.style.display = (sel.value === 'married') ? '' : 'none';
            }
        });
        // Trigger initial state
        sel.dispatchEvent(new Event('change'));
    });

    // File input validation — only JPG, PNG, JPEG
    var fileInputs = document.querySelectorAll('input[type="file"][data-image-only]');
    fileInputs.forEach(function (inp) {
        inp.addEventListener('change', function () {
            var allowed = ['jpg', 'jpeg', 'png'];
            var label = inp.nextElement;
            for (var i = 0; i < inp.files.length; i++) {
                var ext = inp.files[i].name.split('.').pop().toLowerCase();
                if (allowed.indexOf(ext) === -1) {
                    alert('Only JPG, PNG, JPEG formats are allowed.');
                    inp.value = '';
                    return;
                }
            }
        });
    });
});
