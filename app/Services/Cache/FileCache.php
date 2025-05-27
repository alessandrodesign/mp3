<?php

namespace App\Services\Cache;

use Core\Contracts\CacheInterface;
use Core\Utils\Directories;

class FileCache implements CacheInterface
{
    private string $cacheDir;

    public function __construct()
    {
        $cacheDir = PATH_CACHE . "system";
        $this->cacheDir = rtrim($cacheDir, DS);
        Directories::validAndCreate($this->cacheDir);
    }

    private function getFilePath(string $key): string
    {
        return $this->cacheDir . DIRECTORY_SEPARATOR . md5($key) . '.cache';
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->getFilePath($key);
        if (!file_exists($file)) {
            return $default;
        }

        $data = unserialize(file_get_contents($file));
        if ($data['expires_at'] !== null && $data['expires_at'] < time()) {
            $this->delete($key);
            return $default;
        }

        return $data['value'];
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $file = $this->getFilePath($key);
        $expiresAt = $ttl !== null ? time() + $ttl : null;
        $data = serialize(['value' => $value, 'expires_at' => $expiresAt]);
        return file_put_contents($file, $data) !== false;
    }

    public function delete(string $key): bool
    {
        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            return unlink($file);
        }
        return false;
    }

    public function clear(): bool
    {
        $files = glob($this->cacheDir . DIRECTORY_SEPARATOR . '*.cache');
        $success = true;
        foreach ($files as $file) {
            if (!unlink($file)) {
                $success = false;
            }
        }
        return $success;
    }

    public function has(string $key): bool
    {
        $file = $this->getFilePath($key);
        if (!file_exists($file)) {
            return false;
        }
        $data = unserialize(file_get_contents($file));
        if ($data['expires_at'] !== null && $data['expires_at'] < time()) {
            $this->delete($key);
            return false;
        }
        return true;
    }
}