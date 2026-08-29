<?php

namespace App\Modules\Transaction\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class DistributedLockService
{
    private $defaultTTL = 10;
    private $maxWaitTime = 5;
    
    /**
     * ✅ Acquire lock with automatic retry
     */
    public function acquire($key, $ttl = null, $waitTime = null)
    {
        if (! extension_loaded('redis') || ! class_exists('Redis')) {
            return null;
        }

        $ttl = $ttl ?? $this->defaultTTL;
        $waitTime = $waitTime ?? $this->maxWaitTime;
        $lockKey = "lock:{$key}";
        $lockValue = Str::random(32);
        $start = time();
        
        while (time() - $start < $waitTime) {
            $acquired = Redis::set($lockKey, $lockValue, 'NX', 'EX', $ttl);
            
            if ($acquired) {
                return $lockValue;
            }
            
            // Check if lock is stale
            $currentLock = Redis::get($lockKey);
            if ($currentLock) {
                $ttlRemaining = Redis::ttl($lockKey);
                if ($ttlRemaining < 0) {
                    Redis::del($lockKey);
                    continue;
                }
            }
            
            usleep(100000); // 100ms
        }
        
        throw new \Exception('Could not acquire lock: ' . $key, 409);
    }
    
    /**
     * ✅ Release lock atomically
     */
    public function release($key, $lockValue)
    {
        if (! extension_loaded('redis') || ! class_exists('Redis')) {
            return false;
        }

        $lockKey = "lock:{$key}";
        
        $script = "
            if redis.call('get', KEYS[1]) == ARGV[1] then
                return redis.call('del', KEYS[1])
            else
                return 0
            end
        ";
        
        return Redis::eval($script, 1, $lockKey, $lockValue);
    }
}