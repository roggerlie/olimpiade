<?php

// TailAdmin-styled error pages (resources/views/errors/*.blade.php) — Laravel
// picks these up automatically by HTTP status code, no route wiring needed.

test('a nonexistent route renders the custom 404 page', function () {
    $this->get('/this-route-does-not-exist')
        ->assertNotFound()
        ->assertSee('404')
        ->assertSee('Kembali ke Beranda');
});

test('the 500 and 503 error views render without errors', function () {
    expect(view('errors.500')->render())->toContain('500');
    expect(view('errors.503')->render())->toContain('503');
});
