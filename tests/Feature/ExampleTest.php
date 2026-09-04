<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Script Executions');
        $response->assertSee('fix-url-keys');
        $response->assertSee('History for this migration');
    }

    public function test_run_script_redirects_with_status(): void
    {
        $response = $this->post('/executions/run');

        $response->assertRedirect();
        $response->assertSessionHas('status');
    }
}
