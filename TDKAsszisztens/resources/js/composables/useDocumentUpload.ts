import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

export function useDocumentUpload() {
    const fileInput = ref<HTMLInputElement | null>(null);

    const upload = useForm<{ file: File | null }>({
        file: null,
    });

    function openFilePicker() {
        fileInput.value?.click();
    }

    function onFileChange(event: Event) {
        const input = event.target as HTMLInputElement;

        if (!input.files?.length) {
            return;
        }

        upload.file = input.files[0];
        upload.post(route('documents.store'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                upload.reset();
                router.reload({ only: ['documents'] });
            },
            onFinish: () => {
                input.value = '';
            },
        });
    }

    return { fileInput, upload, openFilePicker, onFileChange };
}
