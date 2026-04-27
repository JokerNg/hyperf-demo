<?php

declare(strict_types=1);

namespace App\Service;

use Hyperf\Contract\ConfigInterface;
use Oh86\SmCryptor\Impl\LocalCryptor;

class SmCryptorService
{
    private LocalCryptor $cryptor;

    public function __construct(ConfigInterface $config)
    {
        $this->cryptor = new LocalCryptor($config->get('sm_cryptor.local'));
    }

    public function sm4Encrypt(string $text): string
    {
        return $this->cryptor->sm4Encrypt($text);
    }

    public function sm4Decrypt(string $cipherText): string
    {
        return $this->cryptor->sm4Decrypt($cipherText);
    }

    public function sm3(string $text): string
    {
        return $this->cryptor->sm3($text);
    }

    public function hmacSm3(string $text): string
    {
        return $this->cryptor->hmacSm3($text);
    }

    public function sm2GenSign(string $text): string
    {
        return $this->cryptor->sm2GenSign($text);
    }

    public function sm2VerifySign(string $text, string $sign): bool
    {
        return $this->cryptor->sm2VerifySign($text, $sign);
    }

    public function sm2Encrypt(string $text): string
    {
        return $this->cryptor->sm2Encrypt($text);
    }

    public function sm2Decrypt(string $cipherText): string
    {
        return $this->cryptor->sm2Decrypt($cipherText);
    }
}
