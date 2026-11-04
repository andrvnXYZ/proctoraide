<?php

namespace Tests\Concerns;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;

trait RefreshMongoDatabase
{
    use RefreshDatabase;

    /**
     * Migrate the MongoDB test database, then reconnect before starting a transaction.
     */
    protected function refreshTestDatabase()
    {
        if (! RefreshDatabaseState::$migrated) {
            $this->migrateDatabases();

            $this->app[Kernel::class]->setArtisan(null);

            RefreshDatabaseState::$migrated = true;

            // migrate:fresh disconnects the MongoDB client, so purge the
            // manager before the per-test transaction opens a new session.
            $this->app['db']->purge();
        }

        $this->beginDatabaseTransaction();
    }
}
