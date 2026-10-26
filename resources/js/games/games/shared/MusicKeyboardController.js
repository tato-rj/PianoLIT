export function keyboardLabelForLetter({
  letter,
  solfege = "",
  useSolfege = false,
  letterToSolfege = {},
} = {}) {
  const cleanLetter = String(letter || "").trim().toUpperCase();
  if (!cleanLetter) return "";
  if (!useSolfege) return cleanLetter;

  const cleanSolfege = String(solfege || "").trim();
  if (cleanSolfege) {
    return cleanSolfege.charAt(0).toUpperCase() + cleanSolfege.slice(1).toLowerCase();
  }

  return letterToSolfege[cleanLetter] || cleanLetter;
}

export function applyAccidentalToSingleNoteValue(
  current,
  accidentalType,
  {
    useSolfege = false,
    solfegeToLetter = {},
    letterToSolfege = {},
  } = {},
) {
  const m = String(current || "").trim().match(/^([A-G]|Do|Re|Mi|Fa|Sol|La|Si)([#b♯♭𝄪𝄫]{0,2})$/i);
  if (!m) return null;

  const tokenUpper = String(m[1] || "").toUpperCase();
  const letter = solfegeToLetter[tokenUpper] || tokenUpper;
  const baseDisplay = useSolfege
    ? (letterToSolfege[letter] || letter)
    : letter;
  const rawAcc = m[2] || "";
  const normalizedAcc =
    rawAcc === "#" || rawAcc === "♯" ? "sharp" :
    rawAcc === "##" || rawAcc === "𝄪" ? "double_sharp" :
    rawAcc === "b" || rawAcc === "♭" ? "flat" :
    rawAcc === "bb" || rawAcc === "𝄫" ? "double_flat" :
    "";

  if (accidentalType === "sharp") {
    if (normalizedAcc === "double_sharp" || normalizedAcc === "sharp") return `${baseDisplay}𝄪`;
    return `${baseDisplay}♯`;
  }

  if (accidentalType === "flat") {
    if (normalizedAcc === "double_flat" || normalizedAcc === "flat") return `${baseDisplay}𝄫`;
    return `${baseDisplay}♭`;
  }

  return `${baseDisplay}`;
}

export function deleteSingleNoteValue(current) {
  const value = String(current || "").trim();
  if (!value) return "";

  const m = value.match(/^([A-G]|Do|Re|Mi|Fa|Sol|La|Si)([#b♯♭𝄪𝄫]{0,2})$/i);
  if (!m) {
    const chars = Array.from(value);
    return chars.slice(0, -1).join("");
  }

  if (m[2]) return m[1];
  return "";
}

export class MusicKeyboardController {
  constructor({
    namespace,
    $keyboard,
    pushTimeout = null,
  } = {}) {
    this.ns = namespace || "musicKeyboard";
    this.$keyboard = $keyboard || $("#music-keyboard").first();
    this._pushTimeout = typeof pushTimeout === "function" ? pushTimeout : null;
    this._hideTimer = null;
    this._suppressHideUntil = 0;
  }

  static parseButton($button) {
    return {
      letter: String($button.attr("data-lettername") || "").trim().toUpperCase(),
      solfege: String($button.attr("data-solfege") || "").trim(),
      accidentalType: String($button.attr("data-accidental") || "").trim().toLowerCase(),
      isDelete: $button.is("[data-delete]"),
      text: String($button.text() || "").trim(),
    };
  }

  bind({
    $root,
    focusSelector,
    clickSelector = null,
    resolveInputFromClick = null,
    getActiveInput,
    setActiveInput,
    clearActiveInput,
    isManagedInput = null,
    isInputDisabled = null,
    transformButtonPress,
  } = {}) {
    const manages = typeof isManagedInput === "function"
      ? isManagedInput
      : ($input) => !!($input && $input.length);

    const disabled = typeof isInputDisabled === "function"
      ? isInputDisabled
      : ($input) => !!$input?.prop?.("disabled");

    if (clickSelector && $root?.length) {
      $root
        .off(`click.${this.ns}KeyboardActivate`, clickSelector)
        .on(`click.${this.ns}KeyboardActivate`, clickSelector, (e) => {
          const $input = typeof resolveInputFromClick === "function"
            ? resolveInputFromClick($(e.currentTarget), e)
            : $(e.currentTarget).find(focusSelector).first();
          if (!$input || !$input.length) return;
          if (disabled($input)) {
            clearActiveInput?.();
            return;
          }
          setActiveInput?.($input);
          $input.trigger("focus");
          this.show();
        });
    }

    if ($root?.length) {
      $root
        .off(`focusin.${this.ns}Keyboard`, focusSelector)
        .on(`focusin.${this.ns}Keyboard`, focusSelector, (e) => {
          const $input = $(e.currentTarget);
          if (!manages($input)) return;
          setActiveInput?.($input);
          this.show();
        });

      $root
        .off(`focusout.${this.ns}Keyboard`, focusSelector)
        .on(`focusout.${this.ns}Keyboard`, focusSelector, () => {
          this._scheduleHide(() => {
            const $active = $(document.activeElement);
            const inManagedInput = manages($active);
            const inKeyboard = $active.closest("#music-keyboard").length;
            if (!inManagedInput && !inKeyboard) {
              this.hide();
              clearActiveInput?.();
            }
          });
        });

      $root
        .off(`keydown.${this.ns}Keyboard`, focusSelector)
        .on(`keydown.${this.ns}Keyboard`, focusSelector, (e) => {
          if (!manages($(e.currentTarget))) return;
          e.preventDefault();
        });

      $root
        .off(`paste.${this.ns}Keyboard`, focusSelector)
        .on(`paste.${this.ns}Keyboard`, focusSelector, (e) => {
          if (!manages($(e.currentTarget))) return;
          e.preventDefault();
        });
    }

    $(document)
      .off(`mousedown.${this.ns}Keyboard touchstart.${this.ns}Keyboard`)
      .on(`mousedown.${this.ns}Keyboard touchstart.${this.ns}Keyboard`, (e) => {
        const $target = $(e.target);
        if (manages($target.closest(focusSelector))) return;
        if ($target.closest("#music-keyboard").length) {
          this._suppressHideUntil = Date.now() + 250;
          return;
        }
        this.hide();
        clearActiveInput?.();
      });

    this.$keyboard
      .off(`click.${this.ns}KeyboardWrite`, "button")
      .on(`click.${this.ns}KeyboardWrite`, "button", (e) => {
        e.preventDefault();
        const $button = $(e.currentTarget);
        const $active = getActiveInput?.() && getActiveInput().length
          ? getActiveInput()
          : $(document.activeElement).closest(focusSelector);

        if (!$active || !$active.length || !manages($active)) return;
        if (disabled($active)) return;
        if (typeof transformButtonPress !== "function") return;

        const result = transformButtonPress({
          action: MusicKeyboardController.parseButton($button),
          currentValue: String($active.val() || "").trim(),
          $input: $active,
          $button,
        });

        if (result == null) return;
        $active.val(result).trigger("input");
      });
  }

  syncLetterButtonLabels({
    useSolfege = false,
    letterToSolfege = {},
  } = {}) {
    if (!this.$keyboard?.length) return;

    this.$keyboard.find("button[data-lettername]").each((_, el) => {
      const $button = $(el);
      const letter = String($button.attr("data-lettername") || "").trim().toUpperCase();
      const solfege = String($button.attr("data-solfege") || "").trim();
      const label = keyboardLabelForLetter({
        letter,
        solfege,
        useSolfege,
        letterToSolfege,
      });

      if (label) $button.text(label);
    });
  }

  show() {
    if (!this.$keyboard.length) return;
    if (this._hideTimer) {
      clearTimeout(this._hideTimer);
      this._hideTimer = null;
    }
    this.$keyboard
      .stop(true, true)
      .show()
      .removeClass("animate__bounceOutDown")
      .addClass("animate__animated animate__bounceInUp");
  }

  hide() {
    if (!this.$keyboard.length) return;
    if (!this.$keyboard.is(":visible")) return;
    this.$keyboard
      .removeClass("animate__bounceInUp")
      .addClass("animate__animated animate__bounceOutDown");

    const hideTid = setTimeout(() => {
      this.$keyboard.hide();
      this.$keyboard.removeClass("animate__bounceOutDown");
    }, 550);

    if (this._pushTimeout) this._pushTimeout(hideTid);
  }

  _scheduleHide(onHide) {
    if (this._hideTimer) clearTimeout(this._hideTimer);
    this._hideTimer = setTimeout(() => {
      this._hideTimer = null;
      if (Date.now() < this._suppressHideUntil) return;
      if (typeof onHide === "function") onHide();
    }, 0);
  }
}
