<script setup lang="ts">
import ThinkingOrb from '@/components/ThinkingOrb.vue';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { type ChatMessage } from '@/types';
import { ChevronDown } from 'lucide-vue-next';
import { nextTick, ref, watch } from 'vue';

const props = defineProps<{
    messages: ChatMessage[];
    pending?: boolean;
}>();

const scrollContainer = ref<HTMLElement | null>(null);

function parseContent(content: string): { thinking: string | null; answer: string } {
    const match = content.match(/<gondolkodas>([\s\S]*?)<\/gondolkodas>/i);

    if (!match) {
        return { thinking: null, answer: content };
    }

    return {
        thinking: match[1].trim(),
        answer: content.replace(match[0], '').trim(),
    };
}

watch(
    () => [props.messages.length, props.pending],
    async () => {
        await nextTick();
        scrollContainer.value?.scrollTo({ top: scrollContainer.value.scrollHeight });
    },
    { immediate: true },
);
</script>

<template>
    <div ref="scrollContainer" class="flex-1 overflow-y-auto">
        <div class="mx-auto flex min-h-full w-full max-w-3xl flex-col space-y-6 p-4">
            <div v-for="message in props.messages" :key="message.id" class="flex" :class="message.role === 'user' ? 'justify-end' : 'justify-start'">
                <div v-if="message.role === 'user'" class="max-w-[80%] whitespace-pre-wrap rounded-xl bg-muted px-4 py-2.5 text-sm">
                    {{ message.content }}
                </div>
                <div v-else class="w-full space-y-2 text-sm">
                    <Collapsible v-if="parseContent(message.content).thinking">
                        <CollapsibleTrigger
                            class="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground [&[data-state=open]>svg]:rotate-180"
                        >
                            <ChevronDown class="size-3.5 transition-transform" />
                            Gondolkodás
                        </CollapsibleTrigger>
                        <CollapsibleContent class="mt-1 whitespace-pre-wrap border-l-2 border-muted-foreground/30 pl-3 text-xs text-muted-foreground">
                            {{ parseContent(message.content).thinking }}
                        </CollapsibleContent>
                    </Collapsible>
                    <div class="whitespace-pre-wrap">{{ parseContent(message.content).answer }}</div>
                </div>
            </div>

            <div v-if="props.pending" class="flex items-center justify-start gap-2 text-sm text-muted-foreground">
                <ThinkingOrb state="working" :size="20" />
                <span class="animate-pulse">Dolgozik…</span>
            </div>
        </div>
    </div>
</template>
