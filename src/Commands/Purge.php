<?php

namespace BlitzPHP\Vollmacht\Commands;

use BlitzPHP\Cli\Console\Command;
use BlitzPHP\Utilities\DateTime\Date;
use BlitzPHP\Vollmacht\Vollmacht;
use BlitzPHP\Wolke\Builder;

class Purge extends Command
{
	/**
	 * {@inheritDoc}
	 */
	protected string $name = 'vollmacht:purge';

    /**
	 * {@inheritDoc}
     */
    protected $description = 'Purge revoked and / or expired tokens and authentication codes';

    /**
     * {@inheritDoc}
     */
    protected array $array = [
		'--revoked' => 'Only purge revoked tokens and authentication codes',
		'--expired' => 'Only purge expired tokens and authentication codes',
		'--hours'   => ['The number of hours to retain expired tokens', 168],
	];

    /**
     * {@inheritDoc}
     */
    public function handle()
    {
        $revoked = $this->option('revoked') || ! $this->option('expired');

        $expired = $this->option('expired') || ! $this->option('revoked')
            ? Date::now()->subHours($this->option('hours'))
            : false;

        $constraint = fn (Builder $query): Builder => $query
            ->when($revoked, fn () => $query->orWhere('revoked', true))
            ->when($expired, fn () => $query->orWhere('expires_at', '<', $expired));

        Vollmacht::token()->newQuery()->where($constraint)->delete();
        Vollmacht::authCode()->newQuery()->where($constraint)->delete();
        Vollmacht::refreshToken()->newQuery()->where($constraint)->delete();

        if (Vollmacht::$deviceCodeGrantEnabled) {
            Vollmacht::deviceCode()->newQuery()->where($constraint)->delete();
        }

        $this->info(sprintf('Purged %s.', implode(' and ', array_filter([
            $revoked ? 'revoked items' : null,
            $expired ? "items expired for more than {$expired->diffForHumans()}" : null,
        ]))));
    }
}
