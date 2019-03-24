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
   .styles([
       'resources/vendor/landing-page/css/bootstrap.min.css',
       'resources/vendor/landing-page/css/jquery-ui.css',
       'resources/vendor/landing-page/css/font-awesome.min.css',
       'resources/vendor/landing-page/css/owl.carousel.min.css',
       'resources/vendor/landing-page/css/slicknav.min.css',
       'resources/vendor/landing-page/css/magnificpopup.css',
       'resources/vendor/landing-page/css/jquery.mb.YTPlayer.min.css',
       'resources/vendor/landing-page/css/typography.css',
       'resources/vendor/landing-page/css/style.css',
       'resources/vendor/landing-page/css/responsive.css',
   ], 'public/landing-page/css/theme.css')
   .scripts([
         'public/js/admin.js',
   		'resources/js/vendor/Chart.min.js',
   		'resources/js/vendor/jquery.dataTables.js',
   		'resources/js/vendor/jquery.easing.min.js',
   		'resources/js/vendor/tables.min.js',
   		'resources/js/vendor/theme.min.js'
   	], 'public/js/admin.js')
   .scripts([
         'resources/vendor/landing-page/js/jquery-3.2.0.min.js',
         'resources/vendor/landing-page/js/jquery-ui.js',
         'resources/vendor/landing-page/js/bootstrap.min.js',
         'resources/vendor/landing-page/js/jquery.slicknav.min.js',
         'resources/vendor/landing-page/js/owl.carousel.min.js',
         'resources/vendor/landing-page/js/magnific-popup.min.js',
         'resources/vendor/landing-page/js/counterup.js',
         'resources/vendor/landing-page/js/jquery.waypoints.min.js',
         'resources/vendor/landing-page/js/jquery.mb.YTPlayer.min.js',
         'resources/vendor/landing-page/js/theme.js',
      ], 'public/landing-page/js/theme.js')
   .copyDirectory('resources/js/vendor', 'public/js/vendor')
   .copyDirectory('resources/images', 'public/images')
   .copyDirectory('resources/vendor/landing-page/img', 'public/landing-page/img')
   .copyDirectory('resources/vendor/landing-page/fonts', 'public/landing-page/fonts')
   .version();
