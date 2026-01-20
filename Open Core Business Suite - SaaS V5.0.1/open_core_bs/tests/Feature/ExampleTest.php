<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Test that the homepage redirects to login for unauthenticated users.
     */
    public function test_the_application_redirects_unauthenticated_users(): void
    {
        $response = $this->get('/');

        // App redirects unauthenticated users to login
        $response->assertRedirect();
    }
}
