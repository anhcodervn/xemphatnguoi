<script setup lang="ts">
import { supportService } from '@/services/support.service';
import { useSupportStore } from '@/stores/support.store';
import { useUserStore } from '@/stores/user.store';
import type { SupportConversation, SupportMessage, SupportMessageCreatedEvent, SupportMessagesReadEvent } from '@/types/support.type';
import { handleErrorResponse } from '@/utils/response';
import { ArrowDown, CheckCheck, LoaderCircle, MessageCircleMore, RefreshCw, Send } from 'lucide-vue-next';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

const userStore = useUserStore();
const supportStore = useSupportStore();
const conversation = ref<SupportConversation | null>(null);
const messages = ref<SupportMessage[]>([]);
const messageText = ref('');
const loadingInitial = ref(true);
const loadingOlder = ref(false);
const sending = ref(false);
const markingRead = ref(false);
const nextCursor = ref<string | null>(null);
const hasMore = ref(false);
const showNewMessageButton = ref(false);
const messageArea = ref<HTMLElement | null>(null);
const composer = ref<HTMLTextAreaElement | null>(null);

const isNearBottom = (): boolean => {
    const element = messageArea.value;
    return !element || element.scrollHeight - element.scrollTop - element.clientHeight < 120;
};

const scrollToBottom = async (behavior: ScrollBehavior = 'smooth'): Promise<void> => {
    await nextTick();
    const element = messageArea.value;
    element?.scrollTo({ top: element.scrollHeight, behavior });
    showNewMessageButton.value = false;
};

const mergeMessage = (incoming: SupportMessage): void => {
    const index = messages.value.findIndex((message) => message.id === incoming.id);

    if (index >= 0) {
        messages.value[index] = { ...messages.value[index], ...incoming, status: 'sent' };
        return;
    }

    messages.value.push({ ...incoming, status: 'sent' });
};

const markIncomingRead = async (): Promise<void> => {
    if (markingRead.value || !conversation.value) {
        return;
    }

    try {
        markingRead.value = true;
        const response = await supportService.clientMarkRead();
        supportStore.applyStats(response.stats);
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        markingRead.value = false;
    }
};

const loadInitial = async (): Promise<void> => {
    try {
        loadingInitial.value = true;
        const response = await supportService.clientThread();
        conversation.value = response.conversation;
        messages.value = response.messages.map((message) => ({ ...message, status: 'sent' }));
        nextCursor.value = response.meta.next_cursor;
        hasMore.value = response.meta.has_more;
        supportStore.applyStats(response.stats);
        await scrollToBottom('auto');
        await markIncomingRead();
        await nextTick();
        composer.value?.focus();
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loadingInitial.value = false;
    }
};

const loadOlder = async (): Promise<void> => {
    const element = messageArea.value;

    if (!element || loadingOlder.value || !hasMore.value || !nextCursor.value || !conversation.value) {
        return;
    }

    try {
        loadingOlder.value = true;
        const previousHeight = element.scrollHeight;
        const response = await supportService.clientThread(nextCursor.value);
        const knownIds = new Set(messages.value.map((message) => message.id));
        const olderMessages = response.messages.filter((message) => !knownIds.has(message.id));
        messages.value = [...olderMessages, ...messages.value];
        nextCursor.value = response.meta.next_cursor;
        hasMore.value = response.meta.has_more;
        await nextTick();
        element.scrollTop += element.scrollHeight - previousHeight;
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loadingOlder.value = false;
    }
};

const handleScroll = (): void => {
    const element = messageArea.value;

    if (element && element.scrollTop <= 24) {
        void loadOlder();
    }

    if (isNearBottom()) {
        showNewMessageButton.value = false;
    }
};

const sendMessage = async (retryMessage?: SupportMessage): Promise<void> => {
    const content = (retryMessage?.message ?? messageText.value).trim();

    if (content === '' || sending.value) {
        return;
    }

    const temporaryId = `pending-${Date.now()}`;
    const pendingMessage: SupportMessage = {
        id: temporaryId,
        conversation_id: conversation.value?.id ?? 0,
        sender_id: userStore.user?.id ?? 0,
        sender_role: 'user',
        message: content,
        read_at: null,
        created_at: new Date().toISOString(),
        status: 'sending',
    };

    if (retryMessage) {
        messages.value = messages.value.filter((message) => message.id !== retryMessage.id);
    } else {
        messageText.value = '';
    }

    messages.value.push(pendingMessage);
    await scrollToBottom();

    try {
        sending.value = true;
        const response = await supportService.clientSend(content);
        messages.value = messages.value.filter((message) => message.id !== temporaryId);
        conversation.value = response.conversation;
        mergeMessage(response.message);
        supportStore.applyStats(response.stats);
        await scrollToBottom();
    } catch (error) {
        const failedMessage = messages.value.find((message) => message.id === temporaryId);
        if (failedMessage) {
            failedMessage.status = 'failed';
        }
        handleErrorResponse(error);
    } finally {
        sending.value = false;
        await nextTick();
        composer.value?.focus();
    }
};

