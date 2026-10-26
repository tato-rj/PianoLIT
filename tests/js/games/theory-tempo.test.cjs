const assert = require('node:assert/strict');
const { test } = require('node:test');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '../../../resources/js/games/games/shared/tempo.js'), 'utf8')
    .replace(/^export const /gm, 'const ')
    .replace(/^export function /gm, 'function ');

const context = vm.createContext({});
vm.runInContext(`${source}\nglobalThis.tempo = { normalizeMetronomeBpm, beatMsForBpm, eighthNoteMsForBpm };`, context);
const { normalizeMetronomeBpm, beatMsForBpm, eighthNoteMsForBpm } = context.tempo;

test('tempo is shared within the Beat Hero selector range', () => {
    assert.equal(normalizeMetronomeBpm('60'), 60);
    assert.equal(normalizeMetronomeBpm(20), 50);
    assert.equal(normalizeMetronomeBpm(220), 160);
    assert.equal(normalizeMetronomeBpm('invalid'), 80);
});

test('snake eighth notes move twice for each metronome quarter note', () => {
    for (const bpm of [50, 60, 80, 120, 160]) {
        const quarterMs = beatMsForBpm(bpm);
        const snakeMs = eighthNoteMsForBpm(bpm);
        assert.equal(snakeMs, quarterMs / 2);
        assert.equal(60000 / snakeMs, bpm * 2);
    }
});
