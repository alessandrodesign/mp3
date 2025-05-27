<?php

namespace App\Services\Cache;

use Core\Contracts\CacheInterface;

class ArrayCache implements CacheInterface
{
    private array $cache = [];
    private array $expirations = [];

    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->has($key)) {
            return $default;
        }
        return $this->cache[$key];
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $this->cache[$key] = $value;
        if ($ttl !== null) {
            $this->expirations[$key] = time() + $ttl;
        } else {
            unset($this->expirations[$key]);
        }
        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->cache[$key], $this->expirations[$key]);
        return true;
    }

    public function clear(): bool
    {
        $this->cache = [];
        $this->expirations = [];
        return true;
    }

    public function has(string $key): bool
    {
        if (!array_key_exists($key, $this->cache)) {
            return false;
        }
        if (isset($this->expirations[$key]) && $this->expirations[$key] < time()) {
            $this->delete($key);
            return false;
        }
        return true;
    }
}