const handleComposerKeydown = (event: KeyboardEvent): void => {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        void sendMessage();
    }
};

const handleRealtimeMessage = (event: Event): void => {
    const payload = (event as CustomEvent<SupportMessageCreatedEvent>).detail;

    if (payload.conversation.user.id !== userStore.user?.id) {
        return;
    }

    const shouldFollow = isNearBottom();
    conversation.value = { ...payload.conversation, unread_count: 0 };
    mergeMessage(payload.message);

    if (payload.message.sender_role === 'admin') {
        void markIncomingRead();
    }

    if (shouldFollow) {
        void scrollToBottom();
    } else {
        showNewMessageButton.value = true;
    }
};

const handleReadReceipt = (event: Event): void => {
    const payload = (event as CustomEvent<SupportMessagesReadEvent>).detail;

    if (payload.conversation_id !== conversation.value?.id) {
        return;
    }

    const readIds = new Set(payload.message_ids);
    messages.value = messages.value.map((message) => (readIds.has(Number(message.id)) ? { ...message, read_at: payload.read_at } : message));
};

const formatTime = (value: string | null): string => {
    if (!value) {
        return '';
    }

    return new Date(value).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
};

onMounted(() => {
    window.addEventListener('support:message-created', handleRealtimeMessage);
    window.addEventListener('support:messages-read', handleReadReceipt);
    void loadInitial();
});

onBeforeUnmount(() => {
    window.removeEventListener('support:message-created', handleRealtimeMessage);
    window.removeEventListener('support:messages-read', handleReadReceipt);
});
</script>

