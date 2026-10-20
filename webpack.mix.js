const mix = require('laravel-mix');
const brandAssets = require('./scripts/brand-assets');
mix.extend('versionAssets', require('./scripts/version-assets'));

// Use Dart Sass's modern API. The legacy Sass/Babel workarounds for Mix 2
// are superseded by Mix 6's PostCSS 8 and Webpack 5 pipeline.
const sassOptions = {
    implementation: require('sass'),
    api: 'modern',
    sassOptions: {style: 'expanded'}
};

mix.options({
    // Keep Bootstrap's border custom properties intact when minifying CSS.
    cssNano: {mergeLonghand: false}
});

mix.webpackConfig({
    output: {hashFunction: 'xxhash64'},
    // Concatenation reads the compiled intermediates from disk in hot mode too.
    devServer: {devMiddleware: {writeToDisk: true}}
});

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

// Compile intermediate bundles separately: Mix 6 excludes a concatenation's
// destination from its inputs, so in-place styles()/scripts() would lose code.
mix.js('resources/js/app.js', 'public/.mix/js')
    .js('resources/js/admin.js', 'public/.mix/js')
    .js('resources/js/tone.js', 'public/js')
    .sass('resources/sass/app.scss', 'public/.mix/css', sassOptions)
    .sass('resources/sass/admin.scss', 'public/.mix/css', sassOptions)
    .sass('resources/sass/shared.scss', 'public/css', sassOptions)
    .styles([
      'public/.mix/css/app.css',
      'public/css/shared.css'
      ], 'public/css/app.css')
    .styles([
      'resources/sass/vendor/admin-theme.min.css',
      'resources/sass/vendor/datatables.css',
      'resources/sass/vendor/dropzone.css',
      'public/.mix/css/admin.css',
      'public/css/shared.css'
      ], 'public/css/admin.css')
    .scripts([
      'public/.mix/js/app.js',
      'node_modules/swiper/dist/js/swiper.min.js',
      ], 'public/js/app.js')
    .scripts([
      'public/.mix/js/admin.js',
      'resources/js/vendor/Chart.min.js',
      'resources/js/vendor/jquery.dataTables.js',
      'resources/js/components/admin-table-fields.js',
      'resources/js/vendor/jquery.easing.min.js',
      'resources/js/vendor/tables.min.js',
      'resources/js/vendor/theme.min.js'
      ], 'public/js/admin.js')
    .copyDirectory('resources/js/vendor', 'public/js/vendor')
    .copyDirectory('resources/js/views', 'public/js/views')
    .copyDirectory('resources/js/components', 'public/js/components')
    .copyDirectory('resources/js/tinyeditor', 'public/js/tinyeditor')
    .copyDirectory('resources/images', 'public/images')
    .copyDirectory('resources/audio/metronome', 'public/audio/metronome')
    .copy('resources/data/composer-globe.geojson', 'public/data/composer-globe.geojson')
    .copy('resources/fonts/escore/LibreBodoni-Regular.ttf', 'public/fonts/escore/LibreBodoni-Regular.ttf')
    .copy('resources/fonts/escore/LibreBodoni-Bold.ttf', 'public/fonts/escore/LibreBodoni-Bold.ttf')
    .copy('resources/fonts/escore/OFL.txt', 'public/fonts/escore/OFL.txt')
    //SVG FLAGS
    .copy('node_modules/flag-icon-css/css/flag-icon.min.css', 'public/css/vendor/flag-icon')
    .copy('node_modules/flag-icon-css/flags', 'public/css/vendor/flags')
    // Mix 6 otherwise versions every copied file, including large source PSDs.
    // Hash the existing entry points and explicit version list after minification.
    .versionAssets(['public/js/app.js', 'public/js/admin.js', 'public/js/tone.js', 'public/css/app.css', 'public/css/admin.css', 'public/css/shared.css', 'public/js/views/admin-design-system.js', 'public/js/views/admin-access-limits.js', 'public/js/views/membership-billing.js', 'public/js/views/checkout.js', 'public/js/views/admin-user-profile.js', 'public/js/components/admin-table-loading.js', 'public/js/components/activity.js', 'public/js/components/audio.js', 'public/js/components/score-metronome.js', 'public/js/vendor/globe.gl.min.js', 'public/data/composer-globe.geojson', 'public/js/views/composer-globe.js', 'public/js/views/explore-moods.js', 'public/js/views/explore-transitions.js', 'public/js/views/explore.js', 'public/js/views/highlights.js', 'public/js/views/campaign-attribution.js', 'public/js/views/composer-biography-admin.js', 'public/js/views/composers.js', 'public/js/views/escore.js', 'public/js/views/feedback.js', 'public/js/views/playlist-moments.js', 'public/js/views/playlist-player.js', 'public/js/views/admin-pieces.js', 'public/js/views/admin-piece-editor.js', 'public/js/views/admin-user-map.js', 'public/js/views/timeline-events-admin.js', 'public/js/views/video-moments.js', 'public/js/views/video-moments-admin.js', 'public/js/views/piece-access.js', 'public/js/views/piece-description.js', 'public/js/views/score-editor.js', 'public/js/views/match-tour.js', 'public/js/views/collections.js', 'public/js/views/folders.js', 'public/js/tinyeditor/tiny.js', ...brandAssets]);
