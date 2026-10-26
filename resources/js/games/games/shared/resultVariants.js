const lastVariants = new Map();

// Remember each result band separately, including across Play again page reloads.
export function chooseResultVariant(tier, count = 5) {
  const key = `musicGames.resultVariant.${tier}`;
  let previous = lastVariants.get(tier);
  try {
    const saved = window.sessionStorage.getItem(key);
    if (saved !== null && /^\d+$/.test(saved)) previous = Number(saved);
  } catch (_) {
    // Storage may be unavailable in a private or restricted browser session.
  }

  const hasPrevious = Number.isInteger(previous) && previous >= 0 && previous < count;
  const choices = count - (hasPrevious && count > 1 ? 1 : 0);
  let next = Math.floor(Math.random() * choices);
  if (hasPrevious && count > 1 && next >= previous) next += 1;
  lastVariants.set(tier, next);

  try {
    window.sessionStorage.setItem(key, String(next));
  } catch (_) {
    // The in-memory fallback still prevents repeats during this page's lifetime.
  }
  return next;
}
