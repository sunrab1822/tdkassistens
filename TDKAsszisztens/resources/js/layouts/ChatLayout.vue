<script setup lang="ts">
import AppShell from '@/components/AppShell.vue';
import ChatSidebar from '@/components/chat/ChatSidebar.vue';
import { Button } from '@/components/ui/button';
import { SidebarInset, SidebarTrigger } from '@/components/ui/sidebar';
import { type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { X } from 'lucide-vue-next';
import { computed } from 'vue';

defineProps<{
    title: string;
}>();

const page = usePage<SharedData>();
const conversations = computed(() => page.props.conversations ?? []);
</script>

<template>
    <AppShell variant="sidebar">
        <ChatSidebar :conversations="conversations" @toggle-documents="router.visit(route('chat.index', { view: 'documents' }))" />

        <SidebarInset class="h-svh overflow-hidden">
            <header class="flex h-14 shrink-0 items-center gap-2 border-b px-4">
                <SidebarTrigger />
                <span class="truncate text-sm font-medium">{{ title }}</span>
                <Button variant="ghost" size="icon" class="ml-auto h-7 w-7" title="Vissza a chathoz" @click="router.visit(route('chat.index'))">
                    <X class="size-4" />
                </Button>
            </header>

            <div class="flex-1 overflow-y-auto">
                <slot />
            </div>
        </SidebarInset>
    </AppShell>
</template>
