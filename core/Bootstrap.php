<?php

namespace Core;

use Core\Utils\Directories;
use Exception;
use RuntimeException;
use Symfony\Component\Dotenv\Dotenv;
use Throwable;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

class Bootstrap
{
    const string ENV_DEV = 'development';
    const string ENV_PROD = 'production';
    const string ENV_TEST = 'test';
    const array SUPPORTED_ENVS = [
        self::ENV_DEV,
        self::ENV_PROD,
        self::ENV_TEST,
    ];

    /**
     * Instância única do Bootstrap.
     *
     * @var $instance self|null
     */
    private static ?self $instance = null;
    private string $basePath;
    private array $defaultConstants = [
        'DS' => DIRECTORY_SEPARATOR,
    ];

    /**
     * Construtor privado para evitar instância direta.
     * @throws Throwable
     */
    private function __construct()
    {
        $this->setPaths();
        $this->loadEnvironment();
        $this->loadConstants();
        $this->setupDefinitions();
        $this->startApp();
    }

    /**
     * Retorna a instância atual.
     *
     * @return self
     * @throws RuntimeException
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Retorna o caminho base absoluto da aplicação.
     *
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Inicia a aplicação e retorna a instância única.
     *
     * @return self
     */
    public static function run(): self
    {
        return self::getInstance();
    }

    /**
     * Proíbe clonagem da instância.
     *
     * @return void
     * @throws RuntimeException
     */
    private function __clone(): void
    {
        throw new RuntimeException("Cloning Bootstrap is not allowed.");
    }

    /**
     * Proíbe unserialize da instância.
     *
     * @return void
     * @throws RuntimeException
     */
    public function __wakeup(): void
    {
        throw new RuntimeException("Bootstrap is not allowed.");
    }

    /**
     * Define os caminhos principais da aplicação e os adiciona ao array de constantes padrão.
     *
     * @return void
     */
    private function setPaths(): void
    {
        $this->basePath = rtrim(dirname(__DIR__), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        $paths = [
            'PATH_ROOT' => $this->basePath,
            'PATH_PUBLIC' => $this->basePath . 'public' . DIRECTORY_SEPARATOR,
            'PATH_STORAGE' => $this->basePath . 'storage' . DIRECTORY_SEPARATOR,
            'PATH_TRANSLATIONS' => $this->basePath . 'translations' . DIRECTORY_SEPARATOR,
            'PATH_HELPERS' => $this->basePath . 'helpers' . DIRECTORY_SEPARATOR,
            'PATH_MUSIC' => $this->basePath . 'storage' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'music' . DIRECTORY_SEPARATOR,
            'PATH_CACHE' => $this->basePath . 'storage' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR,
            'PATH_LOG' => $this->basePath . 'storage' . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR,
            'PATH_VIEWS' => $this->basePath . 'resource' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR,
            'PATH_CONTROLLERS' => $this->basePath . 'app' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR,
            'PATH_MIDDLEWARES' => $this->basePath . 'app' . DIRECTORY_SEPARATOR . 'Middlewares' . DIRECTORY_SEPARATOR,
            'PATH_MODElS' => $this->basePath . 'app' . DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR,
            'PATH_SERVICES' => $this->basePath . 'app' . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR,
            'PATH_PROVIDERS' => $this->basePath . 'app' . DIRECTORY_SEPARATOR . 'Providers' . DIRECTORY_SEPARATOR,
        ];

        $this->defaultConstants = array_merge($this->defaultConstants, $paths);
    }

    /**
     * Carrega as variáveis de ambiente do arquivo .env usando symfony/dotenv
     *
     * @return void
     */
    private function loadEnvironment(): void
    {
        $dotenv = new Dotenv();
        $envFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
        if (!file_exists($envFile)) {
            throw new RuntimeException(sprintf(".env not found in %s", $envFile));
        }

        $dotenv->load($envFile);

        foreach ($_ENV as $key => $value) {
            if (!defined($key)) {
                define($key, $value);
            }
        }
    }

    /**
     * Carrega constantes globais definidas no arquivo .constants
     *
     * O formato do arquivo deve ser: CHAVE=valor (um por linha)
     *
     * @return void
     */
    private function loadConstants(): void
    {
        $pathConsts = __DIR__
            . DIRECTORY_SEPARATOR . "Config"
            . DIRECTORY_SEPARATOR . '.constants';

        if (!file_exists($pathConsts)) {
            throw new RuntimeException(sprintf(".constants not found in %s", $pathConsts));
        }

        $lines = file($pathConsts, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);

            $key = trim($key);
            $value = trim($value);

            if (!defined($key)) {
                define($key, $value);
            }
        }

        foreach ($this->defaultConstants as $key => $value) {
            if (!defined($key)) {
                define($key, $value);
            }
        }

        if (defined('SECRET_KEY')) {
            define('SECRET_KEY_GLOBAL', hex2bin(SECRET_KEY));
        }
    }

