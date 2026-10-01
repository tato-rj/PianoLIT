const mix = require('laravel-mix');

// css-loader 0.28 uses cssnano 3, whose longhand merger mistakes custom
// properties such as --bs-border-width/style for ordinary border properties.
// Keep those variables intact in production; components resolve them at runtime.
mix.extend('preserveCssVariables', config => {
    config.module.rules.forEach(rule => {
        ['use', 'loaders'].forEach(key => {
            if (!Array.isArray(rule[key])) return;
            rule[key] = rule[key].map(loader => {
                if (loader === 'css-loader') loader = {loader};
                if (loader.loader === 'css-loader') {
                    loader.options = Object.assign({}, loader.options, {
                        minimize: mix.inProduction() ? {mergeLonghand: false} : false
                    });
                }
                return loader;
            });
        });
    });
});
mix.preserveCssVariables();

// Mix 2 excludes node_modules from Babel. Bootstrap 5 needs transpilation
// for this project's Webpack 3/Uglify production build.
mix.webpackConfig({
    module: {
        rules: [{
            test: /bootstrap[\\/]dist[\\/]js[\\/]bootstrap\.bundle\.js$|bootstrap[\\/]js[\\/]dist[\\/].*\.js$/,
            loader: 'babel-loader',
            options: {
                presets: [['env', {modules: false}]],
                plugins: ['transform-object-rest-spread']
            }
        }]
    }
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

mix.js('resources/js/app.js', 'public/js')
    .js('resources/js/admin.js', 'public/js')
    .js('resources/js/tone.js', 'public/js')
    .sass('resources/sass/app.scss', 'public/css')
    .sass('resources/sass/admin.scss', 'public/css')
    .sass('resources/sass/design-system.scss', 'public/css')
    .sass('resources/sass/studio-policies/layout.scss', 'public/css/studio-policies')
    .sass('resources/sass/studio-policies/simple.scss', 'public/css/studio-policies')
    .sass('resources/sass/studio-policies/elegant.scss', 'public/css/studio-policies')
    .sass('resources/sass/studio-policies/informal.scss', 'public/css/studio-policies')
    .styles([
      'public/css/app.css',
      'resources/sass/primer/primer.css',
      'public/css/design-system.css'
      ], 'public/css/app.css')
    .styles([
      'resources/sass/vendor/theme.min.css',
      'resources/sass/vendor/tables.css',
      'resources/sass/vendor/dropzone.css',
      'public/css/admin.css',
      'resources/sass/primer/primer.css',
      'public/css/design-system.css'
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
    //SVG FLAGS
    .copy('node_modules/flag-icon-css/css/flag-icon.min.css', 'public/css/vendor/flag-icon')
    .copy('node_modules/flag-icon-css/flags', 'public/css/vendor/flags')
    .version(['public/js/views/piece-timeline-admin.js', 'public/js/views/video-moments.js', 'public/js/views/video-moments-admin.js', 'public/js/views/piece-access.js', 'public/js/views/piece-description.js', 'public/js/views/score-editor.js', 'public/js/views/match-tour.js', 'public/js/views/collections.js', 'public/js/views/folders.js', 'public/js/tinyeditor/tiny.js']);
