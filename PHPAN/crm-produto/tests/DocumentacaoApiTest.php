<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

/**
 * Impede que docs/api.md minta: toda rota registrada em routes/api.php
 * precisa aparecer documentada. É o que transforma "documentar" em hábito
 * verificável em vez de promessa.
 */
final class DocumentacaoApiTest extends TestCase
{
    public function testTodaRotaDaApiEstaDocumentada(): void
    {
        $rotas = (string) file_get_contents(dirname(__DIR__) . '/routes/api.php');
        $doc = (string) file_get_contents(dirname(__DIR__) . '/docs/api.md');

        preg_match_all("#\\\$router->(\w+)\('(/api/v1[^']*)'#", $rotas, $m, PREG_SET_ORDER);

        self::assertNotEmpty($m, 'nenhuma rota encontrada em routes/api.php');

        foreach ($m as [, $metodo, $caminho]) {
            $secao = strtoupper($metodo) . ' ' . str_replace('/api/v1', '', $caminho);

            self::assertStringContainsString(
                $secao,
                $doc,
                "A rota {$secao} não está documentada em docs/api.md",
            );
        }
    }

    public function testDocumentaOsCodigosDeErroQueAApiRealmenteUsa(): void
    {
        $doc = (string) file_get_contents(dirname(__DIR__) . '/docs/api.md');

        foreach (['validation_failed', 'not_found', 'conflict'] as $code) {
            self::assertStringContainsString($code, $doc);
        }
    }
}
