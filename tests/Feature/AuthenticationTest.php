<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_can_view_the_login_page(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Painel de Indicadores')
            ->assertSee('Entrar');
    }

    public function test_a_guest_is_redirected_to_login_when_accessing_the_dashboard(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_the_demo_user_is_created_with_a_hashed_password(): void
    {
        $this->seed(DemoUserSeeder::class);

        $user = User::query()->where('email', DemoUserSeeder::EMAIL)->firstOrFail();

        $this->assertNotSame(DemoUserSeeder::PASSWORD, $user->password);
        $this->assertTrue(Hash::check(DemoUserSeeder::PASSWORD, $user->password));
    }

    public function test_a_user_can_log_in_with_valid_credentials(): void
    {
        $this->seed(DemoUserSeeder::class);

        $response = $this->post(route('login.store'), [
            'email' => DemoUserSeeder::EMAIL,
            'password' => DemoUserSeeder::PASSWORD,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_invalid_credentials_do_not_authenticate_the_user(): void
    {
        $this->seed(DemoUserSeeder::class);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => DemoUserSeeder::EMAIL,
            'password' => 'senha-incorreta',
        ]);

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email')
            ->assertSessionHasInput('email', DemoUserSeeder::EMAIL);
        $this->assertGuest();
    }

    public function test_an_authenticated_user_can_view_the_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($user->name);
    }

    public function test_an_authenticated_user_is_redirected_away_from_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_an_authenticated_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
