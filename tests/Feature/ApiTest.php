<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\User;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test all responses are in JSON
     *
     * @return void
     */
    public function testJsonResponse()
    {
        $response = $this->get('/api/user');

        $response->assertStatus(401);
        $response->assertJson(
            [
                'message' => 'Unauthenticated.'
            ]
        );
    }

    /**
     * Test Protected Routes Require Api Token
     *
     * @return void
     */
    public function testProtectedRoutesRequireApiToken()
    {
        $response = $this->get('/api/user');
        $response->assertStatus(401);

        $token = str_random(20);
        factory(User::class)->create(
            [
                'api_token' => $token
            ]
        );

        $response = $this->get(
            '/api/user',
            [
                'HTTP_Authorization' => 'Bearer ' . $token
            ]
        );
        $response->assertStatus(200);
    }
}
