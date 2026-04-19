<?php

namespace BlitzPHP\Vollmacht\Commands;

use BlitzPHP\Cli\Console\Command;
use BlitzPHP\Vollmacht\Patchs\Rsa\KeyPair;
use BlitzPHP\Vollmacht\Vollmacht;

class Keys extends Command
{
	/**
	 * {@inheritDoc}
	 */
	protected string $name = 'vollmacht:keys';

	/**
     * {@inheritDoc}
     */
    protected string $description = 'Create the encryption keys for API authentication';

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
    public function handle(): int
    {
        [$publicKey, $privateKey] = [
            Vollmacht::keyPath('oauth-public.key'),
            Vollmacht::keyPath('oauth-private.key'),
        ];

        if ((file_exists($publicKey) || file_exists($privateKey)) && ! $this->option('force')) {
            $this->error('Encryption keys already exist. Use the --force option to overwrite them.');

            return EXIT_ERROR;
        }

		(new KeyPair(
			privateKeyBits: (int) $this->option('length'),
		))->generate(
			$privateKey, $publicKey
		);

        if (! is_windows()) {
            chmod($publicKey, 0660);
            chmod($privateKey, 0600);
        }

        $this->success('Encryption keys generated successfully.');

        return EXIT_SUCCESS;
    }
}
