<?php

namespace App\Services\Translations;

use Core\Bootstrap;
use Stichoza\GoogleTranslate\Exceptions\LargeTextException;
use Stichoza\GoogleTranslate\Exceptions\RateLimitException;
use Stichoza\GoogleTranslate\Exceptions\TranslationRequestException;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Component\HttpFoundation\Request;
use Stichoza\GoogleTranslate\GoogleTranslate;

class I18n
{
    private Translator $translator;
    private string $defaultLocale;
    private string $translationsDir;
    private array $supportedLocales = ['en', 'pt-BR', 'es']; // Defina seus idiomas suportados

    public function __construct(protected Request $request)
    {
        $locale = $request->getLocale() ?? DEFAULT_LOCALE;
        $this->defaultLocale = $this->parserLocale($locale);
        $this->translationsDir = PATH_TRANSLATIONS;
        $this->translator = new Translator($this->defaultLocale);
        $this->translator->addLoader('array', new ArrayLoader());
        $this->loadTranslations();
    }

    private function parserLocale(string $locale, string $search = "-", string $replace = "_"): string
    {
        return str_replace($search, $replace, $locale);
    }

    private function loadTranslations(): void
    {
        $files = glob($this->translationsDir . 'messages.*.php');
        foreach ($files as $file) {
            $locale = $this->extractLocaleFromFilename($file);
            if ($locale) {
                $messages = include $file;
                if (is_array($messages)) {
                    $this->translator->addResource('array', $messages, $locale);
                }
            }
        }
    }

    private function extractLocaleFromFilename(string $file): ?string
    {
        // Extrai o locale do nome do arquivo, ex: messages.en.php => en
        $basename = basename($file);
        if (preg_match('/messages\.([a-z]{2}(?:_[A-Z]{2})?)\.php$/', $basename, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public function setLocale(string $locale): void
    {
        $this->translator->setLocale($locale);
    }

    public function getLocale(): string
    {
        return $this->translator->getLocale();
    }

    /**
     * Traduz a chave e adiciona automaticamente se não existir.
     *
     * @param string $id
     * @param array $parameters
     * @param string|null $domain
     * @param string|null $locale
     * @return string
     */
    public function translate(string $id, array $parameters = [], string $domain = null, string $locale = null): string
    {
        $locale = $locale ?? $this->getLocale();

        // Se a chave não existir, adiciona traduções automáticas
        if (!$this->hasTranslation($id, $locale)) {
            $this->addAutoTranslation($id);
            // Recarrega as traduções para refletir a nova chave
            $this->loadTranslations();
        }

        return $this->translator->trans($id, $parameters, $domain, $locale);
    }

    private function hasTranslation(string $id, string $locale): bool
    {
        $catalogue = $this->translator->getCatalogue($locale);
        return $catalogue->has($id);
    }

    /**
     * Adiciona traduções automáticas básicas para a chave em todos os idiomas suportados.
     *
     * @param string $id
     * @return void
     */
    private function addAutoTranslation(string $id): void
    {
        foreach ($this->supportedLocales as $locale) {
            $this->saveTranslation(
                $locale,
                $id,
                $this->defaultLocale == $this->parserLocale($locale)
                    ? $id : $this->translateText($locale, $id)
            );
        }
    }

    /**
     * Salva a tradução no arquivo PHP correspondente.
     *
     * @param string $locale O locale do arquivo de tradução (ex: 'en', 'pt_BR').
     * @param string $id A chave da tradução.
     * @param string $translation O texto traduzido a ser salvo.
     * @return void
     */
    private function saveTranslation(string $locale, string $id, string $translation): void
    {
        $locale = $this->parserLocale($locale, '-', '_');
        $file = $this->translationsDir . DIRECTORY_SEPARATOR . "messages.$locale.php";

        $translations = [];
        if (file_exists($file)) {
            $translations = include $file;
            if (!is_array($translations)) {
                $translations = [];
            }
        }

        // Atualiza ou adiciona a tradução
        $translations[$id] = $translation;

        // Ordena as chaves para manter o arquivo organizado
        ksort($translations);

        // Gera o conteúdo PHP para salvar
        $content = "<?php\n\nreturn " . var_export($translations, true) . ";\n";

        file_put_contents($file, $content);
    }

    private function translateText(string $locale, string $id): ?string
    {
        try {
            $tr = new GoogleTranslate($locale, $this->defaultLocale, [
                'timeout' => 10,
                'verify' => Bootstrap::isProd()
            ]);
            return $tr->translate($id);
        } catch (\Throwable $e) {
            return $id;
        }
    }

    public function detectLocale(Request $request): string
    {
        $path = $request->getPathInfo();

        if (preg_match('/^\/([a-z]{2}(_[A-Z]{2})?)\//', $path, $matches)) {
            $locale = $matches[1];
            if ($this->isValidLocale($locale)) {
                return $locale;
            }
        }

        $locales = $request->getLanguages();
        foreach ($locales as $locale) {
            if ($this->isValidLocale($locale)) {
                return $locale;
            }
        }

        return $this->defaultLocale;
    }

    private function isValidLocale(string $locale): bool
    {
        $file = $this->translationsDir . 'messages.' . $locale . '.php';
        return file_exists($file);
    }
}