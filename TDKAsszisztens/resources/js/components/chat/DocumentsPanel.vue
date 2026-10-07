<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { useDocumentUpload } from '@/composables/useDocumentUpload';
import { router } from '@inertiajs/vue3';
import { FileText, Loader2, Trash2, Upload } from 'lucide-vue-next';

const props = defineProps<{
    documents?: string[];
}>();

const { fileInput, upload: form, openFilePicker, onFileChange } = useDocumentUpload();

function refresh() {
    router.reload({ only: ['documents'] });
}

function remove(filename: string) {
    if (confirm(`Biztosan törlöd a(z) "${filename}" fájlt a tudásbázisból?`)) {
        router.delete(route('documents.destroy', filename), {
            preserveScroll: true,
            onSuccess: refresh,
        });
    }
}
</script>

<template>
    <div class="flex-1 overflow-y-auto">
        <div class="mx-auto w-full max-w-3xl space-y-4 p-4">
            <div class="flex items-center gap-2">
                <input ref="fileInput" type="file" accept=".pdf,.png,.jpg,.jpeg" class="hidden" @change="onFileChange" />
                <Button variant="outline" size="sm" class="gap-2" :disabled="form.processing" @click="openFilePicker">
                    <Loader2 v-if="form.processing" class="size-4 animate-spin" />
                    <Upload v-else class="size-4" />
                    {{ form.processing ? 'Feldolgozás…' : 'Fájl feltöltése' }}
                </Button>
                <span class="text-xs text-muted-foreground">PDF, PNG, JPG · max. 20 MB</span>
            </div>
            <InputError :message="form.errors.file" />

            <div v-if="props.documents === undefined" class="space-y-2">
                <Skeleton class="h-10 w-full" />
                <Skeleton class="h-10 w-2/3" />
            </div>
            <div v-else-if="props.documents.length === 0" class="text-sm text-muted-foreground">Még nincs feltöltött dokumentum.</div>
            <ul v-else class="divide-y rounded-xl border text-sm">
                <li v-for="document in props.documents" :key="document" class="flex items-center gap-2 px-3 py-2">
                    <FileText class="size-4 shrink-0 text-muted-foreground" />
                    <span class="min-w-0 flex-1 truncate">{{ document }}</span>
                    <button type="button" class="rounded p-1 text-muted-foreground hover:text-destructive" @click="remove(document)">
                        <Trash2 class="size-4" />
                    </button>
                </li>
            </ul>
        </div>
    </div>
</template>
