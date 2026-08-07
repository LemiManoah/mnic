<?php

declare(strict_types=1);

it('uses login as the landing page', function (): void {
    $page = visit('/');

    $page->assertSee('Log in to your account');
});
