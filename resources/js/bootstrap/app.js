window._ = require('lodash');
window.Popper = require('popper.js').default;
window.jQueryUI = require('jquery-ui-bundle');
window.moment = require('moment');
window.Plyr = require('plyr');
window.Mark = require('mark.js/dist/jquery.mark.min.js');
window.axios = require('axios').default;

/**
 * We'll load jQuery and the Bootstrap jQuery plugin which provides support
 * for JavaScript based Bootstrap features such as modals and tabs. This
 * code may be modified to fit the specific needs of your application.
 */

try {
    window.$ = window.jQuery = require('jquery');

    require('bootstrap');
} catch (e) {}
