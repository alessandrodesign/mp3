<?php

use App\Services\Translations\I18nService;

if (!function_exists('t')) {
    /**
     * Função global para tradução.
     *
     * @param string $id ID da tradução (chave)
     * @param array $parameters Parâmetros para substituição
     * @param string|null $domain Domínio opcional
     * @param string|null $locale Localidade opcional
     * @return string Tradução final
     * Atalho para tradução.
     * @example
     *   t('hello');
     *   t('greeting.name', ['name' => 'João']);
     *   t('greeting.name', ['name' => 'João'], 'messages', 'pt_BR');
     */
    function t(string $id, array $parameters = [], string $domain = null, string $locale = null): string
    {
        try {
            global $container;

            $service = $container->get(I18nService::class);

            return $service->trans($id, $parameters, $domain, $locale);
        } catch (Throwable $exception) {
            return $id;
        }
    }
}

if (!function_exists('tf')) {
    function tf(string $id, ...$parameters): string
    {
        return sprintf(t($id), ...$parameters);
    }
}