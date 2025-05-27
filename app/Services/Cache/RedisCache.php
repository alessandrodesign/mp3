<?php

namespace App\Services\Cache;

use Core\Contracts\CacheInterface;
use Predis\Client;

class RedisCache implements CacheInterface
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'scheme' => 'tcp',
            'host' => REDIS_HOST,
            'port' => REDIS_PORT,
        ]);
        $this->client->connect();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->client->get($key);
        if ($value === null) {
            return $default;
        }
        return unserialize($value);
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $serialized = serialize($value);
        if ($ttl !== null) {
            $result = $this->client->setex($key, $ttl, $serialized);
        } else {
            $result = $this->client->set($key, $serialized);
        }
        return $result === true || $result === 'OK';
    }

    public function delete(string $key): bool
    {
        return $this->client->del([$key]) > 0;
    }

    public function clear(): bool
    {
        $this->client->flushdb();
        return true;
    }

    public function has(string $key): bool
    {
        return $this->client->exists($key) === 1;
    }
}