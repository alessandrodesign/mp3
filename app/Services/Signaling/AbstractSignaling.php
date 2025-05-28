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
        $kill = false;

        // Verifica se o arquivo PID existe
        if (file_exists($this->pid)) {
            $pid = trim(file_get_contents($this->pid));

            // Garante que o PID seja numérico antes de tentar matar
            if (ctype_digit($pid)) {

                // Tenta usar posix_kill se estiver disponível
                if (function_exists('posix_kill')) {
                    $kill = posix_kill((int)$pid, SIGKILL);
                } // Se posix_kill não estiver disponível, usa exec()
                elseif (function_exists('exec')) {
                    exec("kill -9 $pid 2>&1", $output, $resultCode);
                    $kill = $resultCode === 0;
                } // Último recurso: usa shell_exec()
                else {
                    $retorno = shell_exec("kill -9 $pid 2>&1");
                    $kill = empty($retorno); // Se não houver saída, assumimos que deu certo
                }

                // Se o processo foi finalizado, remove o arquivo .pid
                if ($kill) {
                    @unlink($this->pid);
                }
            }
        }

        return $kill;
    }

}