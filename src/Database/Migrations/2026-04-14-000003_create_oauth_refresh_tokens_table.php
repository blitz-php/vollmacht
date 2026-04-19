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
		$this->create('oauth_refresh_tokens', function(Builder $table) {
            $table->char('id', 80)->primary();
            $table->char('access_token_id', 80)->index();
            $table->boolean('revoked')->default(false);
            $table->dateTime('expires_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIfExists('oauth_refresh_tokens');
    }

    /**
     * {@inheritDoc}
     */
    public function useConnection(): string
    {
        return config('vollmacht.connection', parent::useConnection());
    }
};
