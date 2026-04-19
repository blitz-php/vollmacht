<?php

namespace BlitzPHP\Vollmacht\Commands;

use BlitzPHP\Cli\Console\Command;
use BlitzPHP\Vollmacht\Entities\Client;
use BlitzPHP\Vollmacht\Repositories\ClientRepository;
use BlitzPHP\Vollmacht\Vollmacht;

class ClientCommand extends Command
{
	protected string $name = 'vollmacht:client';

    protected string $description = 'Create a client for issuing access tokens';
		
	protected array $options = [
		'--name'         => 'Nom du client',
		'--personal'     => 'Create a personal access token client',
		'--password'     => 'Create a password grant client',
		'--client'       => 'Create a client credentials grant client',
		'--implicit'     => 'Create an implicit grant client',
		'--device'       => 'Create a device authorization grant client',
		'--provider'     => 'The name of the user provider',
		'--redirect-uri' => 'The URI to redirect to after authorization ',
		'--public'       => 'Create a public client (without secret)',
	];

	public function __construct(protected ClientRepository $clients)
	{
	}

    /**
     * {@inheritDoc}
     */
    public function handle()
    {
        $client = match (true) {
            $this->option('personal') => $this->createPersonalAccessClient(),
            $this->option('password') => $this->createPasswordClient(),
            $this->option('client') => $this->createClientCredentialsClient(),
            $this->option('implicit') => $this->createImplicitClient(),
            $this->option('device') => $this->createDeviceCodeClient(),
            default => $this->createAuthCodeClient()
        };

        $this->info('New client created successfully.');

        if ($client) {
            $this->justify('Client ID', $client->getKey());

            if ($client->confidential()) {
                $this->justify('Client Secret', $client->plainSecret);
                $this->warn('The client secret will not be shown again, so don\'t lose it!');
            }
        }
    }

    /**
     * Create a new personal access client.
     */
    protected function createPersonalAccessClient(): ?Client
    {
        $this->clients->createPersonalAccessGrantClient(
			$this->option('name'),
			$this->retrieveProvider()
		);

        return null;
    }

    /**
     * Create a new password grant client.
     */
    protected function createPasswordClient(): Client
    {
        $confidential = $this->hasOption('public')
            ? ! $this->option('public')
            : $this->confirm('Would you like to make this client confidential?', 'n');

        return $this->clients->createPasswordGrantClient(
			$this->option('name'),
			$this->retrieveProvider(),
			$confidential
		);
    }

	protected function retrieveProvider(): string
	{
		return 'vollmacht';
		
		if (null === $provider = $this->option('provider')) {
			$provider = $this->choice(
            	'Which user provider should this client use to retrieve users?',
            collect(config('auth.guards'))->where('driver', 'passport')->pluck('provider')->all()
                ?: collect(config('auth.providers'))->keys()->all(),
            config('auth.guards.api.provider')
        	);
		}

		return $provider;
	}

    /**
     * Create a client credentials grant client.
     */
    protected function createClientCredentialsClient(): Client
    {
        return $this->clients->createClientCredentialsGrantClient($this->option('name'));
    }

    /**
     * Create an implicit grant client.
     */
    protected function createImplicitClient(): Client
    {
        $redirect = $this->option('redirect-uri') ?: $this->ask(
            'Where should we redirect the request after authorization?',
            url('/auth/callback')
        );

        return $this->clients->createImplicitGrantClient(
			$this->option('name'),
			explode(',', $redirect)
		);
    }

    /**
     * Create a device code client.
     */
    protected function createDeviceCodeClient(): Client
    {
        $confidential = $this->hasOption('public')
            ? ! $this->option('public')
            : $this->confirm('Would you like to make this client confidential?');

        return $this->clients->createDeviceAuthorizationGrantClient($this->option('name'), $confidential);
    }

    /**
     * Create an authorization code client.
     */
    protected function createAuthCodeClient(): Client
    {
        $redirect = $this->option('redirect-uri') ?: $this->ask(
            'Where should we redirect the request after authorization?',
            url('/auth/callback')
        );

        $confidential = $this->hasOption('public')
            ? ! $this->option('public')
            : $this->confirm('Would you like to make this client confidential?');

        $enableDeviceFlow = Vollmacht::$deviceCodeGrantEnabled &&
            $this->confirm('Would you like to enable the device authorization flow for this client?', 'n');

        return $this->clients->createAuthorizationCodeGrantClient(
            $this->option('name'),
			explode(',', $redirect), $confidential, null, $enableDeviceFlow
        );
    }
}
