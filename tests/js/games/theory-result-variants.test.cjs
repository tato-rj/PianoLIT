const assert = require('node:assert/strict');
const { test } = require('node:test');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '../../../resources/js/games/games/shared/resultVariants.js'), 'utf8')
    .replace('export function', 'function');

function setup(storage = new Map(), random = Math.random) {
    const context = vm.createContext({
        Math: Object.assign(Object.create(Math), { random }),
        window: { sessionStorage: {
            getItem: key => storage.get(key) ?? null,
            setItem: (key, value) => storage.set(key, value),
        } },
    });
    vm.runInContext(source + '\nglobalThis.choose = chooseResultVariant;', context);
    return context.choose;
}

test('each result band can choose all five variants', () => {
    for (const tier of ['excellent', 'strong', 'encouraging']) {
        const variants = Array.from({ length: 5 }, (_, index) => setup(new Map(), () => (index + 0.5) / 5)(tier));
        assert.deepEqual(variants, [0, 1, 2, 3, 4]);
    }
});

test('Play again reloads never repeat the last variant in the same band', () => {
    const storage = new Map();
    for (const tier of ['excellent', 'strong', 'encouraging']) {
        let previous;
        for (let i = 0; i < 30; i++) {
            // A new module context represents a full page reload; even a fixed RNG must not repeat.
            const next = setup(storage, () => 0)(tier);
            assert.notEqual(next, previous);
            assert.ok(next >= 0 && next < 5);
            previous = next;
        }
    }
});

test('changing bands preserves each bands last variant independently', () => {
    const choose = setup(new Map(), () => 0.7);
    assert.equal(choose('excellent'), 3);
    assert.equal(choose('strong'), 3);
    assert.equal(choose('encouraging'), 3);
    assert.notEqual(choose('excellent'), 3);
    assert.notEqual(choose('strong'), 3);
    assert.notEqual(choose('encouraging'), 3);
});

test('unavailable browser storage still allows results and prevents in-page repeats', () => {
    const blockedStorage = { get() { throw new Error('blocked'); }, set() { throw new Error('blocked'); } };
    const choose = setup(blockedStorage, () => 0);
    assert.equal(choose('excellent'), 0);
    assert.equal(choose('excellent'), 1);
    assert.equal(choose('excellent'), 0);
});

test('invalid saved values cannot select an absent character', () => {
    for (const value of ['broken', '-1', '5', '1.5', '', '999999']) {
        const storage = new Map([['musicGames.resultVariant.excellent', value]]);
        assert.equal(setup(storage, () => 0.999)('excellent'), 4);
    }
});
