require('./bootstrap/app');
require('dragscroll/dragscroll.js');
require('./vendor/clamp');
require('./helpers/display');
require('./helpers/array');
require('./helpers/extensions');
require('./helpers/url');
require('./helpers/string');
require('./helpers/cookie');
require('./helpers/numbers');
require('./components/modals');
require('./components/tables');
require('./components/forms');
require('./components/triggers');
require('./components/overlays');
require('./components/progressbar');
require('./components/auth');
require('./components/toggle');
require('./youtube/YoutubeV3');
require('./search/Search');

require('./startup');

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': app.csrfToken
    }
});

$(function(){
    var requiredCheckboxes = $('.options-required :checkbox[required]');
    requiredCheckboxes.change(function(){
        if(requiredCheckboxes.is(':checked')) {
            requiredCheckboxes.removeAttr('required');
        } else {
            requiredCheckboxes.attr('required', 'required');
        }
    });
});