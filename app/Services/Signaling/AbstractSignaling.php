<?php

namespace App\Services\Signaling;

abstract class AbstractSignaling
{
    private string $pid;

    protected function createPidFile(string $class): void
    {
        $this->pid = __DIR__ . DS . strtolower(basename(str_replace('\\', DS, $class))) . '.pid';
    }

    protected function createPid(): void
    {
        file_put_contents($this->pid, getmypid());
    }

    /**
     * Encerra o processo cujo PID está armazenado no arquivo.
     *
     * @return bool True se o processo foi finalizado com sucesso, false caso contrário.
     */
    public function stop(): bool
    {
        if (!file_exists($this->pid)) {
            return true;
        }

        $kill = false;

        $pid = trim(file_get_contents($this->pid));

        if (!ctype_digit($pid)) {
            return false;
        }

        if ($this->isWindows()) {
            $command = "taskkill /F /PID $pid";
        } else {
            $this->usePosixKill($pid, $kill);
            $command = "kill -9 $pid 2>&1";
        }

        if (!$kill) {
            $this->useExec($command, $kill);
            $this->useShellExec($command, $kill);
        }

        if ($kill) {
            @unlink($this->pid);
        }

        return $kill;
    }

    protected function isWindows(): bool
    {
        return stripos(PHP_OS, 'WIN') === 0;
    }

    protected function useExec(string $command, bool &$kill = false): void
    {
        if (!$kill && function_exists('exec')) {
            exec($command, $outputLines, $resultCode);
            $kill = $resultCode === 0;
        }
    }

    protected function useShellExec(string $command, bool &$kill = false): void
    {
        if (!$kill && function_exists('shell_exec')) {
            $retorno = shell_exec($command);
            $kill = empty($retorno);
        }
    }

    protected function usePosixKill(int $pid, bool &$kill = false): void
    {
        if (!$kill && function_exists('posix_kill')) {
            $kill = posix_kill($pid, SIGKILL);
        }
    }
}