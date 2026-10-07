<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register()
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_client_users_can_register_and_are_auto_verified(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Elena Client',
            'email' => 'elena.client@example.com',
            'phone' => '+351912345678',
            'role' => 'client',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'elena.client@example.com')->firstOrFail();
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertEquals(UserRole::Client, $user->role);
        $this->assertEquals('+351912345678', $user->phone);
    }

    public function test_owner_users_can_register_with_business_scaffolding_and_auto_verified(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Carlos Owner',
            'email' => 'carlos.owner@example.com',
            'phone' => '+351912999888',
            'business_name' => 'Apex Grooming Studio',
            'role' => 'owner',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'carlos.owner@example.com')->firstOrFail();
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertEquals(UserRole::Owner, $user->role);

        $business = $user->ownedBusinesses()->first();
        $this->assertNotNull($business);
        $this->assertEquals('Apex Grooming Studio', $business->name);
        $this->assertEquals(1, $business->locations()->count());
    }
}
