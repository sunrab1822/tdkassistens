<script setup lang="ts">
import ThinkingOrb from '@/components/ThinkingOrb.vue';
import { useDocumentUpload } from '@/composables/useDocumentUpload';
import { FileText } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    documents?: string[];
    pending?: boolean;
}>();

const { fileInput, upload, openFilePicker, onFileChange } = useDocumentUpload();

const recentDocuments = computed(() => props.documents?.slice(-5) ?? []);
</script>

<template>
    <div class="flex-1 overflow-y-auto">
        <div class="mx-auto flex min-h-full w-full max-w-3xl flex-col items-center justify-center gap-4 p-4">
            <input ref="fileInput" type="file" accept=".pdf,.png,.jpg,.jpeg" class="hidden" @change="onFileChange" />
            <button
                type="button"
                title="Dokumentum feltöltése"
                :disabled="upload.processing"
                class="-translate-y-16 rounded-full transition-transform hover:scale-105 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed"
                @click="openFilePicker"
            >
                <ThinkingOrb
                    :state="upload.processing || props.pending ? 'working' : 'searching'"
                    :size="64"
                    :zoom="1.5"
                    :speed="0.6"
                    aria-label="TDK Asszisztens"
                />
            </button>
            <div class="w-full">
                <slot />
            </div>
            <ul v-if="recentDocuments.length" class="mt-8 w-2/3 space-y-1 rounded-xl border px-4 py-3 text-center text-xs text-muted-foreground">
                <li v-for="document in recentDocuments" :key="document" class="flex items-center justify-center gap-1.5">
                    <FileText class="size-3 shrink-0" />
                    <span class="truncate">{{ document }}</span>
                </li>
            </ul>
            <p v-else class="text-center text-sm text-muted-foreground">Írj egy üzenetet a beszélgetés megkezdéséhez.</p>
        </div>
    </div>
</template>
