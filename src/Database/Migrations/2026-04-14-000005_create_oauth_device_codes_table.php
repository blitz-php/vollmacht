<?php

use BlitzPHP\Database\Migration\Builder;
use BlitzPHP\Database\Migration\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->create('oauth_device_codes', function (Builder $table) {
            $table->char('id', 80)->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->foreignUuid('client_id')->index();
            $table->char('user_code', 8)->unique();
            $table->text('scopes');
            $table->boolean('revoked');
            $table->dateTime('user_approved_at')->nullable();
            $table->dateTime('last_polled_at')->nullable();
            $table->dateTime('expires_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIfExists('oauth_device_codes');
    }

    /**
     * {@inheritDoc}
     */
    public function useConnection(): string
    {
        return config('vollmacht.connection', parent::useConnection());
    }
};
