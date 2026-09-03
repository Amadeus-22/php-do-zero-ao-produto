<?php

declare(strict_types=1);

namespace App\Support;

use App\Application\Cliente\ClienteService;
use App\Application\Lembrete\LembreteService;
use App\Auditoria\AuditLogger;
use App\Auditoria\Auditoria;
use App\Auth\LoginPainel;
use App\Auth\ResetSenhaService;
use App\Auth\TokenService;
use App\Billing\AssinaturaService;
use App\Billing\PlanLimiter;
use App\Billing\WebhookPagamento;
use App\Config\Config;
use App\Domain\Anexo\RepositorioDeAnexos;
use App\Domain\Cliente\RepositorioDeClientes;
use App\Domain\Notificacao\RemetenteDeEmail;
use App\Domain\Relatorio\GeradorDeRelatorio;
use App\Domain\Usuario\RepositorioDeUsuarios;
use App\Exportacao\ExportadorDeClientesCsv;
use App\Filas\Handlers\EnviarEmailBoasVindas;
use App\Filas\Handlers\GerarRelatorioClientes;
use App\Filas\Handlers\NotificarLembrete;
use App\Filas\JobDispatcher;
use App\Filas\Worker;
use App\Infrastructure\Anexo\RepositorioDeAnexosPdo;
use App\Infrastructure\Cliente\RepositorioDeClientesPdo;
use App\Infrastructure\Lembrete\RepositorioDeLembretesPdo;
use App\Infrastructure\Notificacao\RemetenteDeEmailEmLog;
use App\Infrastructure\Relatorio\GeradorDeRelatorioCsv;
use App\Infrastructure\Relatorio\GeradorDeRelatorioPdf;
use App\Infrastructure\Usuario\RepositorioDeUsuariosPdo;
use App\Log\Logger;
use App\Log\LoggerFabrica;
use App\Uploads\UploadService;

/**
 * Montagem manual das dependências. NÃO é container de DI — é só o lugar único
 * onde se decide qual implementação concreta o app usa.
 *
 * A implementação em memória NÃO aparece aqui de propósito: ela é um duplo de
 * teste, e os testes a injetam com Container::usar(). Persistência da aplicação
 * é banco de dados; dado de negócio não mora em JSON no disco.
 */
final class Container
{
    private static ?RepositorioDeClientes $repositorio = null;

    private static ?RepositorioDeUsuarios $usuarios = null;

    private static ?RemetenteDeEmail $remetente = null;

    private static ?Auditoria $auditoria = null;

    public static function repositorioDeClientes(): RepositorioDeClientes
    {
        return self::$repositorio ??= new RepositorioDeClientesPdo(Database::conexao());
    }

    public static function clienteService(): ClienteService
    {
        return new ClienteService(self::repositorioDeClientes(), self::auditoria(), self::planLimiter());
    }

    public static function repositorioDeAnexos(): RepositorioDeAnexos
    {
        return new RepositorioDeAnexosPdo(Database::conexao());
    }

    public static function repositorioDeUsuarios(): RepositorioDeUsuarios
    {
        return self::$usuarios ??= new RepositorioDeUsuariosPdo(Database::conexao());
    }

    public static function tokenService(): TokenService
    {
        return new TokenService(Database::conexao());
    }

    public static function rateLimiter(): RateLimiter
    {
        return new RateLimiter(Database::conexao());
    }

    public static function loginPainel(): LoginPainel
    {
        return new LoginPainel(self::repositorioDeUsuarios(), self::rateLimiter());
    }

    public static function auditoria(): Auditoria
    {
        return self::$auditoria ??= new AuditLogger(Database::conexao());
    }

    public static function usarAuditoria(Auditoria $auditoria): void
    {
        self::$auditoria = $auditoria;
    }

    public static function remetenteDeEmail(): RemetenteDeEmail
    {
        return self::$remetente ??= new RemetenteDeEmailEmLog(dirname(__DIR__, 2) . '/var/emails.log');
    }

    public static function resetSenhaService(): ResetSenhaService
    {
        return new ResetSenhaService(
            Database::conexao(),
            self::repositorioDeUsuarios(),
            self::remetenteDeEmail(),
            self::tokenService(),
            Config::string('APP_URL', 'http://localhost:8000'),
        );
    }

    public static function logger(): Logger
    {
        return LoggerFabrica::criar();
    }

    public static function dispatcher(): JobDispatcher
    {
        return new JobDispatcher(Database::conexao());
    }

    public static function lembreteService(): LembreteService
    {
        return new LembreteService(new RepositorioDeLembretesPdo(Database::conexao()), self::dispatcher());
    }

    public static function uploadService(): UploadService
    {
        // FORA de public/: mesmo que a validação de tipo fosse burlada, um .php
        // ali não seria executável por URL.
        return new UploadService(dirname(__DIR__, 2) . '/storage/anexos');
    }

    public static function exportadorCsv(): ExportadorDeClientesCsv
    {
        return new ExportadorDeClientesCsv(Database::conexao());
    }

    public static function geradorDeRelatorio(): GeradorDeRelatorio
    {
        return new GeradorDeRelatorioCsv();
    }

    public static function geradorDePdf(): GeradorDeRelatorio
    {
        return new GeradorDeRelatorioPdf();
    }

    public static function worker(): Worker
    {
        $raiz = dirname(__DIR__, 2);

        return new Worker(
            Database::conexao(),
            [
                'enviar_email_boas_vindas' => new EnviarEmailBoasVindas(self::repositorioDeClientes(), self::remetenteDeEmail()),
                'notificar_lembrete' => new NotificarLembrete(Database::conexao(), self::remetenteDeEmail()),
                'gerar_relatorio_clientes' => new GerarRelatorioClientes(
                    self::repositorioDeClientes(),
                    self::geradorDeRelatorio(),
                    $raiz . '/var/relatorios',
                ),
            ],
            self::logger(),
        );
    }

    public static function planLimiter(): PlanLimiter
    {
        return new PlanLimiter(Database::conexao());
    }

    public static function assinaturaService(): AssinaturaService
    {
        return new AssinaturaService(Database::conexao());
    }

    public static function webhookPagamento(): WebhookPagamento
    {
        return new WebhookPagamento(
            Database::conexao(),
            self::assinaturaService(),
            Config::string('WEBHOOK_SECRET', 'segredo-local-de-estudo'),
            self::logger(),
        );
    }

    public static function usar(RepositorioDeClientes $repositorio): void
    {
        self::$repositorio = $repositorio;
    }

    public static function usarUsuarios(RepositorioDeUsuarios $usuarios): void
    {
        self::$usuarios = $usuarios;
    }

    public static function usarRemetente(RemetenteDeEmail $remetente): void
    {
        self::$remetente = $remetente;
    }
}
