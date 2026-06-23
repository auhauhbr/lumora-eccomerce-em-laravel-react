<?php

namespace App\Enumeracoes;

enum PapelUsuario: string
{
    case Cliente = 'customer';
    case Administrador = 'admin';
}
