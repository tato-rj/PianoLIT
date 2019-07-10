require('./bootstrap');
require('./vendor/clamp');
require('./helpers/display');
require('./helpers/extensions');

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': app.csrfToken
    }
});