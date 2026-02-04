<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DashboardTest extends DuskTestCase
{
    public function test_guest_is_redirected_to_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->driver->manage()->deleteAllCookies();
            $browser->visit('/dashboard')
                ->assertPathIs('/auth/login');
        });
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::where('email', 'admin@demo.com')->first();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->driver->manage()->deleteAllCookies();
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->assertPathIs('/dashboard');
        });
    }

    public function test_dashboard_displays_user_name(): void
    {
        $user = User::where('email', 'admin@demo.com')->first();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->driver->manage()->deleteAllCookies();
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->assertSee($user->first_name);
        });
    }

    public function test_sidebar_navigation_is_visible(): void
    {
        $user = User::where('email', 'admin@demo.com')->first();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->driver->manage()->deleteAllCookies();
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->assertPresent('.layout-menu, .menu-vertical, nav, aside');
        });
    }
}
