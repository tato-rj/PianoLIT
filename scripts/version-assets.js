const Version = require('laravel-mix/src/components/Version');

// Keep Mix's file watchers, but version only the requested assets. Mix 6's
// built-in version() also hashes every copied file, including large source PSDs.
module.exports = class VersionAssets extends Version {
    register(files) {
        if (this.context.isUsing('hmr')) return;

        super.register(files);
        this.context.api.then(stats => {
            if (stats.hasErrors()) return;

            // Concatenated production assets are minified after version tasks.
            // Hash their final bytes so Laravel always receives the current id.
            files.forEach(file => this.context.manifest.hash(file.replace(/^public\//, '')));
            this.context.manifest.refresh();
        });
    }
};
