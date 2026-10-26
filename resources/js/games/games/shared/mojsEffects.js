export function playBurstConfettiAtElement(targetEl, {
  parentEl = document.body,
  index = 0,
} = {}) {
  const mojs = window.mojs;
  if (!mojs || !targetEl) return;

  const parentRect = parentEl.getBoundingClientRect();
  const rect = targetEl.getBoundingClientRect();
  const x = (rect.left - parentRect.left) + (rect.width / 2);
  const y = (rect.top - parentRect.top) + (rect.height / 2);
  const countBoost = Math.min(18, Math.max(0, Number(index) || 0));

  const burst = new mojs.Burst({
    parent: parentEl,
    left: 0,
    top: 0,
    radius: { 6: 56 },
    angle: 45,
    count: 22 + countBoost,
    zIndex: 5,
    children: {
      radius: "rand(3,6)",
      fill: "#ffe54c",
      scale: { 2: 0, easing: "quad.in" },
      pathScale: [1.8, null],
      degreeShift: [13, null],
      duration: [500, 760],
      easing: "quint.out",
      isForce3d: true,
    },
  });

  burst
    .tune({ x, y })
    .replay();
}

const smokeBurstCache = new WeakMap();

function _getSmokeBurst(parentEl, fill = "black") {
  const mojs = window.mojs;
  if (!mojs || !mojs.Burst || !parentEl) return null;

  if (fill !== "black") {
    const DURATION = 400;
    return new mojs.Burst({
      parent: parentEl,
      left: 0,
      top: 0,
      degree: 0,
      count: 3,
      radius: { 0: 100 },
      children: {
        fill,
        pathScale: "rand(0.5, 1)",
        radius: "rand(12, 15)",
        swirlSize: "rand(10, 15)",
        swirlFrequency: "rand(2, 4)",
        direction: [1, -1],
        duration: `rand(${1 * DURATION}, ${2 * DURATION})`,
        delay: "rand(0, 75)",
        easing: "quad.out",
        isSwirl: true,
        isForce3d: true,
      },
    });
  }

  const cached = smokeBurstCache.get(parentEl);
  if (cached) return cached;

  const DURATION = 400;
  const smoke = new mojs.Burst({
    parent: parentEl,
    left: 0,
    top: 0,
    degree: 0,
    count: 3,
    radius: { 0: 100 },
    children: {
      fill: "black",
      pathScale: "rand(0.5, 1)",
      radius: "rand(12, 15)",
      swirlSize: "rand(10, 15)",
      swirlFrequency: "rand(2, 4)",
      direction: [1, -1],
      duration: `rand(${1 * DURATION}, ${2 * DURATION})`,
      delay: "rand(0, 75)",
      easing: "quad.out",
      isSwirl: true,
      isForce3d: true,
    },
  });

  smokeBurstCache.set(parentEl, smoke);
  return smoke;
}

export function playSmokePuffAtElement(targetEl, { parentEl = document.body, fill = "black" } = {}) {
  const mojs = window.mojs;
  if (!mojs || !targetEl || !parentEl) return;

  const parentRect = parentEl.getBoundingClientRect();
  const rect = targetEl.getBoundingClientRect();
  const x = (rect.left - parentRect.left) + (rect.width / 2);
  const y = (rect.top - parentRect.top) + (rect.height / 2);

  const smoke = _getSmokeBurst(parentEl, fill);
  if (!smoke) return;

  smoke
    .tune({ x, y })
    .generate()
    .replay();
}

export function playSnakeCellBreakBurstAtElement(targetEl, {
  parentEl = document.body,
  index = 0,
} = {}) {
  const mojs = window.mojs;
  if (!mojs || !targetEl || !parentEl) return;

  const parentRect = parentEl.getBoundingClientRect();
  const rect = targetEl.getBoundingClientRect();
  const x = (rect.left - parentRect.left) + (rect.width / 2);
  const y = (rect.top - parentRect.top) + (rect.height / 2);
  const boost = Math.min(4, Math.max(0, Number(index) || 0));

  const yellowShards = new mojs.Burst({
    parent: parentEl,
    left: 0,
    top: 0,
    x,
    y,
    count: 14 + boost,
    radius: { 0: 62 + boost * 9 },
    zIndex: 9,
    children: {
      shape: "rect",
      fill: "#ffe54c",
      radius: "rand(8.5,15.5)",
      pathScale: [1, 0.3],
      degreeShift: "rand(-28,28)",
      duration: "rand(760,1100)",
      delay: "rand(0,85)",
      easing: "quart.out",
      isForce3d: true,
    },
  });

  const blackBits = new mojs.Burst({
    parent: parentEl,
    left: 0,
    top: 0,
    x,
    y,
    count: 18 + boost,
    radius: { 0: 74 + boost * 10 },
    zIndex: 9,
    children: {
      shape: "circle",
      fill: "black",
      radius: "rand(7.2,13.2)",
      pathScale: [1.1, 0.35],
      degreeShift: "rand(-35,35)",
      duration: "rand(820,1200)",
      delay: "rand(0,95)",
      easing: "quint.out",
      isForce3d: true,
    },
  });

  new mojs.Timeline().add(yellowShards, blackBits).play();
}
