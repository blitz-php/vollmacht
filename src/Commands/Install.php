<?php

namespace BlitzPHP\Vollmacht\Commands;

use BlitzPHP\Cli\Console\Command;

class Install extends Command
{
	/**
	 * {@inheritDoc}
	 */
	protected string $name = 'vollmacht:install';

	/**
     * {@inheritDoc}
     */
    protected string $description = 'Run the commands necessary to prepare Passport for use';

    /**
     * {@inheritDoc}
     */
    protected array $options = [
		'--force' => 'Overwrite keys they already exist',
		'--length' => ['The length of the private key', 4096],
	];

    /**
     * {@inheritDoc}
     */
    public function handle()
    {
        $this->call('vollmacht:keys', options: [
            '--force' => $this->option('force'),
            '--length' => $this->option('length'),
        ]);

        $this->eol()->call(
			'publish',
			options: ['--namespace' => 'BlitzPHP\\Vollmacht']
		);

        if ($this->eol()->confirm('Would you like to run all pending database migrations?')) {
            $this->eol()->call('migrate', options: ['--namespace' => 'BlitzPHP\\Vollmacht']);
		
            if ($this->eol()->confirm('Would you like to create the "personal access" grant client?')) {
                $this->eol()->call(
					'vollmacht:client',
					options: ['--name' => config('app.name'), '--personal' => true]
				);
            }
        }
    }
}
