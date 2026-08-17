<?php

test('public traffic fine landing page renders successfully', function () {
    $this->get('/')
        ->assertOk()
        ->assertSeeText('Tra cứu phạt nguội toàn quốc')
        ->assertSeeText('Không cần đăng nhập')
        ->assertSee('data-lookup-form', false);
});
