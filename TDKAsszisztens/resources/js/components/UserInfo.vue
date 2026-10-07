<script setup lang="ts">
import type { User } from '@/types';
import { Blobatar } from '@blobatar/vue';

interface Props {
    user: User;
    showEmail?: boolean;
}

withDefaults(defineProps<Props>(), {
    showEmail: false,
});
</script>

<template>
    <Blobatar :name="user.email" :title="user.name" class="h-8 w-8 shrink-0 rounded-lg" />

    <div class="grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-medium">{{ user.name }}</span>
        <span v-if="showEmail" class="truncate text-xs text-muted-foreground">{{ user.email }}</span>
    </div>
</template>
