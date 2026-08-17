<?php

test('client header menus render above dashboard content without a legacy marquee', function () {
    $header = file_get_contents(dirname(__DIR__, 2).'/resources/js/layouts/client/Header.vue');

    expect($header)
        ->toContain('fixed left-0 right-0 top-0 z-30')
        ->toContain('relative z-20 flex items-center justify-between')
        ->toContain('top-28 z-50')
        ->toContain('absolute right-0 z-50 mt-3')
        ->not->toContain('marquee');
});
