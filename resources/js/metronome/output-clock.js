// Map the speaker's audio position onto a monotonic presentation clock.
// Device timestamps are measurements, not animation commands: small changes
// in a reported latency must not make the hand jump backward on every poll.
export class OutputClock {
    reset() { this.sample = null; }

    read(context, milliseconds = performance.now()) {
        const wall = milliseconds / 1000;
        let measured;
        try {
            const stamp = context.getOutputTimestamp?.();
            const age = wall - stamp?.performanceTime / 1000;
            if (stamp?.performanceTime > 0 && Number.isFinite(stamp.contextTime)
                && stamp.contextTime >= 0 && age >= -0.05 && age < 1) {
                measured = stamp.contextTime + age;
            }
        } catch (error) { /* Use reported latency on older browsers. */ }
        if (!Number.isFinite(measured)) {
            const latency = context.outputLatency ?? context.baseLatency ?? 0;
            measured = context.currentTime - (Number.isFinite(latency) && latency > 0 ? latency : 0);
        }
        measured = Math.min(context.currentTime, measured);
        const previous = this.sample;
        let time = measured;
        if (previous && previous.context === context && wall >= previous.wall && wall - previous.wall < 1) {
            const elapsed = wall - previous.wall;
            const predicted = previous.time + elapsed;
            // Slew gently toward a changed device estimate; never seek the
            // visual timeline or let timestamp noise reverse a count.
            const correction = (measured - predicted) * Math.min(1, elapsed / 0.25);
            const limit = elapsed * 0.08;
            time = Math.max(previous.time, predicted + Math.max(-limit, Math.min(limit, correction)));
        }
        this.sample = { context, wall, time };
        return time;
    }
}
