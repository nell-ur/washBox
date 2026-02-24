<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        // Allow either OK (200) or Redirect (302) depending on middleware
        $this->assertTrue(in_array($response->getStatusCode(), [200, 302]));
    }
}
