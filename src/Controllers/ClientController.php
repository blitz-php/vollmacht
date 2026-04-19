<?php

namespace BlitzPHP\Vollmacht\Controllers;

use BlitzPHP\Contracts\Http\StatusCode;
use BlitzPHP\Http\Response;
use BlitzPHP\Vollmacht\Entities\Client;
use BlitzPHP\Vollmacht\Repositories\ClientRepository;
use BlitzPHP\Wolke\Collection;

/**
 * @deprecated Will be removed in a future Laravel version.
 */
class ClientController extends BaseController
{
	/**
     * Create a client controller instance.
     */
    public function __construct(protected ClientRepository $clients)
	{

    }

    /**
     * Get all of the clients for the authenticated user.
     *
     * @return Collection<int, Client>
     */
    public function forUser(): Collection
    {
        return $this->clients->forUser($this->authenticator->getUser());
    }

    /**
     * Store a new client.
     */
    public function store(): Client
    {
        $post = $this->validate([
            'name'         => ['required', 'string', 'max:255'],
            'redirect'     => ['required', 'url:http,https'],
            'confidential' => 'boolean',
        ])->toArray();

        $client = $this->clients->createAuthorizationCodeGrantClient(
            $post['name'],
            explode(',', $post['redirect']),
            $confidential = $this->request->boolean('confidential', true),
            $this->authenticator->getUser(),
        );

        return $confidential ? $client->append(['plain_secret']) : $client;
    }

    /**
     * Update the given client.
     */
    public function update(string|int $clientId): Response|Client
    {
        $client = $this->clients->findForUser($clientId, $this->authenticator->getUser());

        if (! $client) {
            return (new Response())->withStatus(404);
        }

        $post = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'redirect' => ['required', 'url:http,https'],
        ])->toArray();

        $this->clients->update(
            $client, $post['name'], explode(',', $post['redirect'])
        );

        return $client;
    }

    /**
     * Delete the given client.
     */
    public function destroy(string|int $clientId): Response
    {
        $client = $this->clients->findForUser($clientId, $this->authenticator->getUser());

        if (! $client) {
			return (new Response())->withStatus(StatusCode::NOT_FOUND);
        }

        $this->clients->delete($client);

        return (new Response())->withStatus(StatusCode::NO_CONTENT);
    }
}
