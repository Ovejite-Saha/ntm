// session-timeout.js — 2-minute (120 seconds) auto logout timer
(function () {
    var TIMEOUT = 120 * 1000; // 2 minutes
    var warnAt = 10 * 1000;   // warn 10s before
    var timer, warnTimer;
    var timeoutUrl;

    function getTimeoutUrl() {
        var path = window.location.pathname;
        if (path.indexOf('/admin/') !== -1) {
            timeoutUrl = '../login-redirect.php?timeout=1';
        } else if (path.indexOf('/user/') !== -1) {
            timeoutUrl = '../login-redirect.php?timeout=1';
        } else {
            timeoutUrl = 'login-redirect.php?timeout=1';
        }
    }

    function logout() {
        window.location.href = timeoutUrl;
    }

    function resetTimer() {
        clearTimeout(timer);
        clearTimeout(warnTimer);
        warnTimer = setTimeout(function () {
            // Optional: could show a warning here
        }, TIMEOUT - warnAt);
        timer = setTimeout(logout, TIMEOUT);
    }

    getTimeoutUrl();
    // Track user activity
    ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'].forEach(function (evt) {
        document.addEventListener(evt, resetTimer, { passive: true });
    });
    resetTimer();
})();
