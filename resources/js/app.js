require('./bootstrap');
require('./vendor/clamp');
require('./helpers/display');
require('./helpers/extensions');
require('./helpers/url');

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': app.csrfToken
    }
});