<?php

test('landing page renders the proxy catalog successfully', function () {
    $this->get('/')->assertOk();
});
