require('./bootstrap');
require('inputmask/dist/jquery.inputmask.bundle.js');
require('./helpers/cookie');
require('./helpers/string');
require('./helpers/extensions');
require('./helpers/charts');
require('./cropper/SimpleCropper');

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': app.csrfToken
    }
});
