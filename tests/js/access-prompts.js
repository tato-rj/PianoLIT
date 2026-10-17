const assert = require('assert');
const initialize = require('../../resources/js/components/access-prompts');
module.exports = function () {
    const events = {}, title = {}, description = {};
    const attrs = {'data-access-prompt': 'premium', 'data-access-copy': JSON.stringify({default: {title:'More music awaits', description:'Choose Premium.'}, audio: {title:'Keep listening', description:'Hear more.'}, annotations: {title:'Save your markings', description:'Mark more.'}})};
    const modal = {getAttribute(name) {return attrs[name];}, setAttribute(name,value) {attrs[name]=value;}, removeAttribute(name) {delete attrs[name];},
        querySelector(selector) {return selector==='[data-access-title]'?title:description;}};
    const win = {document: {addEventListener(name,fn) {events[name]=fn;}, querySelector() {return modal;}},
        bootstrap: {Modal: {getOrCreateInstance() {return {show(opener) {events['show.bs.modal']({target:modal,relatedTarget:opener});}};}}}};
    const api = initialize(win);
    api.show('#piece-upgrade-modal','audio'); assert.strictEqual(title.textContent,'Keep listening');
    assert.strictEqual(attrs['data-access-action'],undefined);
    events['show.bs.modal']({target:modal}); assert.strictEqual(title.textContent,'More music awaits','A later unknown action must not retain the previous title.');
    const trigger={getAttribute(){return 'annotations';}};
    events['show.bs.modal']({target:modal,relatedTarget:{closest(){return trigger;}}}); assert.strictEqual(title.textContent,'Save your markings');
    api.show(modal,'unknown'); assert.strictEqual(title.textContent,'More music awaits');
    api.show(modal,'__proto__'); assert.strictEqual(title.textContent,'More music awaits');
    attrs['data-access-prompt']='signup';api.show(modal,'audio');assert.strictEqual(title.textContent,'More music awaits','Action-specific signup copy stays unchanged.');
    console.log('Access prompt action-title regressions passed.');
};
if (require.main===module) module.exports();
