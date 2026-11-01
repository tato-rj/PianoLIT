// Traced from the four supplied conducting-pattern screenshots.
// Motion follows the reference video's flowing half-beat gestures: ease through
// turns, carry momentum between them, and soften the top/bottom reversals.
// The four-beat side loops mirror each other around the center downstroke.
export const CONDUCTING_PATTERNS = {
    1: [
        {
            point: [200, 540],
            curves: [
                { to: [208.85, 384.54], controls: [[173.1, 508.74], [200.34, 423.2]] },
                { to: [235.85, 210.89], controls: [[221.33, 327.78], [235.85, 270.11]] },
                { to: [200.83, 36], controls: [[235.85, 153.6], [211.94, 91.77]] },
                { to: [200, 540], controls: [[215, 204.22], [213.91, 372.13]] },
            ],
        },
    ],
    2: [
        {
            point: [200, 540],
            curves: [
                // Match the three-beat turn's rounded, horizontal crest.
                { to: [247, 343], controls: [[312.81, 572.13], [301, 343]] },
                { to: [178.55, 416.85], controls: [[212.81, 343], [239.55, 434.83]] },
                { to: [218.96, 256.19], controls: [[216.22, 388.02], [218.96, 301.19]] },
                { to: [200, 36], controls: [[218.96, 183.16], [211.01, 108.19]] },
            ],
        },
        {
            point: [200, 36],
            curves: [{ to: [200, 540], controls: [[200, 204], [200, 372]] }],
        },
    ],
    3: [
        {
            point: [200, 540],
            curves: [
                { to: [203.83, 326.33], controls: [[175.99, 514.59], [162.56, 326.33]] },
                { to: [304.96, 450.7], controls: [[226.88, 326.33], [261.71, 450.7]] },
                // A shared horizontal tangent rounds the right-hand reversal.
                { to: [354, 299], controls: [[349.3, 450.7], [402, 299]] },
            ],
        },
        {
            point: [354, 299],
            curves: [
                { to: [306.18, 406.25], controls: [[313.64, 299], [337.04, 406.25]] },
                { to: [200, 36], controls: [[215.55, 406.25], [197.12, 94.21]] },
            ],
        },
        {
            point: [200, 36],
            curves: [{ to: [200, 540], controls: [[200, 204], [200, 372]] }],
        },
    ],
    4: [
        {
            point: [200, 540],
            curves: [
                { to: [208.28, 402.99], controls: [[200, 481.8], [202.76, 453.12]] },
                { to: [200.57, 305.53], controls: [[209.56, 391.32], [223.13, 305.53]] },
                { to: [63.15, 464.16], controls: [[171.7, 305.53], [96.35, 464.16]] },
            ],
        },
        {
            point: [63.15, 464.16],
            curves: [
                { to: [79.46, 303.09], controls: [[17.47, 464.16], [32.63, 303.09]] },
                { to: [336.85, 464.16], controls: [[129.84, 303.09], [303.65, 464.16]] },
            ],
        },
        {
            point: [336.85, 464.16],
            curves: [
                { to: [320.54, 303.09], controls: [[382.53, 464.16], [367.37, 303.09]] },
                { to: [235, 384], controls: [[270.16, 303.09], [255, 384]] },
                { to: [200, 36], controls: [[210, 384], [204.69, 88.9]] },
            ],
        },
        {
            point: [200, 36],
            curves: [{ to: [200, 540], controls: [[200, 204], [200, 372]] }],
        },
    ],
};

function curvePoint(start, curve, progress) {
    const t = Math.min(1, Math.max(0, progress));
    const u = 1 - t;
    return start.map((value, axis) =>
        u ** 3 * value + 3 * u ** 2 * t * curve.controls[0][axis]
        + 3 * u * t ** 2 * curve.controls[1][axis] + t ** 3 * curve.to[axis]);
}

const ARC_SAMPLES = 1024;

