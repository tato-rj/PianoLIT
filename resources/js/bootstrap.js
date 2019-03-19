window._ = require('lodash');
import Popper from 'popper.js/dist/umd/popper.min.js';
import jQueryUI from 'jquery-ui-bundle/jquery-ui.min.js';

try {
    window.$ = window.jQuery = require('jquery');
    window.Popper = Popper;
    window.jQueryUI = jQueryUI;
	require('bootstrap');
} catch (e) {
	console.log(e.message);
}
