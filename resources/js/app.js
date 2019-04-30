require('./bootstrap');
require('./vendor/clamp');
require('./helpers/display');

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': app.csrfToken
    }
});