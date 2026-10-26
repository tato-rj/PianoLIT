const assert = require('node:assert/strict');
const { test } = require('node:test');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync(require('node:path').join(__dirname, '../../../resources/js/games/leaderboardProfile.js'), 'utf8').replace(/^import .*;\n/gm, '').replace(/^export /gm, '');
const context = vm.createContext({GAME_SETTINGS_KEY: 'pianolit.games.game.settings.v1'});
vm.runInContext(`${source}\nglobalThis.remember = rememberLeaderboardProfile;`, context);

function form(dataset = {}, order = [1, 2]) {
    const username = {value: ''};
    const avatars = order.map(id => ({
        id: `avatar-${id}`, value: `https://theory.test/images/avatars/avatar-${id}.svg`, checked: false,
        closest() { return {querySelector: () => ({classList: {toggle: (_, dimmed) => { this.dimmed = dimmed; }}})}; },
    }));
    return {dataset, username, avatars, elements: {namedItem: () => username}, querySelectorAll: () => avatars};
}
function storage() {
    let value = null;
    return {getItem: () => value, setItem: (_, next) => { value = next; }};
}

test('reset removes the profile and all saved game settings while preserving unrelated browser data', () => {
    const values = new Map([
        ['pianolit.games.leaderboard.profile.v1', JSON.stringify({name: 'Chosen', avatar: 'avatar-2', games: [{name: 'Note Nest', score: 120}]})],
        ['pianolit.games.game.settings.v1', JSON.stringify({'note-nest': {numOfChallenges: 8}, 'beat-hero': {bpm: 90}})],
        ['pianolit.games.note-python.wardrobe.v1', '{"selected":"pickle"}'],
        ['other-setting', 'keep'],
    ]);
    const saved = {getItem: key => values.get(key) ?? null, removeItem: key => values.delete(key)};
    assert.equal(context.deleteLeaderboardProfile(() => saved), true);
    assert.equal(saved.getItem('pianolit.games.leaderboard.profile.v1'), null);
    assert.equal(saved.getItem('pianolit.games.game.settings.v1'), null);
    assert.equal(saved.getItem('pianolit.games.note-python.wardrobe.v1'), null);
    assert.equal(saved.getItem('other-setting'), 'keep');
    const button = avatarButton(), menu = scoreMenu();
    button.parentElement = {querySelector: () => menu};
    context.renderLeaderboardAvatar(button, () => saved);
    assert.equal(button.placeholder.hidden, false);
    assert.equal(menu.intro.hidden, false);
    assert.equal(menu.details.hidden, true);
    assert.equal(context.deleteLeaderboardProfile(() => { throw Error('blocked'); }), false);
});

test('only a successful post saves the chosen name and avatar, then restores them across games', () => {
    const saved = storage();
    const unposted = form();
    unposted.username.value = 'Not posted'; unposted.avatars[0].checked = true;
    context.remember(unposted, () => saved);
    assert.equal(saved.getItem(), null);
    const posted = form({postedName: 'My chosen name', postedAvatar: 'https://theory.test/images/avatars/avatar-2.svg'});
    context.remember(posted, () => saved);
    assert.deepEqual(JSON.parse(saved.getItem()), {name: 'My chosen name', avatar: 'avatar-2'});
    const nextGame = form({}, [2, 1]);
    context.remember(nextGame, () => saved);
    assert.equal(nextGame.username.value, 'My chosen name');
    assert.equal(nextGame.avatars[0].checked, true);
    assert.equal(nextGame.avatars[0].dimmed, false);
    assert.equal(nextGame.avatars[1].dimmed, true);
    const updated = form({postedName: 'New choice', postedAvatar: 'https://theory.test/images/avatars/avatar-1.svg'});
    context.remember(updated, () => saved);
    assert.deepEqual(JSON.parse(saved.getItem()), {name: 'New choice', avatar: 'avatar-1'});
});

test('missing, corrupt, or blocked storage leaves a blank usable form', () => {
    for (const getStorage of [() => storage(), () => ({getItem: () => '{broken'}), () => { throw Error('blocked'); }, () => ({getItem: () => '{"name":"Bad avatar","avatar":"unknown"}'})]) {
        const blank = form();
        assert.doesNotThrow(() => context.remember(blank, getStorage));
        assert.equal(blank.username.value, '');
        assert.equal(blank.avatars.some(avatar => avatar.checked), false);
    }
    assert.doesNotThrow(() => context.remember(form({postedName: 'Chosen', postedAvatar: 'https://theory.test/images/avatars/avatar-1.svg'}), () => { throw Error('blocked'); }));
});

