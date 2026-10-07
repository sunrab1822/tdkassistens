<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { type SharedData } from '@/types';
import { Blobatar } from '@blobatar/vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage<SharedData>();
const auth = computed(() => page.props.auth);
</script>

<template>
    <div class="border-b border-sidebar-border/80">
        <div class="mx-auto flex h-14 items-center px-4">
            <Link :href="route('chat.index')" class="flex items-center gap-x-2">
                <AppLogo class="h-6" />
            </Link>

            <div class="ml-auto flex items-center">
                <DropdownMenu>
                    <DropdownMenuTrigger :as-child="true">
                        <Button
                            variant="ghost"
                            size="icon"
                            class="relative size-10 w-auto rounded-full p-1 focus-within:ring-2 focus-within:ring-primary"
                        >
                            <Blobatar :name="auth.user.email" :title="auth.user.name" class="size-8 rounded-full" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-56">
                        <UserMenuContent :user="auth.user" />
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>
    </div>
</template>
