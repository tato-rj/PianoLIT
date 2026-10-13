const assert = require('assert');
const {validate} = require('../../resources/js/components/admin-date-range');

module.exports = function () {
    assert.strictEqual(validate('2026-10-07', '2026-10-13', true, 366), '');
    assert.strictEqual(validate('2026-10-13', '2026-10-13', true, 366), '');
    assert(validate('', '2026-10-13', true, 366).includes('both dates'));
    assert(validate('2026-10-13', '2026-10-07', true, 366).includes('on or after'));
    assert(validate('2026-02-29', '2026-03-01', true, 366).includes('valid dates'));
    assert.strictEqual(validate('2024-02-29', '2024-03-01', true, 366), '');
    assert.strictEqual(validate('2026-01-01', '2027-01-01', true, 366), '');
    assert(validate('2026-01-01', '2027-01-02', true, 366).includes('366 days'));
    assert.strictEqual(validate('2026-10-31', '2026-11-02', true, 3), '', 'Calendar spans are independent of DST offsets.');
    assert.strictEqual(validate('', '2026-10-13', false, 0), '', 'People filters retain an open start.');
    assert.strictEqual(validate('2018-01-01', '', false, 0), '', 'People filters retain an open end.');
    assert.strictEqual(validate('2018-01-01', '2026-10-13', false, 0), '', 'People dates do not inherit the activity one-year limit.');
    assert.strictEqual(validate('', '', false, 0), '');
    assert(validate('<script>', '2026-10-13', false, 0).includes('valid dates'));
};
