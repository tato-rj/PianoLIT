require('./bootstrap');
require('./vendor/clamp');
require('./helpers/display');
require('./helpers/extensions');
require('./helpers/url');
require('./helpers/string');

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': app.csrfToken
    }
});