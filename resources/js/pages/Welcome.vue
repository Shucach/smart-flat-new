<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

/**
 * The door to the flat, and it names neither the flat nor what is behind it.
 *
 * Anybody who belongs here knows where the sign-in page is, so there is nothing
 * to click and nothing to read: a passer-by learns only that something is
 * running and is listening. Everything moves on transform and opacity alone, so
 * an idle phone left on this page costs the board nothing.
 */

/** Ring radius, seconds per turn, and the direction it turns in. */
const rings = [
    { radius: 62, duration: 46, reverse: false, dash: '2 10' },
    { radius: 96, duration: 74, reverse: true, dash: '2 14' },
    { radius: 132, duration: 104, reverse: false, dash: '2 18' },
    { radius: 168, duration: 148, reverse: true, dash: '1 22' },
];

/** Satellites, each parked on a ring at its own angle and pace. */
const nodes = [
    { radius: 62, duration: 46, delay: 0, size: 3.5, reverse: false },
    { radius: 96, duration: 74, delay: -18, size: 2.5, reverse: true },
    { radius: 96, duration: 74, delay: -52, size: 4, reverse: true },
    { radius: 132, duration: 104, delay: -30, size: 3, reverse: false },
    { radius: 132, duration: 104, delay: -88, size: 2, reverse: false },
    { radius: 168, duration: 148, delay: -66, size: 2.5, reverse: true },
];

/** Signal leaving the core, one every few seconds. */
const pulses = [0, -2.8, -5.6, -8.4];
</script>

<template>
    <Head title="•" />

    <div class="stage bg-background text-foreground">
        <svg
            class="field"
            viewBox="0 0 400 400"
            fill="none"
            aria-hidden="true"
            preserveAspectRatio="xMidYMid meet"
        >
            <defs>
                <radialGradient id="halo" cx="50%" cy="50%" r="50%">
                    <stop
                        offset="0%"
                        stop-color="currentColor"
                        stop-opacity="0.14"
                    />
                    <stop
                        offset="55%"
                        stop-color="currentColor"
                        stop-opacity="0.04"
                    />
                    <stop
                        offset="100%"
                        stop-color="currentColor"
                        stop-opacity="0"
                    />
                </radialGradient>
            </defs>

            <circle cx="200" cy="200" r="190" fill="url(#halo)" />

            <g
                v-for="ring in rings"
                :key="`ring-${ring.radius}`"
                class="spin"
                :class="{ 'spin-reverse': ring.reverse }"
                :style="{ animationDuration: `${ring.duration}s` }"
            >
                <circle
                    cx="200"
                    cy="200"
                    :r="ring.radius"
                    stroke="currentColor"
                    stroke-width="1"
                    stroke-linecap="round"
                    :stroke-dasharray="ring.dash"
                    class="ring"
                />
            </g>

            <g
                v-for="(node, index) in nodes"
                :key="`node-${index}`"
                class="spin"
                :class="{ 'spin-reverse': node.reverse }"
                :style="{
                    animationDuration: `${node.duration}s`,
                    animationDelay: `${node.delay}s`,
                }"
            >
                <circle
                    :cx="200 + node.radius"
                    cy="200"
                    :r="node.size"
                    fill="currentColor"
                    class="node"
                />
            </g>

            <circle
                v-for="(delay, index) in pulses"
                :key="`pulse-${index}`"
                cx="200"
                cy="200"
                r="30"
                stroke="currentColor"
                stroke-width="1"
                class="pulse"
                :style="{ animationDelay: `${delay}s` }"
            />

            <circle
                cx="200"
                cy="200"
                r="20"
                fill="currentColor"
                class="core-glow"
            />
            <circle cx="200" cy="200" r="6" fill="currentColor" class="core" />
        </svg>
    </div>
</template>

<style scoped>
.stage {
    display: flex;
    min-height: 100svh;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.field {
    width: min(88vw, 88svh, 640px);
    height: min(88vw, 88svh, 640px);
}

.spin {
    transform-box: view-box;
    transform-origin: 50% 50%;
    animation-name: spin;
    animation-timing-function: linear;
    animation-iteration-count: infinite;
}

.spin-reverse {
    animation-direction: reverse;
}

.ring {
    opacity: 0.28;
}

.node {
    opacity: 0.65;
}

.pulse {
    transform-box: view-box;
    transform-origin: 50% 50%;
    animation: pulse 11.2s cubic-bezier(0.2, 0.6, 0.3, 1) infinite;
}

.core {
    transform-box: view-box;
    transform-origin: 50% 50%;
    animation: breathe 5.6s ease-in-out infinite;
}

.core-glow {
    transform-box: view-box;
    transform-origin: 50% 50%;
    opacity: 0.09;
    animation: breathe 5.6s ease-in-out infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

@keyframes pulse {
    0% {
        transform: scale(0.35);
        opacity: 0;
    }
    12% {
        opacity: 0.34;
    }
    100% {
        transform: scale(6.2);
        opacity: 0;
    }
}

@keyframes breathe {
    0%,
    100% {
        transform: scale(1);
        opacity: 0.85;
    }
    50% {
        transform: scale(1.22);
        opacity: 0.45;
    }
}

/*
  Everything here is decoration, so a viewer who asked for less motion is shown
  the same picture standing still rather than a stripped-down one.
*/
@media (prefers-reduced-motion: reduce) {
    .spin,
    .pulse,
    .core,
    .core-glow {
        animation: none;
    }

    .pulse {
        opacity: 0.12;
        transform: scale(3);
    }
}
</style>
