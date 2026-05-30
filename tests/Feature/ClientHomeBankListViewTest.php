<?php

test('client home bank list keeps the compact bank layout content', function () {
    $component = file_get_contents(resource_path('js/pages/client/home/ListBank.vue'));

    expect($component)
        ->toContain('v-for="bank in banks"')
        ->toContain('usedBanks }}/{{ banks.length }}')
        ->toContain('Danh sách bank')
        ->toContain("{{ bank.active ? 'Đang dùng' : 'Trống' }}");
});
