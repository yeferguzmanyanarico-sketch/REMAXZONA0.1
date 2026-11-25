<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_password_reset_route_is_disabled_by_design(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertNotFound();
    }
}
