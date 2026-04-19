<?php

use Nette\Schema\Expect;

return Expect::structure([
	'private_key'                    => Expect::string()->nullable(),
	'public_key'                     => Expect::string()->nullable(),
	'keys_path'                      => Expect::string()->nullable(),
	'algorithm'                      => Expect::string('RS256'),
	'access_token_lifetime'          => Expect::int(HOUR),
	'refresh_token_lifetime'         => Expect::int(30 * DAY),
	'auth_codes_lifetime'            => Expect::int(10 * MINUTE),
	'personal_access_token_lifetime' => Expect::int(YEAR),
	'scopes'                         => Expect::arrayOf('string', 'string')->default([]),
	'default_scopes'                 => Expect::listOf('string')->default([]),
	'middlewares'                     => Expect::array()->default(['api' => [], 'web' => []]),
	'device_code_grant_enabled'      => Expect::bool(true),
	'registers_json_api_routes'      => Expect::bool(true),
	'views'                          => Expect::arrayOf('string', 'string')->default([]),
	'routes'                         => Expect::structure([
		'enable'    => Expect::bool(true),
		'prefix'    => Expect::string('oauth'),
		'namespace' => Expect::string('BlitzPHP\Vollmacht\Controllers'),
	]),
])->otherItems();