test('remembering does not replace an already entered name or selected avatar', () => {
    const saved = {getItem: () => '{"name":"Previous","avatar":"avatar-2"}'};
    const current = form(); current.username.value = 'Typing now'; current.avatars[0].checked = true;
    context.remember(current, () => saved);
    assert.equal(current.username.value, 'Typing now');
    assert.equal(current.avatars[0].checked, true);
    assert.equal(current.avatars[1].checked, false);
});

function avatarButton() {
    const image = {}, placeholder = {};
    return {image, placeholder, dataset: {avatarBase: 'https://theory.test/images/avatars/'},
        querySelector: selector => selector === '[data-saved-avatar]' ? image : placeholder,
        setAttribute(_, value) { this.label = value; },
    };
}

function scoreForm() {
    const result = form();
    result.metrics = {game: {value: 'Note Nest'}, rounds: {value: '4'}, score: {value: '30'}, accuracy: {value: '90%'}, duration: {value: '00:40'}};
    result.elements.namedItem = name => name === 'username' ? result.username : result.metrics[name];
    result.checkValidity = () => !!result.username.value && result.avatars.some(avatar => avatar.checked);
    result.submissions = 0;
    result.requestSubmit = () => result.submissions++;
    return result;
}

test('a saved profile posts the completed results once using the latest saved name and avatar', () => {
    const result = scoreForm();
    const saved = storage();
    saved.setItem('', JSON.stringify({name: 'Latest name', avatar: 'avatar-2'}));
    result.username.value = 'Old name';
    result.avatars[0].checked = true;
    const metrics = JSON.stringify(result.metrics);
    assert.equal(context.postSavedLeaderboardScore(result, () => saved), true);
    assert.equal(result.username.value, 'Latest name');
    assert.equal(result.avatars[0].checked, false);
    assert.equal(result.avatars[1].checked, true);
    assert.equal(JSON.stringify(result.metrics), metrics);
    assert.equal(result.submissions, 1);
    assert.equal(context.postSavedLeaderboardScore(result, () => saved), true);
    assert.equal(result.submissions, 1);
});

test('first-time, deleted, invalid or unavailable profiles keep the normal leaderboard modal', () => {
    for (const value of [null, '{broken', '{"name":"","avatar":"avatar-1"}', '{"name":"Chosen","avatar":"unknown"}']) {
        const result = scoreForm();
        assert.equal(context.postSavedLeaderboardScore(result, () => ({getItem: () => value})), false);
        assert.equal(result.submissions, 0);
    }
    const result = scoreForm();
    assert.equal(context.postSavedLeaderboardScore(result, () => { throw Error('blocked'); }), false);
    assert.equal(result.submissions, 0);
});

test('saved profiles cannot post missing results or an invalid form', () => {
    const saved = {getItem: () => '{"name":"Chosen","avatar":"avatar-2"}'};
    const missing = scoreForm();
    missing.metrics.score.value = '';
    assert.equal(context.postSavedLeaderboardScore(missing, () => saved), false);
    assert.equal(missing.submissions, 0);
    const invalid = scoreForm();
    invalid.checkValidity = () => false;
    assert.equal(context.postSavedLeaderboardScore(invalid, () => saved), false);
    assert.equal(invalid.submissions, 0);
});

test('the profile icon shows the last successfully posted avatar and changes with a new score', () => {
    const saved = storage(), button = avatarButton();
    context.renderLeaderboardAvatar(button, () => saved);
    assert.equal(button.image.hidden, true);
    assert.equal(button.placeholder.hidden, false);
    for (const id of [2, 1]) {
        context.remember(form({postedName: 'Chosen name', postedAvatar: `https://theory.test/images/avatars/avatar-${id}.svg`}), () => saved);
        context.renderLeaderboardAvatar(button, () => saved);
        assert.equal(button.image.src, `https://theory.test/images/avatars/avatar-${id}.svg`);
        assert.equal(button.image.hidden, false);
        assert.equal(button.placeholder.hidden, true);
        assert.equal(button.label, "Chosen name's avatar");
    }
    saved.setItem('', null);
    context.renderLeaderboardAvatar(button, () => saved);
    assert.equal(button.placeholder.hidden, false);
    assert.equal(button.image.hidden, true);
});

