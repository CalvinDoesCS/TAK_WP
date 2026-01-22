<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase
{
    public function test_login_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->driver->manage()->deleteAllCookies();
            $browser->visit('/auth/login')
                ->assertSee('Welcome back')
                ->assertSee('Sign in');
        });
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->driver->manage()->deleteAllCookies();
            $browser->visit('/auth/login')
                ->type('#email', 'admin@demo.com')
                ->type('#password', 'password123')
                ->press('Sign in')
                ->waitForLocation('/dashboard', 10)
                ->assertPathIs('/dashboard');
        });
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->driver->manage()->deleteAllCookies();
            $browser->visit('/auth/login')
                ->type('#email', 'invalid@example.com')
                ->type('#password', 'wrongpassword')
                ->press('Sign in')
                ->pause(2000)
                ->assertPathIs('/auth/login');
        });
    }

    public function test_login_form_has_required_fields(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->driver->manage()->deleteAllCookies();
            $browser->visit('/auth/login')
                ->assertPresent('#email')
                ->assertPresent('#password')
                ->assertSee('Sign in');
        });
    }
}
