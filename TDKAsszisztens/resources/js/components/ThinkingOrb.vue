<script setup lang="ts">
import type { OrbSize, OrbState } from 'thinking-orbs/engine';
import { MODE_FRAMES, paintFrame, resolvePreset } from 'thinking-orbs/engine';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

type OrbTheme = 'auto' | 'dark' | 'light';

const props = withDefaults(
    defineProps<{
        state?: OrbState;
        size?: OrbSize;
        zoom?: number;
        theme?: OrbTheme;
        speed?: number;
        paused?: boolean;
        ariaLabel?: string;
    }>(),
    {
        state: 'working',
        size: 64,
        zoom: 1,
        theme: 'auto',
        speed: 1,
        paused: false,
    },
);

const displaySize = computed(() => props.size * props.zoom);

const LABELS: Record<OrbState, string> = {
    working: 'Dolgozik…',
    searching: 'Keres…',
    solving: 'Megold…',
    listening: 'Figyel…',
    connecting: 'Kapcsolódik…',
    weaving: 'Fon…',
    composing: 'Ír…',
    breathing: 'Gondolkodik…',
    shaping: 'Alakít…',
};

const canvas = ref<HTMLCanvasElement | null>(null);
const dark = ref(true);
const reduced = ref(false);

let stopEngine: (() => void) | null = null;
let cleanupListeners: (() => void) | null = null;

function ancestorTheme(el: HTMLElement | null): boolean | null {
    let node = el;

    while (node) {
        const attr = node.getAttribute('data-theme');
        if (attr === 'dark') {
            return true;
        }
        if (attr === 'light') {
            return false;
        }
        if (node.classList.contains('dark')) {
            return true;
        }
        if (node.classList.contains('light')) {
            return false;
        }
        node = node.parentElement;
    }

    return null;
}

function resolveDark() {
    if (props.theme === 'dark') {
        dark.value = true;
        return;
    }
    if (props.theme === 'light') {
        dark.value = false;
        return;
    }
    dark.value = ancestorTheme(canvas.value) ?? window.matchMedia('(prefers-color-scheme: dark)').matches;
}

function startEngine() {
    stopEngine?.();
    stopEngine = null;

    const el = canvas.value;
    if (!el) {
        return;
    }

    const size = props.size;
    const zoom = props.zoom;
    const dpr = Math.min(2, window.devicePixelRatio || 1);
    el.width = Math.round(size * zoom * dpr);
    el.height = Math.round(size * zoom * dpr);

    const ctx = el.getContext('2d');
    if (!ctx) {
        return;
    }

    const { mode, speed: baseSpeed, opts } = resolvePreset(props.state, size);
    const frameFn = MODE_FRAMES[mode];
    const effSpeed = baseSpeed * props.speed;

    const frame = (tSec: number) => {
        ctx.setTransform(dpr * zoom, 0, 0, dpr * zoom, 0, 0);
        ctx.clearRect(0, 0, size, size);
        paintFrame(ctx, frameFn(size, tSec, opts), dark.value);
    };

    if (reduced.value) {
        frame(0.6);
        return;
    }

    let raf = 0;
    let running = false;

    const loop = () => {
        frame((performance.now() / 1000) * effSpeed);
        if (running) {
            raf = requestAnimationFrame(loop);
        }
    };
    const start = () => {
        if (running || props.paused) {
            return;
        }
        running = true;
        raf = requestAnimationFrame(loop);
    };
    const stop = () => {
        running = false;
        cancelAnimationFrame(raf);
    };

    frame((performance.now() / 1000) * effSpeed);

    let visible = true;
    const io =
        typeof IntersectionObserver !== 'undefined'
            ? new IntersectionObserver(([entry]) => {
                  visible = entry.isIntersecting;
                  if (visible && document.visibilityState !== 'hidden') {
                      start();
                  } else {
                      stop();
                  }
              })
            : null;
    io?.observe(el);

    const onVis = () => {
        if (document.visibilityState === 'hidden') {
            stop();
        } else if (visible) {
            start();
        }
    };
    document.addEventListener('visibilitychange', onVis);

    if (!io) {
        start();
    }

    stopEngine = () => {
        stop();
        io?.disconnect();
        document.removeEventListener('visibilitychange', onVis);
    };
}

onMounted(() => {
    const mqDark = window.matchMedia('(prefers-color-scheme: dark)');
    const mqReduced = window.matchMedia('(prefers-reduced-motion: reduce)');

    resolveDark();
    reduced.value = mqReduced.matches;

    mqDark.addEventListener('change', resolveDark);

    const onReduced = (e: MediaQueryListEvent) => {
        reduced.value = e.matches;
    };
    mqReduced.addEventListener('change', onReduced);

    const observer = new MutationObserver(resolveDark);
    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class', 'data-theme'],
        subtree: true,
    });

    cleanupListeners = () => {
        mqDark.removeEventListener('change', resolveDark);
        mqReduced.removeEventListener('change', onReduced);
        observer.disconnect();
    };

    startEngine();
});

watch(() => [props.state, props.size, props.zoom, props.speed, props.paused, dark.value, reduced.value], startEngine, { flush: 'post' });

onBeforeUnmount(() => {
    stopEngine?.();
    cleanupListeners?.();
});
</script>

<template>
    <canvas
        ref="canvas"
        role="img"
        :aria-label="ariaLabel ?? LABELS[props.state]"
        :style="{ width: `${displaySize}px`, height: `${displaySize}px`, display: 'block' }"
    />
</template>
