<?php

namespace Testes\Unitarios;

use PHPUnit\Framework\TestCase;

class ExemploTeste extends TestCase
{
    public function test_verdadeiro_e_verdadeiro(): void
    {
        $this->assertTrue(true);
    }
}
