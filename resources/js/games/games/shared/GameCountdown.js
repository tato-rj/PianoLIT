import { GameAudio } from "./GameAudio.js";

export class GameCountdown {
  static STEPS = ["Ready", "Set", "Go!"];

  constructor({ element, valueElement } = {}) {
    this.element = typeof element === "string" ? document.querySelector(element) : element;
    this.valueElement = typeof valueElement === "string"
      ? document.querySelector(valueElement)
      : valueElement || this.element?.querySelector("[data-game-countdown-value]") || null;
    this.startButton = this.element?.querySelector("[data-game-countdown-start]") || null;
    this._valueIsExternal = Boolean(valueElement);
    this._originalValueHtml = this.valueElement?.innerHTML || "";
    this._originalAriaLive = this.valueElement?.getAttribute("aria-live");
    this._originalAriaAtomic = this.valueElement?.getAttribute("aria-atomic");
    this._timers = new Set();
    this._startHandler = null;
    this._cancelHandler = null;
  }

  get exists() {
    return Boolean(this.valueElement);
  }

  async prepareAudio() {
    if (!window.Tone) return;

    try {
      await GameAudio.ensureMetronomeAudio();
    } catch (_) {}
  }

  showStart(onStart) {
    this.cancel();

    if (!this.exists || !this.startButton) {
      onStart?.();
      return;
    }

    if (this.element) this.element.hidden = false;
    this._restoreValue();
    this.startButton.hidden = false;

    this._startHandler = (event) => {
      event.preventDefault();
      this._removeStartHandler();
      if (this.startButton !== this.valueElement) this.startButton.hidden = true;
      onStart?.();
    };
    this.startButton.addEventListener("click", this._startHandler, { once: true });
  }

  start({ beatMs = 1000, onComplete, onCancel } = {}) {
    this.cancel();

    const interval = Math.max(1, Number(beatMs) || 1000);
    const duration = GameCountdown.STEPS.length * interval;

    if (!this.exists) {
      this._setTimer(() => onComplete?.(), duration);
      return duration;
    }

    if (this.element) this.element.hidden = false;
    if (this.startButton && this.startButton !== this.valueElement) this.startButton.hidden = true;
    this.valueElement.hidden = false;
    this.valueElement.classList.add("is-counting-down");
    this.valueElement.setAttribute("aria-live", "polite");
    this.valueElement.setAttribute("aria-atomic", "true");

    if (onCancel) {
      this._cancelHandler = (event) => {
        event.preventDefault();
        this.cancel();
        onCancel();
      };
      this.valueElement.addEventListener("click", this._cancelHandler, { once: true });
    }

    GameCountdown.STEPS.forEach((label, index) => {
      const showStep = () => {
        this.valueElement.textContent = label;
        this.valueElement.classList.remove("game-countdown-step-pop");
        void this.valueElement.offsetWidth;
        this.valueElement.classList.add("game-countdown-step-pop");
        GameAudio.playMetronomeClick(label === "Go!");
      };

      if (index === 0) showStep();
      else this._setTimer(showStep, index * interval);
    });

    this._setTimer(() => {
      this._removeCancelHandler();
      this.hide();
      onComplete?.();
    }, duration);

    return duration;
  }

  hide() {
    if (this.element && !this._valueIsExternal) this.element.hidden = true;
    this._restoreValue();
  }

  cancel() {
    this._timers.forEach((timer) => clearTimeout(timer));
    this._timers.clear();
    this._removeStartHandler();
    this._removeCancelHandler();
    this.hide();
  }

  _setTimer(callback, delayMs) {
    const timer = setTimeout(() => {
      this._timers.delete(timer);
      callback();
    }, Math.max(0, delayMs));
    this._timers.add(timer);
    return timer;
  }

  _removeStartHandler() {
    if (this.startButton && this._startHandler) {
      this.startButton.removeEventListener("click", this._startHandler);
    }
    this._startHandler = null;
  }

  _removeCancelHandler() {
    if (this.valueElement && this._cancelHandler) {
      this.valueElement.removeEventListener("click", this._cancelHandler);
    }
    this._cancelHandler = null;
  }

  _restoreValue() {
    if (!this.valueElement) return;

    this.valueElement.innerHTML = this._originalValueHtml;
    this.valueElement.classList.remove("is-counting-down", "game-countdown-step-pop");
    if (!this._valueIsExternal) this.valueElement.hidden = this.valueElement !== this.startButton;

    if (this._originalAriaLive == null) this.valueElement.removeAttribute("aria-live");
    else this.valueElement.setAttribute("aria-live", this._originalAriaLive);
    if (this._originalAriaAtomic == null) this.valueElement.removeAttribute("aria-atomic");
    else this.valueElement.setAttribute("aria-atomic", this._originalAriaAtomic);
  }

}
