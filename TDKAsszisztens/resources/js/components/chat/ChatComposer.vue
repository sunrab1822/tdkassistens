<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { useDocumentUpload } from '@/composables/useDocumentUpload';
import { Loader2, Plus, SendHorizonal } from 'lucide-vue-next';

defineProps<{
    processing?: boolean;
    error?: string;
}>();

const emit = defineEmits<{
    (e: 'send'): void;
}>();

const message = defineModel<string>({ default: '' });

const { fileInput, upload, openFilePicker, onFileChange } = useDocumentUpload();

function onKeydown(event: KeyboardEvent) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        emit('send');
    }
}
</script>

<template>
    <div class="space-y-2">
        <InputError :message="error" />
        <InputError :message="upload.errors.file" />
        <div
            class="flex items-end gap-1 rounded-2xl border border-input bg-background p-1.5 shadow-sm ring-offset-background focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2"
        >
            <input ref="fileInput" type="file" accept=".pdf,.png,.jpg,.jpeg" class="hidden" @change="onFileChange" />
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="size-8 shrink-0 rounded-full"
                title="Fájl feltöltése"
                :disabled="upload.processing"
                @click="openFilePicker"
            >
                <Loader2 v-if="upload.processing" class="size-4 animate-spin" />
                <Plus v-else class="size-4" />
            </Button>
            <Textarea
                v-model="message"
                :disabled="processing"
                placeholder="Írj üzenetet…"
                class="max-h-40 min-h-8 flex-1 resize-none border-0 bg-transparent px-2 py-1.5 shadow-none focus-visible:ring-0 focus-visible:ring-offset-0"
                rows="1"
                @keydown="onKeydown"
            />
            <Button type="button" size="icon" class="size-8 shrink-0 rounded-full" :disabled="processing || !message.trim()" @click="emit('send')">
                <SendHorizonal class="size-4" />
            </Button>
        </div>
    </div>
</template>
