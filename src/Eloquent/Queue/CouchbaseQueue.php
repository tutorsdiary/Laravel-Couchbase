<?php


namespace Tuda\LaravelCouchbase\Eloquent\Queue;

use Tuda\LaravelCouchbase\Eloquent\CouchbaseConnection;
use Illuminate\Database\Query\Builder;
use Illuminate\Queue\Jobs\DatabaseJob;
use Illuminate\Queue\Jobs\DatabaseJobRecord;
use Illuminate\Support\Facades\DB;
use \Ytake\LaravelCouchbase\Queue\CouchbaseQueue as Queue;

class CouchbaseQueue extends Queue
{

    /**
     * {@inheritdoc}
     */
    protected function getNextAvailableJob($queue)
    {
        $job = $this->database->table($this->table)->from(null)
            ->where('queue', $this->getQueue($queue))
            ->where(function (Builder $query) {
                $this->isAvailable($query);
                $this->isReservedButExpired($query);
            })
            ->orderBy('id', 'asc')
            ->first(['*', DB::raw('meta().id')]);

        return $job ? new DatabaseJobRecord((object)$job) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteReserved($queue, $id)
    {
        $this->database->table($this->table)->from(null)->where('id', $id)->delete();
    }

    /**
     * {@inheritdoc}
     */
    protected function pushToDatabase($queue, $payload, $delay = 0, $attempts = 0)
    {
        $attributes = $this->buildDatabaseRecord(
            $this->getQueue($queue), $payload, $this->availableAt($delay), $attempts
        );
        $increment = $this->incrementKey();
        $attributes['id'] = $increment;
        $result = $this->database->table($this->table)->from(null)
            ->key($this->uniqueKey($attributes))->insert($attributes);
        if ($result) {
            return $increment;
        }

        return false;
    }
}
