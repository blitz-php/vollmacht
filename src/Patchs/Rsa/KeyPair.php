<?php

namespace BlitzPHP\Vollmacht\Patchs\Rsa;

use Exception;

class KeyPair extends \Spatie\Crypto\Rsa\KeyPair
{
    protected string $configPath = __DIR__ . '/openssl.cnf';
    protected ?string $password = null;

    public function configPath(string $path): static
    {
        $this->configPath = $path;

		return $this;
    }

    public function generate(string $privateKeyPath = '', string $publicKeyPath = ''): array
    {
        try {
            return parent::generate($privateKeyPath, $publicKeyPath);
        } catch (Exception $e) {
            if (!str_contains($e->getMessage(), 'openssl_pkey_export()')) {
                throw $e;
            }

			return $this->generateWithConfig($this->configPath, $privateKeyPath, $publicKeyPath);
        }
    }

	public function generateWithConfig(string $configPath, string $privateKeyPath = '', string $publicKeyPath = ''): array
	{
		if (!file_exists($configPath)) {
			throw new Exception("Fichier de configuration OpenSSL introuvable : " . $configPath);
		}
		if (!is_readable($configPath)) {
			throw new Exception("Fichier de configuration OpenSSL non lisible : " . $configPath);
		}

		return $this->generateWithOptions(['config' => $configPath], $privateKeyPath, $publicKeyPath);
	}

	public function generateWithOptions(array $options = [], string $privateKeyPath = '', string $publicKeyPath = ''): array
	{
		while (openssl_error_string()) {}

		$config = array_merge($options, [
			'digest_alg'       => $this->digestAlgorithm,
			'private_key_bits' => $this->privateKeyBits,
			'private_key_type' => $this->privateKeyType,
		]);
		
		if (false === $asymmetricKey = openssl_pkey_new($config)) {
			$error = openssl_error_string();
			throw new Exception("openssl_pkey_new a échoué : " . ($error ?: "erreur inconnue"));
		}

		// Exportation de la clé privée AVEC la même configuration
		$exported = openssl_pkey_export(
			$asymmetricKey,
			$privateKey,
			$this->password,
			$options,
		);

		if ($exported === false) {
			$error = openssl_error_string();
			throw new Exception("openssl_pkey_export a échoué : " . ($error ?: "erreur inconnue"));
		}

		if (empty($privateKey)) {
			throw new Exception("La clé privée exportée est vide");
		}

		// Récupération de la clé publique
		$rawPublicKey = openssl_pkey_get_details($asymmetricKey);
		if ($rawPublicKey === false) {
			$error = openssl_error_string();
			throw new Exception("Impossible d'obtenir les détails de la clé : " . ($error ?: "erreur inconnue"));
		}
		$publicKey = $rawPublicKey['key'];

		if ($privateKeyPath !== '') {
			file_put_contents($privateKeyPath, $privateKey);
		}
		if ($publicKeyPath !== '') {
			file_put_contents($publicKeyPath, $publicKey);
		}

		return [$privateKey, $publicKey];
	}
}
