<script setup lang="ts">
import ConversationList from '@/components/chat/ConversationList.vue';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarRail,
} from '@/components/ui/sidebar';
import UserInfo from '@/components/UserInfo.vue';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { type Conversation, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronsUpDown, FolderOpen, SquarePen } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    conversations: Conversation[];
    activeId?: number;
    documentsActive?: boolean;
}>();

const emit = defineEmits<{
    (e: 'toggle-documents'): void;
    (e: 'new-chat'): void;
}>();

const page = usePage<SharedData>();
const auth = computed(() => page.props.auth);
</script>

<template>
    <Sidebar collapsible="offcanvas">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton :as-child="true">
                        <Link :href="route('chat.index')" @click="emit('new-chat')">
                            <SquarePen />
                            <span>Új chat</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
                <SidebarMenuItem>
                    <SidebarMenuButton :is-active="props.documentsActive" @click="emit('toggle-documents')">
                        <FolderOpen />
                        <span>Dokumentumok</span>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <ConversationList :conversations="props.conversations" :active-id="props.activeId" />
        </SidebarContent>

        <SidebarFooter>
            <SidebarMenu>
                <SidebarMenuItem>
                    <DropdownMenu>
                        <DropdownMenuTrigger :as-child="true">
                            <SidebarMenuButton size="lg" class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground">
                                <UserInfo :user="auth.user" />
                                <ChevronsUpDown class="ml-auto" />
                            </SidebarMenuButton>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent side="top" align="start" class="w-[--radix-dropdown-menu-trigger-width] min-w-56">
                            <UserMenuContent :user="auth.user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarFooter>

        <SidebarRail />
    </Sidebar>
</template>
