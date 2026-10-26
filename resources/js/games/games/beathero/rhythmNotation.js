import { Beam, Dot, Formatter, Renderer, Stave, StaveNote, Stem, Tuplet, Voice } from "vexflow/bravura";

const figures = new Map();

// Engrave each figure once; both cards and thumbnails reuse the SVG.
export function rhythmNotationSvg(figure) {
  if (figures.has(figure.id)) return figures.get(figure.id);

  const container = document.createElement("div");
  const renderer = new Renderer(container, Renderer.Backends.SVG);
  renderer.resize(160, 120);
  const context = renderer.getContext();
  const stave = new Stave(0, 10, 160);
  const notes = figure.notes.map(({ value, dotted }) => {
    const note = new StaveNote({
      keys: ["b/4"],
      duration: `${value}${dotted ? "d" : ""}`,
      stem_direction: Stem.UP,
    });
    if (dotted) Dot.buildAndAttach([note]);
    note.setStyle({ fillStyle: "currentColor", strokeStyle: "currentColor" });
    return note;
  });

  // VexFlow handles secondary hooks and the triplet's rhythmic tick adjustment.
  const beam = notes.length > 1 ? new Beam(notes) : null;
  if (beam) beam.setStyle({ fillStyle: "currentColor", strokeStyle: "currentColor" });
  const tuplet = figure.tuplet ? new Tuplet(notes, figure.tuplet) : null;
  const voice = new Voice({ num_beats: figure.beats, beat_value: 4 }).addTickables(notes);
  new Formatter().joinVoices([voice]).format([voice], notes.length * 26);
  voice.draw(context, stave);
  if (beam) beam.setContext(context).draw();
  if (tuplet) {
    context.save();
    context.setFillStyle("currentColor");
    context.setStrokeStyle("currentColor");
    tuplet.setContext(context).draw();
    context.restore();
  }

  const bounds = notes.map((note) => note.getBoundingBox());
  const left = Math.min(...bounds.map((box) => box.getX()));
  const right = Math.max(...bounds.map((box) => box.getX() + box.getW()));
  const top = Math.min(...bounds.map((box) => box.getY()), tuplet ? tuplet.getYPosition() - 12 : Infinity);
  const bottom = Math.max(...bounds.map((box) => box.getY() + box.getH()));
  const svg = container.querySelector("svg");
  // Shared viewport dimensions preserve the same engraving scale on every card.
  svg.setAttribute("viewBox", `${(left + right) / 2 - 60} ${(top + bottom) / 2 - 43} 120 86`);
  svg.setAttribute("preserveAspectRatio", "xMidYMid meet");
  svg.setAttribute("role", "presentation");
  svg.setAttribute("focusable", "false");
  svg.removeAttribute("width");
  svg.removeAttribute("height");
  svg.removeAttribute("style");
  // Cached inline copies must not duplicate VexFlow's generated document IDs.
  svg.querySelectorAll("[id]").forEach((element) => element.removeAttribute("id"));

  const markup = svg.outerHTML;
  figures.set(figure.id, markup);
  return markup;
}
