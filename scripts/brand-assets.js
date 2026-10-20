// Version every entry point into the approved brand artwork, including browser
// metadata. Manifest icon URLs also carry hashes for installed-app refreshes.
module.exports = [
    ...['', 'admin-'].flatMap(prefix => [
        'favicon.ico', 'favicon-16x16.png', 'favicon-32x32.png',
        'apple-touch-icon.png', 'android-chrome-192x192.png',
        'android-chrome-512x512.png', 'mstile-150x150.png', 'safari-pinned-tab.svg'
    ].map(file => 'public/' + prefix + file)),
    'public/site.webmanifest', 'public/admin.site.webmanifest',
    'public/browserconfig.xml', 'public/admin.browserconfig.xml',
    ...['app-icon.svg', 'admin-icon.svg', 'app-icon.png', 'admin-icon.png',
        'app-icon-ios-1024.png', 'icon-rounded.png', 'icon-rounded-margin.png'
    ].map(file => 'public/images/brand/' + file)
];