<template>
    <section
        class="flex h-[calc(100dvh-9.5rem)] min-h-[520px] flex-col overflow-hidden rounded-[14px] border border-slate-200 bg-white shadow-[0_20px_60px_-36px_rgba(15,23,42,0.35)]"
    >
        <header class="flex shrink-0 items-center justify-between gap-4 border-b border-slate-200 px-4 py-3 sm:px-5">
            <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-50 text-blue-700">
                    <MessageCircleMore class="h-5 w-5" />
                </div>
                <div class="min-w-0">
                    <h1 class="truncate text-sm font-bold text-slate-950">Hỗ trợ DailyProxy</h1>
                    <p class="mt-0.5 flex items-center gap-1.5 text-xs" :class="supportStore.connected ? 'text-blue-700' : 'text-slate-500'">
                        <span class="h-2 w-2 rounded-full" :class="supportStore.connected ? 'bg-blue-500' : 'bg-slate-400'" />
                        {{ supportStore.connected ? 'Đã kết nối realtime' : 'Đang kết nối...' }}
                    </p>
                </div>
            </div>
            <button
                type="button"
                class="proxy-focus inline-flex min-h-11 items-center gap-2 rounded-[10px] px-3 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-blue-700"
                :disabled="loadingInitial"
                @click="loadInitial"
            >
                <RefreshCw class="h-4 w-4" :class="loadingInitial ? 'animate-spin' : ''" />
                <span class="hidden sm:inline">Làm mới</span>
            </button>
        </header>

        <div class="relative min-h-0 flex-1 bg-slate-50/70">
            <div ref="messageArea" class="h-full overflow-y-auto px-3 py-4 sm:px-5" aria-live="polite" @scroll.passive="handleScroll">
                <div v-if="loadingOlder" class="flex items-center justify-center gap-2 py-2 text-xs text-slate-500">
                    <LoaderCircle class="h-4 w-4 animate-spin" /> Đang tải tin nhắn cũ...
                </div>
                <button
                    v-else-if="hasMore"
                    type="button"
                    class="proxy-focus mx-auto mb-3 flex min-h-11 items-center rounded-[10px] px-3 text-xs font-semibold text-blue-700 hover:bg-blue-50"
                    @click="loadOlder"
                >
                    Xem tin nhắn cũ hơn
                </button>

                <div v-if="loadingInitial" class="flex h-full items-center justify-center text-sm text-slate-500">
                    <LoaderCircle class="mr-2 h-5 w-5 animate-spin text-blue-600" /> Đang tải cuộc trò chuyện...
                </div>
                <div v-else-if="messages.length === 0" class="flex h-full flex-col items-center justify-center px-6 text-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-blue-50 text-blue-700">
                        <MessageCircleMore class="h-7 w-7" />
                    </div>
                    <p class="mt-4 text-sm font-bold text-slate-950">Bắt đầu cuộc trò chuyện</p>
                    <p class="mt-1 max-w-sm text-sm leading-6 text-slate-500">
                        Gửi câu hỏi hoặc vấn đề bạn đang gặp, đội ngũ hỗ trợ sẽ phản hồi trực tiếp tại đây.
                    </p>
                </div>

                <div v-else class="space-y-3">
                    <article
                        v-for="message in messages"
                        :key="message.id"
                        class="flex"
                        :class="message.sender_role === 'user' ? 'justify-end' : 'justify-start'"
                    >
                        <div class="max-w-[85%] sm:max-w-[72%]">
                            <div
                                class="rounded-[14px] px-4 py-2.5 text-sm leading-6 shadow-sm"
                                :class="
                                    message.sender_role === 'user'
                                        ? 'rounded-br-sm bg-blue-600 text-white'
                                        : 'rounded-bl-sm border border-slate-200 bg-white text-slate-800'
                                "
                            >
                                <p class="whitespace-pre-wrap break-words">{{ message.message }}</p>
                            </div>
                            <div
                                class="mt-1 flex items-center gap-2 px-1 text-[11px] text-slate-400"
                                :class="message.sender_role === 'user' ? 'justify-end' : 'justify-start'"
                            >
                                <span>{{ formatTime(message.created_at) }}</span>
                                <span v-if="message.status === 'sending'" class="inline-flex items-center gap-1"
                                    ><LoaderCircle class="h-3 w-3 animate-spin" /> Đang gửi</span
                                >
                                <button
                                    v-else-if="message.status === 'failed'"
                                    type="button"
                                    class="inline-flex min-h-11 items-center gap-1 font-semibold text-red-600"
                                    @click="sendMessage(message)"
                                >
                                    Gửi thất bại · Thử lại
                                </button>
                                <span
                                    v-else-if="message.sender_role === 'user' && message.read_at"
                                    class="inline-flex items-center gap-1 text-blue-600"
                                    ><CheckCheck class="h-3.5 w-3.5" /> Đã xem</span
                                >
                            </div>
                        </div>
                    </article>
                </div>
            </div>

            <button
                v-if="showNewMessageButton"
                type="button"
                class="proxy-focus absolute bottom-3 left-1/2 inline-flex min-h-11 -translate-x-1/2 items-center gap-2 rounded-full bg-slate-950 px-4 text-xs font-semibold text-white shadow-lg transition hover:bg-blue-700"
                @click="scrollToBottom()"
            >
                <ArrowDown class="h-4 w-4" /> Tin nhắn mới
            </button>
        </div>

        <form class="shrink-0 border-t border-slate-200 bg-white p-3 sm:p-4" @submit.prevent="sendMessage()">
            <label for="support-message" class="sr-only">Nội dung tin nhắn</label>
            <div
                class="flex items-end gap-2 rounded-[14px] border border-slate-200 bg-slate-50 p-2 transition focus-within:border-blue-400 focus-within:ring-2 focus-within:ring-blue-100"
            >
                <textarea
                    id="support-message"
                    ref="composer"
                    v-model="messageText"
                    rows="1"
                    maxlength="5000"
                    placeholder="Nhập tin nhắn..."
                    class="max-h-32 min-h-11 flex-1 resize-none border-0 bg-transparent px-2 py-2.5 text-base text-slate-900 outline-none placeholder:text-slate-400 focus:ring-0 sm:text-sm"
                    @keydown="handleComposerKeydown"
                />
                <button
                    type="submit"
                    class="proxy-focus inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[10px] bg-blue-600 text-white transition duration-200 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="sending || messageText.trim() === ''"
                    aria-label="Gửi tin nhắn"
                >
                    <Send class="h-5 w-5" />
                </button>
            </div>
            <p class="mt-2 px-1 text-[11px] text-slate-400">Enter để gửi · Shift + Enter để xuống dòng</p>
        </form>
    </section>
</template>
