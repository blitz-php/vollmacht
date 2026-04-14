<?php

namespace BlitzPHP\Vollmacht\Controllers;

use BlitzPHP\Http\Redirection;
use BlitzPHP\Schild\Authentication\AuthenticatorInterface;
use BlitzPHP\Utilities\String\Text;
use BlitzPHP\Vollmacht\Bridge\DeviceCodeRepository;
use BlitzPHP\Vollmacht\Contracts\DeviceAuthorizationViewResponse;
use BlitzPHP\Vollmacht\Repositories\ClientRepository;
use BlitzPHP\Vollmacht\Vollmacht;
use League\OAuth2\Server\Entities\DeviceCodeEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;

class DeviceAuthorizationController extends BaseController
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected AuthenticatorInterface $authenticator,
        protected DeviceCodeRepository $deviceCodes,
        protected ClientRepository $clients,
    ) {
    }

    /**
     * Authorize a device to access the user's account.
     */
    public function __invoke(DeviceAuthorizationViewResponse $viewResponse): Redirection|DeviceAuthorizationViewResponse
	{
        if (! $userCode = $this->request->query('user_code')) {
            return redirect()->route('vollmacht.device');
        }

        $deviceCode = $this->deviceCodes->getDeviceCodeEntityByUserCode(
            str_replace('-', '', $userCode)
        );

        if (! $deviceCode) {
            return redirect()->route('vollmacht.device')
                ->withInput(['user_code' => $userCode])
                ->withErrors([
                    'user_code' => 'Incorrect code.',
                ]);
        }

        $user = $this->authenticator->getUser();
        $deviceCode->setUserIdentifier($user->id);

        $scopes = $this->parseScopes($deviceCode);
        $client = $this->clients->find($deviceCode->getClient()->getIdentifier());

        $this->request->session()->put('authToken', $authToken = Text::random());
        $this->request->session()->put('deviceCode', serialize($deviceCode));

        return $viewResponse->withParameters([
            'client' => $client,
            'user' => $user,
            'scopes' => $scopes,
            'request' => $this->request,
            'authToken' => $authToken,
        ]);
    }

    /**
     * Transform the device code entity's scopes into Scope instances.
     *
     * @return \BlitzPHP\Vollmacht\Scope[]
     */
    protected function parseScopes(DeviceCodeEntityInterface $deviceCode): array
    {
        return Vollmacht::scopesFor(
            collect($deviceCode->getScopes())->map(
                fn (ScopeEntityInterface $scope): string => $scope->getIdentifier()
            )->unique()->all()
        );
    }
}
