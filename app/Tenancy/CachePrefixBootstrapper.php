<?php

namespace App\Tenancy;

use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Foundation\Application;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;

/**
 * Scopes cache keys to the current school by changing the cache prefix.
 *
 * Works with every cache store (database, file, redis), unlike the stock
 * tag-based bootstrapper which needs a store that supports tags.
 */
class CachePrefixBootstrapper implements TenancyBootstrapper
{
    protected ?string $originalPrefix = null;

    public function __construct(protected Application $app) {}

    public function bootstrap(Tenant $tenant): void
    {
        $this->originalPrefix = (string) $this->app['config']->get('cache.prefix');

        $this->setPrefix($this->originalPrefix.'school_'.$tenant->getTenantKey().'_');
    }

    public function revert(): void
    {
        $this->setPrefix((string) $this->originalPrefix);
    }

    protected function setPrefix(string $prefix): void
    {
        $this->app['config']->set('cache.prefix', $prefix);

        /** @var CacheManager $cache */
        $cache = $this->app['cache'];
        $cache->forgetDriver($this->app['config']->get('cache.default'));

        $this->app->forgetInstance('cache.store');
    }
}
