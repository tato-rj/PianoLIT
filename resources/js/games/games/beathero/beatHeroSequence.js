export function buildBeatHeroSequence({
  pool,
  count,
  shuffle,
  previousIds = [],
} = {}) {
  const figures = Array.isArray(pool) ? pool : [];
  const targetCount = Math.max(0, Math.floor(Number(count) || 0));
  if (!figures.length || !targetCount) return [];

  const beats = (figure) => figure.beats ?? 1;
  const answer = [];
  let remaining = targetCount;
  let available = [];
  while (remaining > 0) {
    available = available.filter((figure) => beats(figure) <= remaining);
    if (!available.length) available = shuffle(figures.filter((figure) => beats(figure) <= remaining));
    if (!available.length) return [];
    const figure = available.shift();
    answer.push(figure);
    remaining -= beats(figure);
  }
  const repeatsPrevious = answer.length === previousIds.length
    && answer.every((figure, index) => figure.id === previousIds[index]);

  if (repeatsPrevious) {
    const differentIndex = answer.findIndex((figure) => figure.id !== answer[0].id);
    if (differentIndex > 0) {
      [answer[0], answer[differentIndex]] = [answer[differentIndex], answer[0]];
    } else {
      // A two-card round can be a single half note. Find another exact fit
      // rather than repeating it or exceeding the selected number of beats.
      const alternative = (slots, sequence = []) => {
        if (!slots) return sequence.length !== previousIds.length
          || sequence.some((figure, index) => figure.id !== previousIds[index]) ? sequence : null;
        for (const figure of figures) {
          if (beats(figure) > slots) continue;
          const result = alternative(slots - beats(figure), [...sequence, figure]);
          if (result) return result;
        }
        return null;
      };
      return alternative(targetCount) || answer;
    }
  }

  return answer;
}
