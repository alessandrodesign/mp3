<?php

namespace Core\Utils;

use Exception;

class Crypt
{
    private static ?self $instance = null;
    private string $secretKey = SECRET_KEY_GLOBAL;
    private const string CIPHER = 'AES-256-CBC';
    private const int IV_LENGTH = 16; // 128 bits

    /**
     * Construtor privado para evitar instâncias externas.
     *
     * @throws Exception Se a chave for inválida.
     */
    private function __construct()
    {
    }

    /**
     * Retorna a instância única da classe.
     *
     * @return self
     * @throws Exception Se a instância não foi inicializada e a chave não for fornecida.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Criptografa o texto usando AES-256-CBC.
     *
     * @param string $plaintext Texto a ser criptografado.
     * @return string Texto criptografado em hexadecimal.
     * @throws Exception Se ocorrer erro na geração do IV ou na criptografia.
     */
    public function encrypt(string $plaintext): string
    {
        $iv = random_bytes(self::IV_LENGTH);
        $encrypted = openssl_encrypt($plaintext, self::CIPHER, $this->secretKey, OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            throw new Exception('Error encrypting data.');
        }

        return bin2hex($iv . $encrypted);
    }

    /**
     * Descriptografa o texto criptografado em hexadecimal.
     *
     * @param string $hex Texto criptografado em hexadecimal.
     * @return string|null Texto original ou null se falhar.
     */
    public function decrypt(string $hex): ?string
    {
        $data = hex2bin($hex);
        if ($data === false || strlen($data) < self::IV_LENGTH + 1) {
            return null;
        }

        $iv = substr($data, 0, self::IV_LENGTH);
        $ciphertext = substr($data, self::IV_LENGTH);

        $decrypted = openssl_decrypt($ciphertext, self::CIPHER, $this->secretKey, OPENSSL_RAW_DATA, $iv);

        return $decrypted === false ? null : $decrypted;
    }

    /**
     * Impede a clonagem da instância.
     */
    private function __clone()
    {
        throw new Exception('Clone is not allowed.');
    }

    /**
     * Impede a desserialização da instância.
     */
    public function __wakeup()
    {
        throw new Exception('Cannot unserialize a singleton.');
    }
}