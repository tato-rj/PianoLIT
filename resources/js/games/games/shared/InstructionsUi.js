export class InstructionsUi {
  constructor(rootSelector = "#instructions") {
    this.$root = $(rootSelector).first();
    this.$content = this.$root.find("h6").first();
    if (!this.$content.length) this.$content = this.$root;
    this._typed = null;
    this._lastHtml = this.$content.html() || "";
  }

  show() {
    this.$root.show();
    return this;
  }

  hide() {
    this.$root.hide();
    return this;
  }

  setHtml(value, { animate = true } = {}) {
    if (!this.$content.length) return this;

    const html = String(value ?? "");
    this._lastHtml = html;
    this._destroyTyped();
    this.$content.html("");

    if (!html) return this;

    const shouldAnimate = animate && typeof window.Typed === "function";
    if (!shouldAnimate) {
      this.$content.html(html);
      return this;
    }

    const $typedTarget = $("<span></span>").addClass("instructions__typed");
    this.$content.append($typedTarget);

    this._typed = new window.Typed($typedTarget[0], {
      strings: [html],
      typeSpeed: 24,
      startDelay: 180,
      showCursor: true,
      contentType: "html",
    });

    return this;
  }

  replay() {
    return this.setHtml(this._lastHtml);
  }

  getHtml() {
    return this._lastHtml;
  }

  destroy() {
    this._destroyTyped();
    return this;
  }

  _destroyTyped() {
    if (!this._typed?.destroy) return;
    this._typed.destroy();
    this._typed = null;
  }
}
