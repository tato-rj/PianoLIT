const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/app.js', 'public/js')
	.js('resources/js/admin.js', 'public/js')
   .sass('resources/sass/app.scss', 'public/css')
   .sass('resources/sass/admin.scss', 'public/css')
   .styles([
	    'resources/sass/vendor/theme.min.css',
	    'resources/sass/vendor/tables.css',
	    'public/css/admin.css',
	], 'public/css/admin.css')
   .scripts([
         'public/js/admin.js',
   		'resources/js/vendor/Chart.min.js',
   		'resources/js/vendor/jquery.dataTables.js',
   		'resources/js/vendor/jquery.easing.min.js',
   		'resources/js/vendor/tables.min.js',
   		'resources/js/vendor/theme.min.js'
   	], 'public/js/admin.js')
   .copyDirectory('resources/js/vendor', 'public/js/vendor')
   .copyDirectory('resources/images', 'public/images')
   .version();
