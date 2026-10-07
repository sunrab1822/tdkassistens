<script setup lang="ts">
import { useAppearance, type Appearance } from '@/composables/useAppearance';
import { Monitor, Moon, Sun } from 'lucide-vue-next';
import type { Component } from 'vue';

interface Props {
    class?: string;
}

const { class: containerClass = '' } = defineProps<Props>();

const { appearance, updateAppearance } = useAppearance();

const tabs: { value: Appearance; label: string; Icon?: Component; swatch?: string }[] = [
    { value: 'light', Icon: Sun, label: 'Light' },
    { value: 'dark', Icon: Moon, label: 'Dark' },
    { value: 'system', Icon: Monitor, label: 'System' },
    { value: 'violet-light', label: 'Violet', swatch: 'bg-gradient-to-br from-violet-300 to-indigo-500' },
    { value: 'violet-dark', label: 'Violet Dark', swatch: 'bg-gradient-to-br from-indigo-950 to-violet-500' },
];
</script>

<template>
    <div :class="['inline-flex flex-wrap gap-1 rounded-lg bg-muted p-1', containerClass]">
        <button
            v-for="{ value, Icon, label, swatch } in tabs"
            :key="value"
            @click="updateAppearance(value)"
            :class="[
                'flex items-center rounded-md px-3.5 py-1.5 transition-colors',
                appearance === value
                    ? 'bg-card text-foreground shadow-sm'
                    : 'text-muted-foreground hover:bg-accent/60 hover:text-accent-foreground',
            ]"
        >
            <component :is="Icon" v-if="Icon" class="-ml-1 h-4 w-4" />
            <span v-else :class="['-ml-1 h-4 w-4 rounded-full ring-1 ring-black/10 dark:ring-white/20', swatch]" />
            <span class="ml-1.5 text-sm">{{ label }}</span>
        </button>
    </div>
</template>
