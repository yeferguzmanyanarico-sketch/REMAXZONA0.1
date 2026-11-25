<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_route_is_not_required_for_internal_users(): void
    {
        $response = $this->get('/email/verify');

        $response->assertNotFound();
    }
}
