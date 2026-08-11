<?php

test('support frontend keeps realtime and scroll behavior local', function () {
    $root = dirname(__DIR__, 2).'/resources/js';
    $store = file_get_contents($root.'/stores/support.store.ts');
    $clientPage = file_get_contents($root.'/pages/client/support/index.vue');
    $adminPage = file_get_contents($root.'/pages/admin/support/index.vue');

    expect($store)
        ->toContain("stopListening('.support.message.created', messageCreatedListener)")
        ->toContain("stopListening('.support.messages.read', messagesReadListener)")
        ->not->toContain('setInterval', 'usePoll', 'echo().leave');

    expect($clientPage)
        ->toContain('element.scrollTop += element.scrollHeight - previousHeight')
        ->toContain('showNewMessageButton.value = true')
        ->toContain("event.key === 'Enter' && !event.shiftKey")
        ->toContain('{{ message.message }}')
        ->not->toContain('v-html', 'window.location.reload');

    expect($adminPage)
        ->toContain('upsertConversation(payload.conversation)')
        ->toContain('element.scrollTop += element.scrollHeight - previousHeight')
        ->toContain("status: 'sending'")
        ->toContain("status = 'failed'")
        ->toContain('showNewMessageButton.value = true')
        ->not->toContain('setInterval', 'window.location.reload', 'v-html');
});
