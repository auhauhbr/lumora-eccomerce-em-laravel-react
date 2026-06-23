<?php

namespace App\Pagamentos;

use MercadoPago\Net\HttpRequest;

class RequisicaoCurlComCertificado implements HttpRequest
{
    private \CurlHandle $manipulador;

    public function __construct(
        private readonly string $caminhoCertificado,
    ) {
        $this->manipulador = curl_init();
    }

    public function setOptionArray(array $value): void
    {
        $value[CURLOPT_CAINFO] = $this->caminhoCertificado;
        $value[CURLOPT_SSL_VERIFYHOST] = 2;
        $value[CURLOPT_SSL_VERIFYPEER] = true;

        curl_setopt_array($this->manipulador, $value);
    }

    public function execute(): bool|string
    {
        return curl_exec($this->manipulador);
    }

    public function getInfo(mixed $name): mixed
    {
        return curl_getinfo($this->manipulador, $name);
    }

    public function close(): void
    {
        curl_close($this->manipulador);
    }

    public function error(): string
    {
        return curl_error($this->manipulador);
    }
}
