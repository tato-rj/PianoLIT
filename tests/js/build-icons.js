const assert = require('assert');
const {discoverNames} = require('../../scripts/build-icons');

module.exports = function () {
    const available = new Set(['sparkles', 'x', 'music']);
    const aliases = {close: 'x'};
    assert.deepStrictEqual(discoverNames(`
        @icon('sparkles')
        @icon("close", ['mr' => 0])
        @button(['icon' => 'music'])
        {!! \\App\\Support\\Icon::render('sparkles') !!}
        @icon('brand-apple')
        @icon('not-a-lucide-icon')
        @icon($dynamicName)
        @icon('../../.env')
        <p>sparkles</p>
    `, available, aliases), ['sparkles', 'x', 'music']);
    assert.deepStrictEqual(discoverNames(`@button(['icon' => 'sparkles'])`, available, aliases), ['sparkles']);
    assert.deepStrictEqual(discoverNames(`@icon('sparkles')`, available, aliases), ['sparkles']);
};
