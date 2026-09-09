<?php

namespace Tests\Feature;

use App\Jobs\RunScriptExecution;
use App\Models\StoreMigration;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MigrationPageTest extends TestCase
{
    public function test_the_migrations_journal_renders_stored_rows(): void
    {
        StoreMigration::factory()->create([
            'id' => 92831,
            'source' => 'Magento 1.9',
            'target' => 'Shopify Plus',
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Migrations');
        $response->assertSee('Magento 1.9');
        $response->assertSee('All migrations');
        $response->assertSee('#92831');
        $response->assertSee('new-migration-modal');
        $response->assertSee('Migration ID');
        $response->assertSee('Loading carts from Cart2Cart');
        $response->assertDontSee('GitHub URL');
        $response->assertDontSee('name="source"', false);
        $response->assertDontSee('name="target"', false);
    }

    public function test_unknown_migration_returns_404(): void
    {
        $response = $this->get('/migrations/1');

        $response->assertNotFound();
    }

    public function test_creates_a_temporary_migration_from_cart2cart_source_and_target(): void
    {
        Queue::fake([RunScriptExecution::class]);
        $this->fakeCart2CartStoreAccess(44102, 'OpenCart', 'Shopify');

        $response = $this->from('/')->post('/migrations', [
            'id' => 44102,
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('status');

        $this->get('/')
            ->assertSee('#44102')
            ->assertSee('OpenCart')
            ->assertSee('Shopify');

        $this->get('/migrations/44102')
            ->assertOk()
            ->assertSee('Script Executions')
            ->assertSee('OpenCart')
            ->assertSee('GitHub URL');

        $this->assertDatabaseHas('store_migrations', [
            'id' => 44102,
            'source' => 'OpenCart',
            'target' => 'Shopify',
            'status' => 'active',
        ]);

        $this->assertDatabaseMissing('script_executions', [
            'store_migration_id' => 44102,
        ]);

        Queue::assertNothingPushed();
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/v1/admin/migrations/44102/stores/access'));
    }

    public function test_rejects_a_missing_migration_id(): void
    {
        $response = $this->from('/')->post('/migrations');

        $response->assertRedirect('/');
        $response->assertSessionHasErrors([
            'id' => 'The id field is required.',
        ]);
    }

    public function test_rejects_a_duplicate_migration_id(): void
    {
        StoreMigration::factory()->create([
            'id' => 92831,
        ]);

        $response = $this->from('/')->post('/migrations', [
            'id' => 92831,
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors([
            'id' => 'This migration ID is already in the journal.',
        ]);
    }

    public function test_rejects_a_cart2cart_store_access_failure(): void
    {
        Queue::fake([RunScriptExecution::class]);
        $this->fakeCart2CartStoreAccess(
            migrationId: 44102,
            error: [
                'success' => false,
                'error' => ['message' => 'Migration not found'],
                'code' => 404,
            ],
            status: 404,
        );

        $response = $this->from('/')->followingRedirects()->post('/migrations', [
            'id' => 44102,
        ]);

        $response->assertOk();
        $response->assertSee('Migration not found');
        $response->assertSee('role="alert"', false);

        $this->assertDatabaseMissing('store_migrations', [
            'id' => 44102,
        ]);

        Queue::assertNothingPushed();
    }
}
