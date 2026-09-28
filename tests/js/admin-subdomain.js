const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

module.exports = function () {
    const source = fs.readFileSync(path.join(__dirname, '../../resources/js/tinyeditor/tiny.js'), 'utf8');
    for (const origin of ['https://admin.pianolit.com', 'http://admin.pianolit.test']) {
        const uploadUrl = origin + '/blog/images/upload';
        let settings;
        let request;
        class UploadRequest {
            constructor() { request = this; }
            open(method, url) { this.method = method; this.url = url; }
            setRequestHeader(name, value) { this.headers = {[name]: value}; }
            send(body) { this.body = body; }
        }
        class UploadData {
            append(name, blob, filename) { this.file = {name, blob, filename}; }
        }
        vm.runInNewContext(source, {
            window: {app: {routes: {blogImageUpload: uploadUrl}, csrfToken: 'review-csrf'}},
            tinymce: {init(options) { settings = options; }},
            XMLHttpRequest: UploadRequest,
            FormData: UploadData
        });
        assert.strictEqual(settings.images_upload_url, uploadUrl);
        let uploaded;
        settings.images_upload_handler({blob: () => 'image', filename: () => 'score.png'}, url => { uploaded = url; }, message => { throw new Error(message); });
        assert.strictEqual(request.method, 'POST');
        assert.strictEqual(request.url, uploadUrl);
        assert.deepStrictEqual(request.headers, {'X-CSRF-TOKEN': 'review-csrf'});
        assert.deepStrictEqual(request.body.file, {name: 'file', blob: 'image', filename: 'score.png'});
        request.status = 200;
        request.responseText = JSON.stringify({location: origin + '/storage/score.png'});
        request.onload();
        assert.strictEqual(uploaded, origin + '/storage/score.png');
    }
    console.log('Passed: admin editor uploads use the configured subdomain route.');
};
