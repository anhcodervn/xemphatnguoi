<?php

test('guest users can view the landing page', function () {
    $response = $this->get('/');

    $response
        ->assertOk()
        ->assertSee('id="hero-section"', false)
        ->assertSeeText('NAPTIENTUDONG.COM')
        ->assertSeeTextInOrder(['Recharge', 'Automation'])
        ->assertSeeText('Bảng giá')
        ->assertSeeText('Đăng nhập')
        ->assertSeeText('Tạo tài khoản');
});
