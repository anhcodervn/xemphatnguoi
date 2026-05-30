<?php

test('auth blade login page can be rendered', function () {
    $response = $this->get(route('auth.login'));

    $response->assertOk();
    $response->assertSeeText('Đăng nhập hệ thống');
    $response->assertSeeText('Đăng nhập');
    $response->assertSeeText('Đăng ký');
    $response->assertSeeText('Email hoặc tên đăng nhập');
    $response->assertSeeText('Ghi nhớ đăng nhập');
    $response->assertSeeText('Đăng nhập bằng Google');
});

test('auth blade register page can be rendered', function () {
    $response = $this->get(route('auth.register'));

    $response->assertOk();
    $response->assertSeeText('Tạo tài khoản mới');
    $response->assertSeeText('Đăng nhập');
    $response->assertSeeText('Đăng ký');
    $response->assertSeeText('Nhập lại mật khẩu');
});
