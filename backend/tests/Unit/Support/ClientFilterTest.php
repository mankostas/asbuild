<?php

namespace Tests\Unit\Support;

use App\Models\Client;
use App\Models\Tenant;
use App\Support\ClientFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ClientFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolve_returns_internal_ids_for_public_identifiers(): void
    {
        $tenant = Tenant::create(['name' => 'Acme Inc.']);
        $client = Client::create(['tenant_id' => $tenant->id, 'name' => 'Client A']);

        $request = Request::create('/reports', 'GET', [
            'client_id' => $client->public_id,
        ]);

        $ids = ClientFilter::resolve($request);

        $this->assertSame([$client->id], $ids);
    }

    public function test_resolve_filters_with_permitted_public_identifiers(): void
    {
        $tenant = Tenant::create(['name' => 'Acme Inc.']);
        $clientA = Client::create(['tenant_id' => $tenant->id, 'name' => 'Client A']);
        $clientB = Client::create(['tenant_id' => $tenant->id, 'name' => 'Client B']);

        $request = Request::create('/reports', 'GET', [
            'client_ids' => $clientA->public_id . ',' . $clientB->public_id,
        ]);

        $ids = ClientFilter::resolve($request, [$clientB->public_id]);

        $this->assertSame([$clientB->id], $ids);
    }
}
