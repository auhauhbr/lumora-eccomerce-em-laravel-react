<?php

namespace App\Pagamentos;

readonly class ResultadoCriacaoPagamento
{
    public function __construct(
        public string $referencia,
        public string $url,
    ) {}
}
