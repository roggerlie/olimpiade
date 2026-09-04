<?php

test('a guest visiting the root is redirected to the student login', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});