test('the profile icon rejects invalid image choices and tolerates unavailable storage', () => {
    for (const value of ['{broken', '{"avatar":"https://other.test/image"}', '{"avatar":"avatar-79"}', '{"avatar":"avatar-0"}']) {
        const button = avatarButton();
        context.renderLeaderboardAvatar(button, () => ({getItem: () => value}));
        assert.equal(button.image.hidden, true);
        assert.equal(button.image.src, undefined);
    }
    assert.doesNotThrow(() => context.renderLeaderboardAvatar(avatarButton(), () => { throw Error('blocked'); }));
});

test('posted scores keep one best per game through name and avatar changes', () => {
    const saved = storage();
    const post = (game, score, name = 'Chosen') => context.remember(form({postedName: name, postedAvatar: 'https://theory.test/images/avatars/avatar-1.svg', postedGame: game, postedScore: score}), () => saved);
    post('Note Nest', '120');
    post('Note Nest', '85', 'Renamed');
    post('Beat Hero', '0', 'Renamed');
    assert.deepEqual(JSON.parse(saved.getItem()).games, [{name: 'Note Nest', score: 120}, {name: 'Beat Hero', score: 0}]);
    assert.equal(JSON.parse(saved.getItem()).name, 'Renamed');
    post('Note Nest', '140');
    post('Beat Hero', 'not a score');
    assert.deepEqual(JSON.parse(saved.getItem()).games, [{name: 'Note Nest', score: 140}, {name: 'Beat Hero', score: 0}]);
});

function scoreMenu() {
    const name = {}, empty = {}, details = {}, intro = {};
    const rows = ['Note Nest', 'Beat Hero'].map(game => ({dataset: {profileGame: game}, score: {}, querySelector() { return this.score; }}));
    const elements = {'[data-profile-name]': name, '[data-profile-empty]': empty, '[data-profile-details]': details, '[data-profile-intro]': intro};
    return {name, empty, details, intro, rows, querySelector: selector => elements[selector], querySelectorAll: () => rows};
}

test('the default avatar opens an introduction until a profile is saved, and after it is cleared', () => {
    const menu = scoreMenu(), button = avatarButton(), saved = storage();
    button.parentElement = {querySelector: () => menu};
    context.renderLeaderboardAvatar(button, () => saved);
    assert.notEqual(button.disabled, true);
    assert.equal(menu.intro.hidden, false);
    assert.equal(menu.details.hidden, true);
    context.remember(form({postedName: 'Chosen', postedAvatar: 'https://theory.test/images/avatars/avatar-2.svg'}), () => saved);
    context.renderLeaderboardAvatar(button, () => saved);
    assert.equal(menu.intro.hidden, true);
    assert.equal(menu.details.hidden, false);
    assert.equal(menu.name.textContent, 'Chosen');
    saved.setItem('', null);
    context.renderLeaderboardAvatar(button, () => saved);
    assert.notEqual(button.disabled, true);
    assert.equal(menu.intro.hidden, false);
    assert.equal(menu.details.hidden, true);
});

test('the dropdown shows only played games and handles existing profiles without scores', () => {
    const menu = scoreMenu();
    context.renderLeaderboardScores(menu, {name: 'My name', games: [{name: 'Note Nest', score: 120}]});
    assert.equal(menu.name.textContent, 'My name');
    assert.equal(menu.rows[0].hidden, false);
    assert.equal(menu.rows[0].score.textContent, '120');
    assert.equal(menu.rows[1].hidden, true);
    assert.equal(menu.empty.hidden, true);
    context.renderLeaderboardScores(menu, {name: 'Old profile', avatar: 'avatar-1'});
    assert.equal(menu.rows.every(row => row.hidden), true);
    assert.equal(menu.empty.hidden, false);
    context.renderLeaderboardScores(menu, {games: [null, {name: 'Beat Hero', score: 'broken'}]});
    assert.equal(menu.empty.hidden, false);
    assert.equal(menu.name.textContent, '');
});

test('reset remains available with saved settings even before a profile is created', () => {
    const menu = scoreMenu(), button = avatarButton(), reset = {};
    const query = menu.querySelector;
    menu.querySelector = selector => selector === '[data-profile-reset]' ? reset : query(selector);
    button.parentElement = {querySelector: () => menu};
    const values = new Map([['pianolit.games.game.settings.v1', '{"note-nest":{"numOfChallenges":8}}']]);
    const saved = {getItem: key => values.get(key) ?? null, removeItem: key => values.delete(key)};
    context.renderLeaderboardAvatar(button, () => saved);
    assert.equal(menu.intro.hidden, false);
    assert.equal(reset.hidden, false);
    context.deleteLeaderboardProfile(() => saved);
    context.renderLeaderboardAvatar(button, () => saved);
    assert.equal(reset.hidden, true);
});
