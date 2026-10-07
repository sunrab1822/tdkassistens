<script setup lang="ts">
import AppShell from '@/components/AppShell.vue';
import ChatComposer from '@/components/chat/ChatComposer.vue';
import ChatEmptyState from '@/components/chat/ChatEmptyState.vue';
import ChatMessages from '@/components/chat/ChatMessages.vue';
import ChatSidebar from '@/components/chat/ChatSidebar.vue';
import DocumentsPanel from '@/components/chat/DocumentsPanel.vue';
import { Button } from '@/components/ui/button';
import { SidebarInset, SidebarTrigger } from '@/components/ui/sidebar';
import { type ActiveConversation, type Conversation } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    conversations: Conversation[];
    conversation: ActiveConversation | null;
    documents?: string[];
}>();

const view = ref<'chat' | 'documents'>(new URLSearchParams(window.location.search).get('view') === 'documents' ? 'documents' : 'chat');

const isChatEmpty = computed(() => !props.conversation || props.conversation.messages.length === 0);

watch(
    () => props.conversation?.id,
    () => {
        view.value = 'chat';
    },
);

function toggleDocuments() {
    view.value = view.value === 'documents' ? 'chat' : 'documents';
}

const form = useForm({
    message: '',
});

function send() {
    if (form.processing || !form.message.trim()) {
        return;
    }

    form.post(props.conversation ? route('messages.store', props.conversation.id) : route('conversations.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <Head title="Chat" />

    <AppShell variant="sidebar">
        <ChatSidebar
            :conversations="props.conversations"
            :active-id="view === 'chat' ? props.conversation?.id : undefined"
            :documents-active="view === 'documents'"
            @toggle-documents="toggleDocuments"
            @new-chat="view = 'chat'"
        />

        <SidebarInset class="h-svh overflow-hidden">
            <header class="flex h-14 shrink-0 items-center gap-2 border-b px-4">
                <SidebarTrigger />
                <span class="truncate text-sm font-medium">
                    {{ view === 'documents' ? 'Dokumentumok' : props.conversation?.title || 'Chat' }}
                </span>
                <Button
                    v-if="view === 'documents'"
                    variant="ghost"
                    size="icon"
                    class="ml-auto h-7 w-7"
                    title="Vissza a chathoz"
                    @click="view = 'chat'"
                >
                    <X class="size-4" />
                </Button>
            </header>

            <DocumentsPanel v-if="view === 'documents'" :documents="props.documents" />

            <ChatEmptyState v-else-if="isChatEmpty" :documents="props.documents" :pending="form.processing">
                <ChatComposer v-model="form.message" :processing="form.processing" :error="form.errors.message" @send="send" />
            </ChatEmptyState>

            <template v-else-if="props.conversation">
                <ChatMessages :messages="props.conversation.messages" :pending="form.processing" />
                <div class="mx-auto w-full max-w-3xl px-4 pb-4">
                    <ChatComposer v-model="form.message" :processing="form.processing" :error="form.errors.message" @send="send" />
                </div>
            </template>
        </SidebarInset>
    </AppShell>
</template>