function measureStroke(segment) {
    const samples = [{ point: segment.point, distance: 0 }];
    const curveEnds = [];
    let start = segment.point;
    let previous = start;
    let length = 0;
    for (const curve of segment.curves) {
        // Cache a dense arc-length map once, rather than timing individual
        // Bézier parameters: equal parameter steps do not cover equal distances.
        for (let step = 1; step <= ARC_SAMPLES; step++) {
            const point = curvePoint(start, curve, step / ARC_SAMPLES);
            length += Math.hypot(point[0] - previous[0], point[1] - previous[1]);
            samples.push({ point, distance: length, start, curve, parameter: step / ARC_SAMPLES });
            previous = point;
        }
        curveEnds.push(length);
        start = curve.to;
    }
    return { samples, curveEnds, length };
}

// Each pair marks the curve ending a half-beat gesture and its through-speed.
// Construction joins along a stroke do not get their own timing or easing.
// Zero is reserved for sharp reversals; rounded turns keep moving.
const FLOW_GESTURES = {
    1: [[2, 0], [3, 0]],
    2: [[0, 0.5], [1, 0], [3, 0], [4, 0]],
    3: [[0, 0.5], [1, 0.65], [2, 0.5], [3, 0.65], [4, 0], [5, 0]],
    4: [[1, 0.5], [2, 0.65], [3, 0.65], [4, 0.65], [5, 0.5], [6, 0.65], [7, 0], [8, 0]],
};

const conductingMotion = Object.fromEntries(Object.entries(CONDUCTING_PATTERNS)
    .map(([beats, pattern]) => {
        const motion = measureStroke({
            point: pattern[0].point,
            curves: pattern.flatMap(segment => segment.curves),
        });
        const timing = [{ time: 0, distance: 0, speed: 0 },
            ...FLOW_GESTURES[beats].map(([curve, speed], index) => ({
                time: (index + 1) / 2, distance: motion.curveEnds[curve], speed,
            }))];
        timing.forEach((knot, index) => {
            if (!knot.speed) return;
            const before = timing[index - 1];
            const after = timing[index + 1];
            const incoming = (knot.distance - before.distance) / (knot.time - before.time);
            const outgoing = (after.distance - knot.distance) / (after.time - knot.time);
            // A limited harmonic-mean tangent keeps distance monotonic and
            // shares exactly the same velocity on both sides of each turn.
            knot.speed *= 2 * incoming * outgoing / (incoming + outgoing);
        });
        return [beats, { beats: Number(beats), ...motion, timing }];
    }));

function distanceAtTime(motion, time) {
    if (time <= 0) return 0;
    if (time >= motion.beats) return motion.length;
    const index = Math.min(Math.floor(time * 2), motion.timing.length - 2);
    const start = motion.timing[index];
    const end = motion.timing[index + 1];
    const duration = end.time - start.time;
    const t = (time - start.time) / duration;
    // Cubic Hermite interpolation eases distance, not the Bézier parameter.
    // Unlike separate ease-in/out animations, rounded joins never stop or
    // restart; both adjacent gestures use the same tangent speed.
    return (2 * t ** 3 - 3 * t ** 2 + 1) * start.distance
        + (t ** 3 - 2 * t ** 2 + t) * duration * start.speed
        + (-2 * t ** 3 + 3 * t ** 2) * end.distance
        + (t ** 3 - t ** 2) * duration * end.speed;
}

function pointAtDistance({ samples, length }, distance) {
    if (distance <= 0 || length === 0) return samples[0].point;
    if (distance >= length) return samples[samples.length - 1].point;
    let low = 1;
    let high = samples.length - 1;
    while (low < high) {
        const middle = Math.floor((low + high) / 2);
        if (samples[middle].distance < distance) low = middle + 1;
        else high = middle;
    }
    const before = samples[low - 1];
    const after = samples[low];
    if (distance === after.distance) return after.point;
    const fraction = (distance - before.distance) / (after.distance - before.distance);
    // Use the length map to recover a parameter, then evaluate the actual
    // curve. Interpolating the sample positions instead creates tiny straight
    // chords and a small direction change at every sample boundary.
    return curvePoint(after.start, after.curve, after.parameter - (1 - fraction) / ARC_SAMPLES);
}

