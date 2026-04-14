<?php

namespace BlitzPHP\Vollmacht\Commands;

use BlitzPHP\Cli\Console\Command;
use BlitzPHP\Security\Hashing\Hasher;
use BlitzPHP\Vollmacht\Vollmacht;

class Hash extends Command
{
	/**
     * {@inheritDoc}
     */
	protected string $name = 'vollmacht:hash';

	/**
     * {@inheritDoc}
     */
    protected string $description = 'Hash all of the existing secrets in the clients table';

	/**
     * {@inheritDoc}
     */
    protected array $options = [
		'--force' => 'Force the operation to run without confirmation prompt'
	];


    /**
     * {@inheritDoc}
     */
    public function handle()
    {
        if ($this->option('force') ||
            $this->confirm('Are you sure you want to hash all client secrets? This cannot be undone.', 'n')) {

			/** @var Hasher */
			$hasher = service('hashing');

            foreach (Vollmacht::client()->newQuery()->whereNotNull('secret')->cursor() as $client) {
                if ($hasher->isHashed($client->secret) && ! $hasher->needsRehash($client->secret)) {
                    continue;
                }

                $client->timestamps = false;

                $client->forceFill(['secret' => $client->secret])->save();
            }

            $this->info('All client secrets were successfully hashed.');
        }
    }
}
