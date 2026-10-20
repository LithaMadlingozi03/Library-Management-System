// Small progressive enhancements. Everything is also checked on the server.
document.addEventListener('DOMContentLoaded', function () {

    // Password strength meter: <input data-strength="#meterId" data-strict="1">
    document.querySelectorAll('input[data-strength]').forEach(function (input) {
        const bar = document.querySelector(input.dataset.strength);
        if (!bar) return;
        input.addEventListener('input', function () {
            const v = input.value;
            let score = 0;
            if (v.length >= (input.dataset.strict ? 12 : 8)) score++;
            if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
            if (/\d/.test(v)) score++;
            if (/[^A-Za-z0-9]/.test(v)) score++;
            bar.className = score ? 'strength-' + score : '';
        });
    });

    // "Passwords match" hint: <input data-match="#otherInput" data-match-hint="#hintId">
    document.querySelectorAll('input[data-match]').forEach(function (input) {
        const other = document.querySelector(input.dataset.match);
        const hint = document.querySelector(input.dataset.matchHint);
        if (!other || !hint) return;
        const check = function () {
            if (!input.value) { hint.textContent = ''; return; }
            const ok = input.value === other.value;
            hint.textContent = ok ? 'Passwords match' : "Passwords don't match";
            hint.className = 'form-text ' + (ok ? 'text-success' : 'text-danger');
        };
        input.addEventListener('input', check);
        other.addEventListener('input', check);
    });

    // Ask before destructive actions: <form data-confirm="Are you sure?">
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm(form.dataset.confirm)) e.preventDefault();
        });
    });
});
