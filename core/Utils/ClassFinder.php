<?php

namespace Core\Utils;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use ReflectionClass;

class ClassFinder
{
    /**
     * Encontra todas as classes concretas (não abstratas, não interfaces) dentro de um diretório.
     *
     * @param string $directory Diretório base para busca (ex: app/Controllers)
     * @param string $baseNamespace Namespace base correspondente ao diretório (ex: "App\\Controllers")
     * @return string[] Lista de classes completas (com namespace)
     */
    public static function findConcreteClasses(string $directory, string $baseNamespace): array
    {
        $classes = [];

        $dirIterator = new RecursiveDirectoryIterator($directory);
        $iterator = new RecursiveIteratorIterator($dirIterator);
        $regex = new RegexIterator($iterator, '/^.+\.php$/i', RegexIterator::GET_MATCH);

        foreach ($regex as $files) {
            foreach ($files as $file) {
                // Obtem o namespace e nome da classe do arquivo
                $class = self::getFullClassNameFromFile($file);
                if ($class === null) {
                    continue;
                }

                // Verifica se a classe está dentro do namespace base
                if (str_starts_with($class, $baseNamespace)) {
                    if (class_exists($class)) {
                        $ref = new ReflectionClass($class);
                        if (!$ref->isAbstract() && !$ref->isInterface()) {
                            $classes[] = $class;
                        }
                    }
                }
            }
        }

        return $classes;
    }

    /**
     * Extrai o nome completo da classe (com namespace) de um arquivo PHP.
     *
     * @param string $filePath
     * @return string|null
     */
    private static function getFullClassNameFromFile(string $filePath): ?string
    {
        $src = file_get_contents($filePath);
        $namespace = null;
        $class = null;

        // Extrai namespace
        if (preg_match('/namespace\s+([^;]+);/', $src, $matches)) {
            $namespace = trim($matches[1]);
        }

        // Extrai nome da classe
        if (preg_match('/class\s+([^\s{]+)/', $src, $matches)) {
            $class = trim($matches[1]);
        }

        if ($class === null) {
            return null;
        }

        return $namespace ? $namespace . '\\' . $class : $class;
    }
}