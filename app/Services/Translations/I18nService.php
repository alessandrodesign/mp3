<?php

namespace App\Services\Translations;

use RuntimeException;

/**
 * Serviço Singleton de Tradução.
 */
class I18nService
{
    /**
     * Instância singleton da própria classe.
     */
    private static ?self $instance = null;

    /**
     * Torna o construtor privado para forçar uso de singleton.
     */
    public function __construct(protected I18n $i18n)
    {
        self::$instance = $this;
    }

    /**
     * Retorna a instância singleton.
     */
    public static function getInstance(?I18n $i18n = null): self
    {
        if (!self::$instance) {
            if (!$i18n) {
                throw new RuntimeException('I18n service not initialized');
            }
            self::$instance = new self($i18n);
        }

        return self::$instance;
    }

    /**
     * Define a instância de I18n a ser usada para tradução.
     */
    public function setI18n(I18n $i18n): void
    {
        $this->i18n = $i18n;
    }

    public function getI18n(): ?I18n
    {
        return $this->i18n;
    }

    /**
     * Traduz um texto com base no ID informado.
     *
     * @param string $id Chave da tradução
     * @param array $parameters Parâmetros para substituição
     * @param string|null $domain Domínio opcional
     * @param string|null $locale Localidade opcional
     * @return string Texto traduzido
     */
    public static function translate(
        string  $id,
        array   $parameters = [],
        ?string $domain = null,
        ?string $locale = null
    ): string
    {
        return self::getInstance()->i18n->translate($id, $parameters, $domain, $locale);
    }

    public function trans(
        string  $id,
        array   $parameters = [],
        ?string $domain = null,
        ?string $locale = null
    ): string
    {
        return $this->i18n->translate($id, $parameters, $domain, $locale);
    }

    private function __clone()
    {
    }

    public function __wakeup()
    {
    }

}
