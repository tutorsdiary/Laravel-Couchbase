<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: crazyluv
 * Date: 18. 8. 9
 * Time: 오후 5:31
 */

namespace Tuda\LaravelCouchbase\Eloquent;

use Tuda\LaravelCouchbase\Eloquent\Queue\CouchbaseConnector as QueueConnector;
use Illuminate\Database\DatabaseManager;
use Illuminate\Queue\QueueManager;
use Illuminate\Session\CacheBasedSessionHandler;
use Ytake\LaravelCouchbase\Database\Connectable;
use Ytake\LaravelCouchbase\Database\CouchbaseConnector;
use Ytake\LaravelCouchbase\MemcachedConnector;

class CouchbaseServiceProvider extends \Ytake\LaravelCouchbase\CouchbaseServiceProvider
{
    /**
     * Bootstrap  application services.
     */
    public function boot()
    {
        $this->registerCouchbaseBucketCacheDriver();
        $this->registerMemcachedBucketCacheDriver();
        $this->registerCouchbaseQueueDriver();

        Model::setEventDispatcher($this->app['events']);
    }

    protected function registerCouchbaseComponent()
    {
        $this->app->singleton(Connectable::class, function () {
            return new CouchbaseConnector();
        });

        $this->app->singleton('couchbase.memcached.connector', function () {
            return new MemcachedConnector();
        });

        // add couchbase session driver
        $this->app['session']->extend('couchbase', function ($app) {
            $minutes = $app['config']['session.lifetime'];

            return new CacheBasedSessionHandler(clone $this->app['cache']->driver('couchbase'), $minutes);
        });

        // add couchbase session driver
        $this->app['session']->extend('couchbase-memcached', function ($app) {
            $minutes = $app['config']['session.lifetime'];

            return new CacheBasedSessionHandler(clone $this->app['cache']->driver('couchbase-memcached'), $minutes);
        });

        // add couchbase extension
        $this->app['db']->extend('couchbase', function (array $config, $name) {
            /* @var \Couchbase\Cluster $cluster */
            return new CouchbaseConnection($config, $name);
        });
    }

    /**
     * register custom queue 'couchbase' driver
     */
    protected function registerCouchbaseQueueDriver(): void
    {
        /** @var QueueManager $queueManager */
        $queueManager = $this->app['queue'];
        $queueManager->addConnector('couchbase', function () {
            /** @var DatabaseManager $databaseManager */
            $databaseManager = $this->app['db'];

            return new QueueConnector($databaseManager);
        });
    }
}
