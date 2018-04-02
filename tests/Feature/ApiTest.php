<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\User;
use App\Network;

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

    public function testSignUpRoute()
    {
        $response = $this->json(
            'POST',
            '/api/signup',
            [
                'email' => 'me@mail.com',
                'name' => 'Sally',
                'password' => 'pass1234',
                'password_confirmation' => 'pass1234'
            ]
        );

        $response
            ->assertStatus(200)
            ->assertJsonStructure(['message', 'api_token']);
    }

    public function testLoginRoute()
    {
        $email = 'me@mmmail.com';
        $password = 'pass1234';

        $signup = $this->json(
            'POST',
            '/api/signup',
            [
                'email' => $email,
                'name' => 'Sally',
                'password' => $password,
                'password_confirmation' => $password
            ]
        );

        $signup->assertStatus(200);

        $response = $this->json(
            'POST',
            '/api/login',
            [
                'email' => $email,
                'password' => $password
            ]
        );

        $response
            ->assertStatus(200)
            ->assertJsonStructure(['message', 'api_token']);
    }

    public function testTokenIsSavedOnLogin()
    {
        $email = 'me@mmmail.com';
        $password = 'pass1234';

        $signup = $this->json(
            'POST',
            '/api/signup',
            [
                'email' => $email,
                'name' => 'Sally',
                'password' => $password,
                'password_confirmation' => $password
            ]
        );

        $user = User::where(['email' => $email])->first();
        $token = $user->api_token;
        $this->assertNotNull($user->api_token);

        $user->api_token = null;
        $user->save();

        $user = User::where(['email' => $email])->first();
        $this->assertNull($user->api_token);

        $response = $this->json(
            'POST',
            '/api/login',
            [
                'email' => $email,
                'password' => $password
            ]
        );

        $user = User::where(['email' => $email])->first();
        $newToken = $user->token;
        $this->assertNotNull($user->api_token);
        $this->assertNotEquals($token, $newToken);
    }

    public function testUserCanAddNetworkInfo()
    {
        $token = str_random(20);
        $user = factory(User::class)->create([
            'api_token' => $token
        ]);

        $network = factory(Network::class)->make([
            'user_id' => $user->id
        ]);

        $response = $this->withHeaders([
            'HTTP_Authorization' => 'Bearer ' . $token
        ])
        ->json(
            'POST',
            '/api/network',
            json_decode($network, true)
        );

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'message', 'network_log_id'
            ])
            ->assertJsonFragment([
                'message' => 'Network log added successfully'
            ]);

        $log = json_decode($response->getContent(), true);
        $logId = $log['network_log_id'];

        $addedLog = Network::find($logId);
        $this->assertNotNull($addedLog);
        $this->assertEquals($user->email, $addedLog->user->email);
    }
}
