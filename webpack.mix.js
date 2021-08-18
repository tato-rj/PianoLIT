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
    .js('resources/js/tone.js', 'public/js')
    .sass('resources/sass/app.scss', 'public/css')
    .sass('resources/sass/admin.scss', 'public/css')
    .sass('resources/sass/studio-policies/layout.scss', 'public/css/studio-policies')
    .sass('resources/sass/studio-policies/simple.scss', 'public/css/studio-policies')
    .sass('resources/sass/studio-policies/elegant.scss', 'public/css/studio-policies')
    .sass('resources/sass/studio-policies/informal.scss', 'public/css/studio-policies')
    .styles([
      'public/css/app.css',
      'resources/sass/primer/primer.css'
      ], 'public/css/app.css')
    .styles([
      'resources/sass/vendor/theme.min.css',
      'resources/sass/vendor/tables.css',
      'resources/sass/vendor/dropzone.css',
      'public/css/admin.css',
      'resources/sass/primer/primer.css'
      ], 'public/css/admin.css')
    .scripts([
      'public/js/app.js',
      'node_modules/swiper/dist/js/swiper.min.js',
      ], 'public/js/app.js')
    .scripts([
      'public/js/admin.js',
      'resources/js/vendor/Chart.min.js',
      'resources/js/vendor/jquery.dataTables.js',
      'resources/js/vendor/jquery.easing.min.js',
      'resources/js/vendor/tables.min.js',
      'resources/js/vendor/theme.min.js'
      ], 'public/js/admin.js')
    .copyDirectory('resources/js/vendor', 'public/js/vendor')
    .copyDirectory('resources/js/views', 'public/js/views')
    .copyDirectory('resources/js/components', 'public/js/components')
    .copyDirectory('resources/js/tinyeditor', 'public/js/tinyeditor')
    .copyDirectory('resources/images', 'public/images')
    //FONTAWESOME
    .copy('node_modules/@fortawesome/fontawesome-free/css/all.min.css', 'public/css/vendor/fontawesome')
    .copy('node_modules/@fortawesome/fontawesome-free/webfonts', 'public/css/vendor/webfonts')
    //SVG FLAGS
    .copy('node_modules/flag-icon-css/css/flag-icon.min.css', 'public/css/vendor/flag-icon')
    .copy('node_modules/flag-icon-css/flags', 'public/css/vendor/flags')
    .version();

