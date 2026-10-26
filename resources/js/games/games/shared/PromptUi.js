export class PromptUi {
  constructor(rootSelector = "#prompt") {
    this.$root = $(rootSelector).first();
    this.$short = $("#prompt-short");
    if (!this.$short.length) this.$short = this.$root.find("label").first();
    this.$direction = $("#prompt-direction");
    if (!this.$direction.length) this.$direction = this.$root.find("i").first();
    this.$long = $("#prompt-long");
    if (!this.$long.length) this.$long = this.$root.find("div").last();
  }

  show() {
    this.$root.show();
    return this;
  }

  hide() {
    this.$root.hide();
    return this;
  }

  setShort(value, { html = true } = {}) {
    if (!this.$short.length) return this;
    if (html) this.$short.html(value ?? "");
    else this.$short.text(value ?? "");
    return this;
  }

  getShortText() {
    return this.$short.length ? this.$short.text() : "";
  }

  setLong(value, { html = true } = {}) {
    if (!this.$long.length) return this;
    if (html) this.$long.html(value ?? "");
    else this.$long.text(value ?? "");
    return this;
  }

  clearLong() {
    if (this.$long.length) this.$long.html("");
    return this;
  }

  showDirection(direction = 1) {
    if (!this.$direction.length) return this;
    this.$direction
      .show()
      .removeClass("fa-up-long fa-down-long")
      .addClass(Number(direction) === -1 ? "fa-down-long" : "fa-up-long");
    return this;
  }

  hideDirection() {
    if (this.$direction.length) this.$direction.hide();
    return this;
  }

  setTone(color = "blue") {
    this.$root.removeClass("incorrect");
    if (color === "red" || color === "incorrect") this.$root.addClass("incorrect");
    return this;
  }
}