export function conductorPoint(beats, beat, progress) {
    const motion = conductingMotion[beats] || conductingMotion[1];
    const index = ((beat % motion.beats) + motion.beats) % motion.beats;
    const t = Math.min(1, Math.max(0, progress));
    const distance = distanceAtTime(motion, index + t);
    return pointAtDistance(motion, distance);
}

export function conductorPreparationPoint(beats, progress) {
    const motion = conductingMotion[beats] || conductingMotion[1];
    const t = Math.min(1, Math.max(0, progress));
    return pointAtDistance(motion, distanceAtTime(motion, motion.beats - 0.5 + t * 0.5));
}

export function conductorPreparationBeats() {
    // The reference uses half a beat for the top-to-bottom gesture. Starting
    // playback uses that same gesture, reaching the bottom on the first click.
    return 0.5;
}

function ballTransform([x, y]) {
    // The HTML ball is 44 viewBox units wide (11% of the 400-unit stage).
    // Percentage transforms keep it aligned with the SVG at any screen size,
    // without measuring layout when the viewport or device orientation changes.
    return `translate3d(${(x / 44 - 0.5) * 100}%, ${(y / 44 - 0.5) * 100}%, 0)`;
}

export class ConductorView {
    constructor(stage, { pulseLayer, number }) {
        this.path = stage.querySelector('[data-conductor-path]');
        this.ball = stage.querySelector('[data-conductor-ball]');
        this.beatNumbers = Array.from(stage.querySelectorAll('[data-conductor-count]'));
        this.pulseLayer = pulseLayer;
        this.number = number;
        this.beats = 1;
        this.setBeats(1);
    }

    setBeats(beats) {
        this.stop();
        this.beats = beats;
        const pattern = CONDUCTING_PATTERNS[beats];
        const segments = pattern.flatMap(segment => segment.curves.map(curve =>
            `C ${curve.controls[0].join(' ')} ${curve.controls[1].join(' ')} ${curve.to.join(' ')}`
        ));
        this.path.setAttribute('d', `M ${pattern[0].point.join(' ')} ${segments.join(' ')}`);
        this.prepare(0);
    }

    render(beat, progress) {
        this.position(conductorPoint(this.beats, beat, progress));
    }

    prepare(progress) {
        this.position(conductorPreparationPoint(this.beats, progress));
    }

    position([x, y]) {
        this.ball.style.transform = ballTransform([x, y]);
    }

    stop() {
        this.pulseLayer.style.opacity = '0';
        this.number.style.transform = '';
        this.number.style.opacity = '';
        this.beatNumbers.forEach(number => { number.style.opacity = '0'; });
    }

    sync(position, tempo, active, showCounts = true) {
        // One audio position owns every visible part of this frame. There are
        // no independently running animations to seek, rewind or drift apart.
        if (showCounts) {
            if (position.preparing) this.prepare(position.progress);
            else this.render(position.beat, position.progress);
        }
        if (!active || position.preparing || position.waiting) {
            this.stop();
            return;
        }
        const progress = position.progress;
        // Smooth ease-out, evaluated from audio time rather than callback age.
        const fade = (1 - progress) ** 3;
        const pulse = (1 - Math.min(1, progress * 60000 / tempo / 180)) ** 3;
        this.pulseLayer.style.opacity = String(position.beat === 0 ? fade : 0);
        this.number.style.transform = `scale(${1 + 0.35 * pulse})`;
        this.number.style.opacity = String(0.88 + 0.12 * pulse);
        this.beatNumbers.forEach((number, beat) => {
            number.style.opacity = String(showCounts && beat === position.beat ? 0.12 * fade : 0);
        });
    }
}
