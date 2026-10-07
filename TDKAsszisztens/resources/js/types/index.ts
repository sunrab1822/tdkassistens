import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface Conversation {
    id: number;
    title: string | null;
    updated_at: string;
}

export interface ChatMessage {
    id: number;
    conversation_id: number;
    role: 'user' | 'assistant';
    content: string;
    created_at: string;
}

export interface ActiveConversation {
    id: number;
    title: string | null;
    messages: ChatMessage[];
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    conversations: Conversation[];
    ziggy: {
        location: string;
        url: string;
        port: null | number;
        defaults: Record<string, unknown>;
        routes: Record<string, string>;
    };
}

export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;
