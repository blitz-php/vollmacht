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
         $this->create('oauth_auth_codes', function(Builder $table) {
            $table->char('id', 80)->primary();
            $table->foreignId('user_id')->index();
            $table->foreignUuid('client_id');
            $table->text('scopes')->nullable();
            $table->boolean('revoked')->default(false);
            $table->dateTime('expires_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIfExists('oauth_auth_codes');
    }

    /**
     * {@inheritDoc}
     */
    public function useConnection(): string
    {
        return config('vollmacht.connection', parent::useConnection());
    }
};
