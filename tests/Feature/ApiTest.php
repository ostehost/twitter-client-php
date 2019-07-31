<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiTest extends TestCase
{
    /**
     * Test missing search
     *
     * @return void
     */
    public function testMissingSearch()
    {
        $response = $this->get('/api/tweets');

        $response->assertStatus(418);
    }

    /**
     * Test missing search
     *
     * @return void
     */
    public function testSearch()
    {
        $response = $this->get('/api/tweets?search=test');

        $response->assertStatus(200);
    }
}
