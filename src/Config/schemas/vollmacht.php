<?php

use Nette\Schema\Expect;

return Expect::structure([
	'private_key'                    => Expect::string()->required(),
	'public_key'                     => Expect::string()->required(),
	'algorithm'                      => Expect::string('RS256'),
	'access_token_lifetime'          => Expect::int(HOUR),
	'refresh_token_lifetime'         => Expect::int(30 * DAY),
	'auth_codes_lifetime'            => Expect::int(10 * MINUTE),
	'personal_access_token_lifetime' => Expect::int(YEAR),
	'scopes'                         => Expect::arrayOf('string', 'string')->default([]),
	'default_scopes'                 => Expect::listOf('string')->default([]),
	'user_entity'                    => Expect::string()->default(\BlitzPHP\Vollmacht\Entities\User::class),
	'routes'                         => Expect::bool(true),
	'middleware'                     => Expect::array()->default(['api' => [], 'web' => ['session']]),
])->otherItems();
