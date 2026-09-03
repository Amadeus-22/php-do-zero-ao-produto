<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Base de todos os erros esperados do negócio.
 * Permite à apresentação capturar "qualquer erro de domínio" de uma vez.
 * Módulo 2, aula 5.
 */
abstract class ErroDeDominio extends \DomainException
{
}
