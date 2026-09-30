<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ConsoleSecurityTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_are_sent_to_the_console_login(): void
    {
        $this->get('/'.config('admin.path'))->assertRedirect(route('admin.login'));
    }

    public function test_the_predictable_admin_url_is_not_mounted(): void
    {
        $this->get('/admin')->assertNotFound();
        $this->get('/admin/login')->assertNotFound();
    }

    public function test_a_regular_web_user_cannot_reach_the_console(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/'.config('admin.path'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_the_console_path_is_never_referenced_on_the_public_site(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee(config('admin.path'), false);
    }

    public function test_console_responses_are_marked_noindex(): void
    {
        $this->get('/'.config('admin.path').'/login')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet')
            ->assertSee('noindex,nofollow', false);
    }
}
