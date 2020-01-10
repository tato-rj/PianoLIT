require('./bootstrap');
require('inputmask/dist/jquery.inputmask.bundle.js');
require('./helpers/cookie');
require('./helpers/time');
require('./helpers/string');
require('./helpers/display');
require('./helpers/extensions');
require('./helpers/charts');
require('./cropper/SimpleCropper');
require('./datatable/DataTable');
require('./datatable/DataTableRaw');
require('./components/delete');
require('./components/toggle');

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': app.csrfToken
    }
});
