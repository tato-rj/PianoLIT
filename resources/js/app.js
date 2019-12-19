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