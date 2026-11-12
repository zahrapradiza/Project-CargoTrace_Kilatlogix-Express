<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAndRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/shipments')->assertStatus(401);
        $this->getJson('/api/user')->assertStatus(401);
        $this->get('/shipments')->assertRedirect('/login');
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_api_login_register_user_and_logout_flow(): void
    {
        $registerResponse = $this->postJson('/api/register', [
            'name' => 'New Customer',
            'email' => 'newcust@kilatlogix.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'company_name' => 'Test Logistics Co',
        ]);

        $registerResponse->assertStatus(201)
            ->assertJsonPath('data.user.role', 'customer')
            ->assertJsonStructure(['data' => ['user', 'token']]);

        $this->postJson('/api/login', [
            'email' => 'newcust@kilatlogix.test',
            'password' => 'wrong-password',
        ])->assertStatus(401);

        $loginResponse = $this->postJson('/api/login', [
            'email' => 'newcust@kilatlogix.test',
            'password' => 'password123',
        ]);

        $loginResponse->assertStatus(200);
        $token = $loginResponse->json('data.token');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/user')
            ->assertStatus(200)
            ->assertJsonPath('data.email', 'newcust@kilatlogix.test');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout')
            ->assertStatus(200);
    }

    public function test_role_middleware_enforces_server_side_access_control(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $operator = User::factory()->create(['role' => User::ROLE_OPERATOR]);
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $this->actingAs($customer)->get('/admin/dashboard')->assertStatus(403);
        $this->actingAs($customer)->get('/operator/dashboard')->assertStatus(403);
        $this->actingAs($customer)->get('/customer/dashboard')->assertStatus(200);

        $this->actingAs($operator)->get('/admin/users')->assertStatus(403);
        $this->actingAs($operator)->get('/operator/dashboard')->assertStatus(200);

        $this->actingAs($admin)->get('/admin/dashboard')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/users')->assertStatus(200);
    }
}
