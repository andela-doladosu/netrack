<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiTest extends TestCase
{
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
}
