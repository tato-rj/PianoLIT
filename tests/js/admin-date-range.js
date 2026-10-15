const assert = require('assert');
const {validate} = require('../../resources/js/components/admin-date-range');

module.exports = function () {
    assert.strictEqual(validate('2026-10-07', '2026-10-13', true), '');
    assert.strictEqual(validate('2026-10-13', '2026-10-13', true), '');
    assert(validate('', '2026-10-13', true).includes('both dates'));
    assert(validate('2026-10-13', '2026-10-07', true).includes('on or after'));
    assert(validate('2026-02-29', '2026-03-01', true).includes('valid dates'));
    assert.strictEqual(validate('2024-02-29', '2024-03-01', true), '');
    assert.strictEqual(validate('2026-01-01', '2027-01-01', true), '');
    assert.strictEqual(validate('2026-01-01', '2027-01-02', true), '', 'Custom ranges can exceed one year.');
    assert.strictEqual(validate('2018-01-01', '2026-10-13', true), '', 'Multi-year activity ranges remain valid.');
    assert.strictEqual(validate('2026-10-31', '2026-11-02', true), '', 'Calendar spans are independent of DST offsets.');
    assert.strictEqual(validate('', '2026-10-13', false), '', 'People filters retain an open start.');
    assert.strictEqual(validate('2018-01-01', '', false), '', 'People filters retain an open end.');
    assert.strictEqual(validate('2018-01-01', '2026-10-13', false), '', 'People dates remain unrestricted.');
    assert.strictEqual(validate('', '', false), '');
    assert(validate('<script>', '2026-10-13', false).includes('valid dates'));
};
