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

        $response = $this->json(
            'POST',
            '/api/network',
            json_decode($network, true)
        );

        $response
            ->assertStatus(401);

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

    public function testAnyoneCanViewAllNetworkLogs()
    {
        $logs = $this->json(
            'get',
            '/api/network'
        );

        $logs->assertStatus(200);

        $logs = $this->json(
            'get',
            '/api/network/1'
        );

        $logs->assertStatus(200);

        $api_token = str_random(30);
        $user = factory(User::class)->create([
            'api_token' => $api_token
        ]);
        $user_id = $user->id;

        $logCount = 10;
        factory(Network::class, $logCount)->create([
            'user_id' => $user_id
        ]);

        $response = $this->get('/api/network');

        $response->assertStatus(200)
            ->assertJsonCount($logCount);
    }

    public function testAnyoneCanViewNetworkLogsByAUser()
    {
        $api_token = str_random(30);
        $user = factory(User::class)->create([
            'api_token' => $api_token
        ]);
        $user_id = $user->id;

        $logCount = 10;
        factory(Network::class, $logCount)->create([
            'user_id' => $user_id
        ]);

        $logs = $this->json(
            'get',
            '/api/network',
            [
                'user_id' => $user_id
            ]
        );

        $logs->assertStatus(200)
            ->assertJsonCount($logCount);
        $userLogs = Network::where(['user_id' => $user_id])->get()->toArray();
        $this->assertEquals($logs->json(), $userLogs);
    }

    public function testAUserCanDeleteTheirLogs()
    {
        $api_token = str_random(30);
        $user = factory(User::class)->create([
            'api_token' => $api_token
        ]);
        $user_id = $user->id;

        $logCount = 10;
        $logs = factory(Network::class, $logCount)->create([
            'user_id' => $user_id
        ]);

        $ids = array_pluck($logs, 'id');
        $idsString = implode($ids, ',');

        $logsBeforeDelete = $this->get('/api/network')->json();

        $response = $this->withHeaders([
                'HTTP_Authorization' => 'Bearer ' . $api_token
            ])->deleteJson(
                "/api/network/$idsString"
            );

        $allLogs = Network::all()->toArray();
        $this->assertNotEquals($logsBeforeDelete, $allLogs);
        $response->assertStatus(200);
    }

    public function testAUserCannotDeleteAnotherUsersLogs()
    {
        $logCount = 10;
        $logs = factory(Network::class, $logCount)->create();
        $ids = array_pluck($logs, 'id');
        $idsString = implode($ids, ',');

        $api_token = str_random(30);
        $user = factory(User::class)->create([
            'api_token' => $api_token
        ]);
        $user_id = $user->id;

        $logs = factory(Network::class, $logCount)->create([
            'user_id' => $user_id
        ]);

        $logsBeforeDelete = $this->get('/api/network')->json();

        $response = $this->withHeaders([
                'HTTP_Authorization' => 'Bearer ' . $api_token
            ])->deleteJson(
                "/api/network/$idsString"
            );

        $allLogs = Network::all()->toArray();
        $this->assertEquals($logsBeforeDelete, $allLogs);
        $response->assertStatus(201)
            ->assertJson(['message' => 'Nothing to delete']);
    }
}
