window._ = require('lodash');
window.jQueryUI = require('jquery-ui-bundle');
window.moment = require('moment');
// window.Plyr = require('plyr');
window.Mark = require('mark.js/dist/jquery.mark.min.js');
window.axios = require('axios').default;
window.Masonry = require('masonry-layout');

/**
 * Bootstrap 5's bundle includes Popper and registers its optional jQuery
 * interface when jQuery is present.
 */

try {
    window.$ = window.jQuery = require('jquery');

    window.bootstrap = require('bootstrap/dist/js/bootstrap.bundle');
} catch (e) {}
