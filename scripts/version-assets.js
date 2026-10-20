const Version = require('laravel-mix/src/components/Version');
const crypto = require('crypto');
const fs = require('fs');

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
            // Mix reads files as UTF-8 when hashing; PNG/ICO versions must hash
            // their original bytes too, rather than replacement characters.
            files.forEach(file => {
                const hash = crypto.createHash('md5').update(fs.readFileSync(file)).digest('hex');
                this.context.manifest.add(file.replace(/^public\//, '') + '?id=' + hash);
            });
            this.context.manifest.refresh();
        });
    }
};
