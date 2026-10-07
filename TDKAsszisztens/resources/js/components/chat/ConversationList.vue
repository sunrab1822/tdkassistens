<script setup lang="ts">
import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuAction,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { type Conversation } from '@/types';
import { Link, router } from '@inertiajs/vue3';
import { MessageSquare, Trash2 } from 'lucide-vue-next';

const props = defineProps<{
    conversations: Conversation[];
    activeId?: number;
}>();

function remove(conversation: Conversation) {
    if (confirm(`Biztosan törlöd a(z) "${conversation.title}" beszélgetést?`)) {
        router.delete(route('conversations.destroy', conversation.id));
    }
}
</script>

<template>
    <SidebarGroup>
        <SidebarGroupLabel>Beszélgetések</SidebarGroupLabel>
        <SidebarGroupContent>
            <div v-if="props.conversations.length === 0" class="px-2 py-4 text-sm text-muted-foreground">Még nincs beszélgetésed.</div>
            <SidebarMenu v-else>
                <SidebarMenuItem v-for="conversation in props.conversations" :key="conversation.id">
                    <SidebarMenuButton :as-child="true" :is-active="conversation.id === props.activeId">
                        <Link :href="route('chat.show', conversation.id)">
                            <MessageSquare />
                            <span>{{ conversation.title || 'Új beszélgetés' }}</span>
                        </Link>
                    </SidebarMenuButton>
                    <SidebarMenuAction :show-on-hover="true" @click="remove(conversation)">
                        <Trash2 />
                        <span class="sr-only">Beszélgetés törlése</span>
                    </SidebarMenuAction>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroupContent>
    </SidebarGroup>
</template>
