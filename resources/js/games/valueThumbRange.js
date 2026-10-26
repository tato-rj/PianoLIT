const RANGE_SELECTOR = ".music-games input[data-value-thumb-range]";

function updateValueThumbRange(range) {
  const wrapper = range.closest(".value-thumb-range");
  const output = wrapper?.querySelector(".value-thumb-range__value");
  if (!wrapper || !output) return;

  const min = Number(range.min);
  const max = Number(range.max);
  const value = Number(range.value);
  const progress = max === min ? 0 : (value - min) / (max - min);
  const thumbWidth = Number(range.dataset.valueThumbWidth || 52);
  const thumbOffset = (thumbWidth / 2) - (progress * thumbWidth);

  output.textContent = range.value;
  output.style.left = `calc(${progress * 100}% + ${thumbOffset}px)`;
}

function initializeValueThumbRanges(root = document) {
  root.querySelectorAll(RANGE_SELECTOR).forEach((range) => {
    updateValueThumbRange(range);

    if (range.dataset.valueThumbRangeReady) return;
    range.dataset.valueThumbRangeReady = "true";
    range.addEventListener("input", () => updateValueThumbRange(range));
    range.addEventListener("change", () => updateValueThumbRange(range));
  });
}

document.addEventListener("DOMContentLoaded", () => initializeValueThumbRanges());
