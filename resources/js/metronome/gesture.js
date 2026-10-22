export const MIN_TEMPO = 20;
export const MAX_TEMPO = 300;
export const DEFAULT_TEMPO = 120;

export function clampTempo(value) {
    return Math.min(MAX_TEMPO, Math.max(MIN_TEMPO, value));
}

export class TempoGesture {
    constructor(tempo, x, y, time) {
        this.tempo = tempo;
        this.startX = x;
        this.startY = y;
        this.lastY = y;
        this.lastTime = time;
        this.moved = false;
    }

    move(x, y, time) {
        if (Math.hypot(x - this.startX, y - this.startY) >= 6) this.moved = true;
        if (!this.moved) return Math.round(this.tempo);

        const distance = this.lastY - y;
        const elapsed = Math.max(1, time - this.lastTime);
        // Slow movement gives fine control; a quick swipe covers a wider range.
        const speed = Math.abs(distance) / elapsed;
        const sensitivity = 0.2 + Math.min(speed, 2) * 0.3;
        this.tempo = clampTempo(this.tempo + distance * sensitivity);
        this.lastY = y;
        this.lastTime = time;

        return Math.round(this.tempo);
    }
}
