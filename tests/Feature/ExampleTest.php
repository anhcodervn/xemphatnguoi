<?php

test('landing page renders the proxy catalog successfully', function () {
    $this->get('/')
        ->assertOk()
        ->assertSeeText('Hạ tầng proxy đa dạng,')
        ->assertSeeText('đa quốc gia')
        ->assertSeeText('Chọn proxy phù hợp với nhu cầu của bạn')
        ->assertSee('images/landing/proxy-infrastructure-hero.jpg')
        ->assertSee('viewBox="0 0 24 24"', false)
        ->assertDontSee('bx-shield-check')
        ->assertDontSee('bx-right-arrow-alt');
});