    /**
     * Define configurações essenciais globais para a aplicação.
     *
     * Inclui:
     * - Definição de timezone padrão do PHP para evitar warnings e inconsistências
     * - Definição de locale para formatação correta de datas, números e strings
     * - Definições de headers de segurança comum (para serem usados na resposta HTTP)
     * - Outras configurações importantes para desempenho e segurança
     *
     * @return void
     */
    private function setupDefinitions(): void
    {
        // Define timezone padrão (exemplo: America/Sao_Paulo)
        date_default_timezone_set(TIMEZONE ?? 'America/Sao_Paulo');

        // Define locale para pt_BR (Brasil), pode afetar funções de formatação regional
        setlocale(LC_ALL, 'pt_BR.UTF-8', 'pt_BR', 'portuguese');

        // Headers básicos de segurança
        if (!defined('SECURE_HEADERS_SET')) {
            define('SECURE_HEADERS_SET', true);
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: no-referrer-when-downgrade');
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }

        // Configurações PHP para desempenho
        ini_set('memory_limit', '256M');
        ini_set('max_execution_time', '60');
        ini_set('realpath_cache_size', '4096k');
        ini_set('realpath_cache_ttl', '600');
        ini_set('disable_functions', 'exec,passthru,shell_exec,system');
        ini_set('session.gc_maxlifetime', '1440');
        ini_set('session.use_strict_mode', '1');
        ini_set('max_input_vars', '3000');

        // OPCache (verifica se existe a função)
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        ini_set('opcache.enable', '1');
        ini_set('opcache.memory_consumption', '128');
        ini_set('opcache.interned_strings_buffer', '8');
        ini_set('opcache.max_accelerated_files', '4000');
        ini_set('opcache.revalidate_freq', '2');
    }


    /**
     * Configura o tratamento de exceções e erros da aplicação.
     *
     * - Se estiver em ambiente de desenvolvimento (dev), ativa o Whoops para exibir páginas
     *   de erro amigáveis e detalhadas, facilitando o debug.
     * - Em todos os ambientes, define as diretivas do PHP para não exibir erros na tela,
     *   mas registrá-los em arquivo de log.
     * - Define o nível de relatório de erros ignorando notices, deprecated e strict.
     *
     * @return void
     */
    public static function setupExceptions(): void
    {
        // Ativa Whoops apenas em DEV ou TEST
        if (self::isDev() || self::isTest()) {
            if (class_exists(Run::class) && class_exists(PrettyPageHandler::class)) {
                $whoops = new Run();
                $whoops->pushHandler(new PrettyPageHandler());
                $whoops->register();
            }
        }

        // Configurações de log e exibição
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');

        // Validação do diretório de logs
        $logDir = PATH_LOG;
        Directories::validAndCreate($logDir);

        // Define o arquivo de log com base no ambiente
        $env = self::environment() ?? 'unknown';
        $logFile = sprintf('%s%s%s-%s.log', $logDir, DIRECTORY_SEPARATOR, date('Ymd'), $env);
        ini_set('error_log', $logFile);

        // Define o nível de erro (pode ser sobrescrito por .env)
        $logLevel = match (self::environment()) {
            self::ENV_DEV => $_ENV['PHP_ERROR_LEVEL_DEV'] ?? null,
            self::ENV_TEST => $_ENV['PHP_ERROR_LEVEL_TEST'] ?? null,
            self::ENV_PROD => $_ENV['PHP_ERROR_LEVEL_PROD'] ?? null,
        };

        if (!is_numeric($logLevel)) {
            $logLevel = E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT;
        }

        error_reporting((int)$logLevel);

    }

    /**
     * Retorna o ambiente atual
     *
     * @return string|null
     */
    private static function environment(): ?string
    {
        return defined('ENVIRONMENT') ? ENVIRONMENT : null;
    }

    /**
     * Verifica se o ambiente atual é desenvolvimento.
     *
     * @return bool Retorna true se a constante ENVIRONMENT estiver definida e for igual ao valor de desenvolvimento.
     */
    public static function isDev(): bool
    {
        return self::environment() === self::ENV_DEV;
    }

    /**
     * Verifica se o ambiente atual é produção.
     *
     * @return bool Retorna true se a constante ENVIRONMENT estiver definida e for igual ao valor de produção.
     */
    public static function isProd(): bool
    {
        return self::environment() === self::ENV_PROD;
    }

    /**
     * Verifica se o ambiente atual é de teste.
     *
     * @return bool Retorna true se a constante ENVIRONMENT estiver definida e for igual ao valor de teste.
     */
    public static function isTest(): bool
    {
        return self::environment() === self::ENV_TEST;
    }

    /**
     * Inicia a aplicação.
     *
     * Esse método serve como ponto de entrada para o ciclo de vida principal da aplicação.
     * Responsável por registrar serviços, iniciar rotas ou qualquer lógica
     * de boot principal do sistema.
     *
     * @return void
     * @throws Throwable
     */
    private function startApp(): void
    {
        App::start()->run();
    }

}
