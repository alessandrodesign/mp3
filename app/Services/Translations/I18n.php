<?php

namespace App\Services\Translations;

use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Component\HttpFoundation\Request;

class I18n
{
    private Translator $translator;
    private string $defaultLocale;
    private string $translationsDir;

    public function __construct(
        protected Request $request,
    )
    {
        $locale = $request->getLocale() ?? DEFAULT_LOCALE;
        $this->defaultLocale = str_replace("-", "_", $locale);
        $this->translationsDir = PATH_TRANSLATIONS;
        $this->translator = new Translator($this->defaultLocale);
        $this->translator->addLoader('array', new ArrayLoader());
        $this->loadTranslations();
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

    public function translate(string $id, array $parameters = [], string $domain = null, string $locale = null): string
    {
        return $this->translator->trans($id, $parameters, $domain, $locale);
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