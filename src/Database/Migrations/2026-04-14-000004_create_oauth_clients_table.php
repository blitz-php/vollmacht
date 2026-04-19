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
		$this->create('oauth_clients', function(Builder $table) {
            $table->uuid('id')->primary();
            $table->nullableMorphs('owner');
            $table->string('name');
            $table->string('secret')->nullable();
            $table->string('provider')->nullable();
            $table->text('redirect_uris');
            $table->text('grant_types');
            $table->boolean('revoked');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIfExists('oauth_clients');
    }

    /**
     * {@inheritDoc}
     */
    public function useConnection(): string
    {
        return config('vollmacht.connection', parent::useConnection());
    }
};
