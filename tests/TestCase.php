<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Helper to authenticate and get a token for API requests.
     */
    protected function getAuthToken(): string
    {
        $user = \App\Models\User::factory()->create([
            'email' => 'testuser@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ]);
        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $response->assertStatus(200);
        return $response['token'];
    }
}
