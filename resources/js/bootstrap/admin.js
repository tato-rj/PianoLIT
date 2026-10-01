window._ = require('lodash');
window.jQueryUI = require('jquery-ui-bundle');
window.moment = require('moment');
window.Calendar = require('fullcalendar');
window.Trix = require('trix');
window.Cropper = require('cropperjs');
window.Mark = require('mark.js/dist/jquery.mark.min.js');
window.axios = require('axios').default;

/**
 * Bootstrap 5's bundle includes Popper and registers its optional jQuery
 * interface when jQuery is present.
 */

try {
    window.$ = window.jQuery = require('jquery');

    window.bootstrap = require('bootstrap/dist/js/bootstrap.bundle');
} catch (e) {}
