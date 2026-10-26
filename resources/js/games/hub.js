require('./animate');
window.CountUp = require('countup.js');
window.mojs = require('@mojs/core');
window.Typed = require('typed.js').default || require('typed.js');
window.Confetti = require('canvas-confetti').default;
// PianoLIT already supplies jQuery, Bootstrap, axios, draggable and common helpers.
const { rememberLeaderboardProfile, renderLeaderboardAvatar, deleteLeaderboardProfile, postSavedLeaderboardScore, STORAGE_KEY: leaderboardProfileKey } = require('./leaderboardProfile');
const { rememberGameSettings, refreshGameCards, GAME_SETTINGS_KEY } = require('./gameSettingsMemory');

document.addEventListener("touchstart", () => {}, { passive: true });

$(window).on('load', function() {
    $('.modal.modal-autoshow').modal('show');
});

$(function () {
    const gameSettings = document.querySelector('[data-game-settings]');
    if (gameSettings) {
        try { rememberGameSettings(gameSettings.dataset.gameSettings, JSON.parse(gameSettings.dataset.gameOptions)); } catch (_) { /* Keep play available without saved settings. */ }
    }
    refreshGameCards();
    window.addEventListener('pageshow', () => refreshGameCards());
    window.addEventListener('storage', event => {
        if (event.key === GAME_SETTINGS_KEY || event.key === null) refreshGameCards();
    });
    document.querySelectorAll('form[data-leaderboard-profile]').forEach(form => rememberLeaderboardProfile(form));
    const joinLeaderboard = document.querySelector('[data-join-leaderboard]');
    const scoreForm = document.querySelector('#save-results-modal form[data-leaderboard-profile]');
    if (joinLeaderboard && scoreForm) {
        $(scoreForm.closest('.modal')).on('show.bs.modal.leaderboardProfile', event => {
            if (event.relatedTarget !== joinLeaderboard) return;
            if (!postSavedLeaderboardScore(scoreForm)) return;
            event.preventDefault();
            joinLeaderboard.disabled = true;
        });
        window.addEventListener('pageshow', () => {
            delete scoreForm.dataset.profilePosting;
            joinLeaderboard.disabled = false;
        });
    }
    const userAvatar = document.getElementById('user-avatar');
    if (userAvatar) {
        const refreshAvatar = () => {
            if (userAvatar.getAttribute('aria-expanded') === 'true') $(userAvatar).dropdown('hide');
            renderLeaderboardAvatar(userAvatar);
        };
        refreshAvatar();
        userAvatar.parentElement.querySelector('[data-profile-delete]').addEventListener('click', () => {
            if (!window.confirm('Reset your name, avatar, scores and all game settings on this browser?')) return;
            if (deleteLeaderboardProfile()) {
                refreshGameCards();
                refreshAvatar();
            }
            else window.alert('Could not delete your records. Please try again.');
        });
        window.addEventListener('pageshow', refreshAvatar);
        window.addEventListener('storage', event => {
            if (event.key === leaderboardProfileKey || event.key === GAME_SETTINGS_KEY || event.key === null) refreshAvatar();
        });
    }

    $('.leaderboard-modal').on('show.bs.modal', function () {
        var $players = $(this).find('.leaderboard-player');

        $players.stop(true, true).css({
            opacity: 0,
            top: '6px',
            position: 'relative'
        });

        $players.each(function (index) {
            $(this).delay(index * 100).animate({
                opacity: 1,
                top: 0
            }, 180);
        });
    });
});

$(document).on('change', '[name$="-leaderboard-range"]', function() {
    let url = $(this).data('url');
    let $players = $(this).closest('.leaderboard-wrapper').find('.leaderboard-players');

    axios.get(url)
        .then(function(response) {
            $players.html(response.data);
        })
        .catch(function() {
            alert('Something went wrong...');
        });
});


document.addEventListener("DOMContentLoaded", () => {
  const ranges = document.querySelectorAll('input[type="range"][data-range-labels]');

  for (const range of ranges) {
    const wrap = range.closest(".mb-4") ?? range.parentElement;

    const labelsSel = range.dataset.rangeLabels; // e.g. "#speedLabels"
    const labelsWrap =
      (labelsSel ? wrap?.querySelector(labelsSel) : null)
      ?? (labelsSel ? document.querySelector(labelsSel) : null)
      ?? (labelsSel?.startsWith("#") && range.nextElementSibling?.id === labelsSel.slice(1)
        ? range.nextElementSibling
        : null);

    if (!labelsWrap) continue;

    const labels = Array.from(labelsWrap.querySelectorAll("label"));

    const updateLabels = () => {
      const idx = Number(range.value);
      labels.forEach((label, i) => {
        label.classList.toggle("text-black", i === idx);
        label.classList.toggle("text-light", i !== idx);
      });
    };

    updateLabels();
    range.addEventListener("input", updateLabels);
    range.addEventListener("change", updateLabels);
  }
});

$('.form-number button').on('click', function () {
  const $input = $(this).siblings('input');
  const current = parseInt($input.val(), 10) || 0;

  if ($(this).data('direction') === 'down' && current > $input.attr('min')) {
    $input.val(current - 1);
  } else if ($(this).data('direction') === 'up' && current < $input.attr('max')) {
    $input.val(current + 1);
  }
});

$(document).on('click', '.avatar-picker .player-avatar', function () {
    const $clickedAvatar = $(this);
    const $option = $clickedAvatar.closest('.avatar-option');
    const $container = $clickedAvatar.closest('.avatar-picker');
    const $radio = $option.find('input[type="radio"][name="avatar_url"]');

    if (!$radio.length) return;

    $radio.prop('checked', true).trigger('change');

    $container.find('.player-avatar').addClass('opacity-2');
    $clickedAvatar.removeClass('opacity-2');
});

// /public/js/settings-toggles.js (example path)
// settings/toggles.js
$(document)
  .off("change.settingsToggles")
  .on(
    "change.settingsToggles",
    'input[multichoice], input[singlechoice]',
    function () {
      const $this = $(this);
      const isSingle = $this.is("[singlechoice]");
      const name = $this.attr("name");

      if (isSingle && $this.prop("checked")) {
        $(`input[singlechoice][name="${CSS.escape(name)}"]`)
          .not(this)
          .prop("checked", false);
      }

      const $scope = isSingle
        ? $(`input[singlechoice][name="${CSS.escape(name)}"]`)
        : $this;

      $scope.each(function () {
        const $label = $(this).siblings("label");
        $label.removeClass("btn-white btn-secondary");
        $label.addClass(this.checked ? "btn-secondary" : "btn-white");
      });
    }
  );

$(document).on('click', 'button[data-form]', function() {
    let formID = $(this).data('form');
    $('form'+formID).submit();
});


require('./duel/DuelLobby');
