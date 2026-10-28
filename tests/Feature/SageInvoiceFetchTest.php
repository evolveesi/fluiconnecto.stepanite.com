<?php

namespace Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SageInvoiceFetchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.sage', [
            'url' => 'http://sage.test/Sage300WebApi/v1.0/-/ZMDAT',
            'user' => 'test-user',
            'password' => 'test-password',
            'timeout' => 300,
        ]);
    }

    public function test_sage_timeout_returns_a_readable_service_error(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Operation timed out');
        });

        $this->getJson('/check-sage-uninterfaced-invoices')
            ->assertStatus(503)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Sage did not respond in time. Please try again later.');
    }

    public function test_sage_http_error_returns_a_gateway_error(): void
    {
        Http::fake(['sage.test/*' => Http::response('Unavailable', 503)]);

        $this->getJson('/check-sage-uninterfaced-invoices')
            ->assertStatus(502)
            ->assertJsonPath('success', false);
    }

    public function test_empty_invoice_list_completes_without_interfacing(): void
    {
        Http::fake(['sage.test/*' => Http::response(['value' => []], 200)]);

        $this->getJson('/check-sage-uninterfaced-invoices')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('processed', 0);
    }
}
