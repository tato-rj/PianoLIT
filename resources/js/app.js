require('./bootstrap');
require('./vendor/clamp');

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': app.csrfToken
    }
});