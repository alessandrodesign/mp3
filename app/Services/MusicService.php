<?php

namespace App\Services;

use Core\Utils\Crypt;
use Exception;

class MusicService
{
    private string $musicDirectory;
    private array $musicMap = [];

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->musicDirectory = rtrim(PATH_MUSIC, '/\\');
        $this->loadMusics();
    }

    public function getMusicDirectory(): string
    {
        return $this->musicDirectory;
    }

    /**
     * Carrega músicas do diretório e monta o mapa de criptografias
     * @throws Exception
     */
    private function loadMusics(): void
    {
        $files = scandir($this->musicDirectory);
        foreach ($files as $file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            if ($this->isAllowedExtension($extension)) {
                $originalName = pathinfo($file, PATHINFO_BASENAME);
                $encrypted = Crypt::getInstance()->encrypt($originalName);
                $this->musicMap[$encrypted] = $originalName;
            }
        }
    }

    /**
     * Retorna lista de músicas
     */
    public function list(): array
    {
        $result = [];
        foreach ($this->musicMap as $encrypted => $original) {
            $result[] = [
                'original' => $original,
                'encrypted' => $encrypted,
            ];
        }
        return $result;
    }

    /**
     * @param string|array $encryptedName
     * @param bool $returnMimeType
     * @param bool $onlyName
     * @param bool $showExtension
     * @return string|null
     * @throws Exception
     */
    public function get(string|array $encryptedName, bool $returnMimeType = false, bool $onlyName = false, bool $showExtension = true): ?string
    {
        // Descriptografa o nome da música
        $fileName = Crypt::getInstance()->decrypt($encryptedName);

        // Caminho base onde estão armazenadas as músicas
        $basePath = $this->getMusicDirectory();

        // Caminho completo do arquivo
        $fullPath = realpath($basePath . DIRECTORY_SEPARATOR . $fileName);

        // Verifica se o arquivo existe e está dentro do diretório permitido
        if ($fullPath && str_starts_with($fullPath, realpath($basePath)) && file_exists($fullPath)) {
            if ($returnMimeType) {
                return mime_content_type($fullPath);
            }

            if ($onlyName) {
                return $showExtension ? basename($fullPath) : pathinfo($fullPath, PATHINFO_FILENAME);
            }

            return $fullPath;
        }

        return null;
    }


    /**
     * Verifica se uma ou mais extensões são permitidas
     *
     * @param string|array $extension Extensão única ou array de extensões
     * @return bool True se todas as extensões forem permitidas
     */
    private function isAllowedExtension(array|string $extension): bool
    {
        $allowedExtensions = ["mp3", "m4a", "ogg"];

        if (is_array($extension)) {
            // Retorna true somente se todas forem permitidas
            return count(array_diff($extension, $allowedExtensions)) === 0;
        }

        return in_array($extension, $allowedExtensions, true);
    }

    public function removeExtension(string $music): string
    {
        return pathinfo($music, PATHINFO_FILENAME);
    }
}