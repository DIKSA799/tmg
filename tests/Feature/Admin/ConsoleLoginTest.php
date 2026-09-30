<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ConsoleLoginTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_console_login_page_renders_on_the_secret_path(): void
    {
        $this->get('/'.config('admin.path').'/login')
            ->assertOk()
            ->assertSee('Console access', false);
    }

    public function test_the_seeded_admin_can_sign_in(): void
    {
        $this->seed(AdminSeeder::class);

        $this->post('/'.config('admin.path').'/login', [
            'username' => config('admin.user.username'),
            'password' => config('admin.user.password'),
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs(Admin::query()->firstOrFail(), 'admin');
    }

    public function test_sign_in_records_the_last_login_time(): void
    {
        $this->seed(AdminSeeder::class);

        $this->post('/'.config('admin.path').'/login', [
            'username' => config('admin.user.username'),
            'password' => config('admin.user.password'),
        ]);

        $this->assertNotNull(Admin::query()->firstOrFail()->last_login_at);
    }

    public function test_a_wrong_password_is_rejected(): void
    {
        $this->seed(AdminSeeder::class);

        $this->from('/'.config('admin.path').'/login')
            ->post('/'.config('admin.path').'/login', [
                'username' => config('admin.user.username'),
                'password' => 'not-the-password',
            ])
            ->assertRedirect('/'.config('admin.path').'/login')
            ->assertSessionHasErrors('username');

        $this->assertGuest('admin');
    }

    public function test_repeated_failures_are_rate_limited(): void
    {
        $this->seed(AdminSeeder::class);

        foreach (range(1, 5) as $attempt) {
            $this->post('/'.config('admin.path').'/login', [
                'username' => config('admin.user.username'),
                'password' => 'wrong-password',
            ]);
        }

        $this->from('/'.config('admin.path').'/login')
            ->post('/'.config('admin.path').'/login', [
                'username' => config('admin.user.username'),
                'password' => config('admin.user.password'),
            ])
            ->assertSessionHasErrors('username');

        $this->assertStringContainsString('Too many attempts', session('errors')->first('username'));
        $this->assertGuest('admin');
    }

    public function test_an_authenticated_admin_is_sent_to_the_dashboard_from_the_login_page(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get('/'.config('admin.path').'/login')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_an_admin_can_sign_out(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin')
            ->post('/'.config('admin.path').'/logout')
            ->assertRedirect(route('admin.login'));

        $this->assertGuest('admin');
    }
}
