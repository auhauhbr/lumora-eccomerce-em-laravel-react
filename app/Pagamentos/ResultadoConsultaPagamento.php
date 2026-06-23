<?php

namespace App\Pagamentos;

readonly class ResultadoConsultaPagamento
{
    public function __construct(
        public string $referencia,
        public ?int $pedidoId,
        public string $status,
        public ?string $aprovadoEm = null,
    ) {}
}
