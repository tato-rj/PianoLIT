require('./components/icons');
require('./components/logo-tilt')(window);
require('./bootstrap/admin');
if (window.AdminTableLoading) window.AdminTableLoading.bind(window.jQuery);
require('./components/offcanvas');
require('inputmask/dist/jquery.inputmask.bundle.js');
require('./helpers/cookie');
require('./helpers/time');
require('./helpers/string');
require('./helpers/display');
require('./helpers/extensions');
require('./helpers/charts');
require('./cropper/SimpleCropper');
require('./search/Search');
require('./quickchart/ChartBootstrap');
require('./quickchart/ChartFactory');
require('./quickchart/QuickChart');
require('./datatable/DataTable');
require('./datatable/DataTableRaw');
require('./components/modals');
require('./components/access-prompts')(window);
require('./components/tables');
require('./components/toggle');
require('./components/forms');
require('./components/triggers');
require('./components/audio');
require('./components/admin-text-improver');
require('./components/admin-date-range');
require('./components/admin-daily-activity-chart');
require('./components/checkbox-menu');
require('./components/admin-back-to-top');
require('./components/tab-navigation')(window);
const { BottomSheetDrag } = require('./controls/bottom-sheet-drag');
document.querySelectorAll('[data-sheet-draggable]').forEach(panel => new BottomSheetDrag(panel, () => window.bootstrap.Offcanvas.getOrCreateInstance(panel).hide()));
const { FontSelect } = require('./controls/font-select');
document.querySelectorAll('[data-font-select]').forEach(root => new FontSelect(root, window.bootstrap.Dropdown));

require('./startup');

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': app.csrfToken
    }
});
