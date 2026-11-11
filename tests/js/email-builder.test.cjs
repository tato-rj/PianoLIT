const {test} = require('node:test');
const assert = require('node:assert/strict');
const builder = require('../../resources/js/views/admin-email-builder');

test('drop positions move blocks above, below and to either end without losing content', () => {
    const items = [{id:'a',content:'A'}, {id:'b',content:'B'}, {id:'c',content:'C'}];
    assert.deepEqual(builder.move(items,'a',3).map(x=>x.id), ['b','c','a']);
    assert.deepEqual(builder.move(items,'c',0).map(x=>x.id), ['c','a','b']);
    assert.deepEqual(builder.move(items,'b',2), items);
    assert.deepEqual(builder.move(items,'missing',0), items);
    assert.deepEqual(items.map(x=>x.content), ['A','B','C']);
});
test('legacy content opens unchanged in a normalized block and new blocks have unique identities', () => {
    const legacy = '<p>Hello [first_name]</p><img src="https://example.test/original.jpg">';
    const doc = builder.normalize({version:1,settings:{},blocks:[{id:'legacy',type:'text',content:legacy}]});
    assert.equal(doc.blocks[0].content, legacy);
    assert.equal(doc.blocks[0].id, 'legacy');
    assert.equal(doc.settings.background, '#ffffff');
    assert.notEqual(builder.block('panel',doc.settings).id, builder.block('panel',doc.settings).id);
});
