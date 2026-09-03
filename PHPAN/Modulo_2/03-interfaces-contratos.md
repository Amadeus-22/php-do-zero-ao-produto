# Aula 03 — Interfaces e contratos: o "porquê" antes do "como"

**Curso:** PHPAN (intermediário) · **Módulo 2** — OOP de verdade (sem framework)
**Aula:** https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-02-oop/03-interfaces-contratos

Status: [x] assistida · [x] anotada · [x] praticada
**Código:** [RepositorioDeClientes.php](../crm-produto/src/Domain/Cliente/RepositorioDeClientes.php) (contrato) ·
[RepositorioDeClientesEmMemoria.php](../crm-produto/src/Infrastructure/Cliente/RepositorioDeClientesEmMemoria.php) ·
[RemetenteDeEmailEmLog.php](../crm-produto/src/Infrastructure/Notificacao/RemetenteDeEmailEmLog.php) ·
[GeradorDeRelatorioCsv.php](../crm-produto/src/Infrastructure/Relatorio/GeradorDeRelatorioCsv.php)

## Objetivo

Interfaces como **contratos** que separam "o que uma operação faz" de "como é
implementada" — desacoplando o domínio de banco, e-mail e geração de relatório.

## Conceito

Uma interface declara **quais métodos existem e com que assinatura**, sem dizer como
funcionam. Quem implementa promete cumprir. Quem usa não precisa saber qual
implementação está por trás.

```php
<?php

declare(strict_types=1);

namespace App\Domain\Cliente;

interface RepositorioDeClientes
{
    public function salvar(Cliente $cliente): Cliente;

    public function buscarPorId(int $id): ?Cliente;

    /** @return list<Cliente> */
    public function todosAtivos(): array;
}
```

Não menciona PDO, MySQL, arquivo ou memória. Só diz: "quem for repositório de
clientes sabe salvar, buscar por ID e listar os ativos".

### O ganho concreto (não é "boa prática" abstrata)

Poder **trocar a implementação sem tocar em quem usa**:

- Em **teste**, implementação em memória — rápida, sem banco real.
- Em **produção**, PDO com MySQL. Migrar de banco reescreve só a infraestrutura.
- Para **e-mail**, programa-se contra `RemetenteDeEmail`: em dev grava em log, em
  produção chama SMTP/API de terceiro.

A implementação em memória do projeto está em
[RepositorioDeClientesEmMemoria.php](../crm-produto/src/Infrastructure/Cliente/RepositorioDeClientesEmMemoria.php)
— é exatamente ela que os testes usam, rodando em milissegundos sem estado externo.

> **Divergência anotada:** a aula põe a implementação em memória em
> `App\Domain\Cliente`. No projeto ela ficou em `App\Infrastructure\Cliente`, para
> ficar coerente com a estrutura de camadas da aula 4 e com a regra "o domínio não
> conhece implementação". O contrato (`RepositorioDeClientes`) continua no domínio.

### Interfaces vs classes abstratas

| | Interface | Classe abstrata |
|---|---|---|
| O que define | Só a assinatura (contrato) | Contrato + implementação parcial compartilhada |
| Herança múltipla | Uma classe implementa várias | PHP só estende **uma** classe |
| Quando usar | Contratos entre camadas (repositório, e-mail, gateway) | Comportamento compartilhado numa família "é um" real |

No PHPAN o uso dominante é **interface** — a maioria dos contratos entre camadas não
precisa de implementação parcial, só de contrato claro.

### Injeção de dependência

A classe **recebe** a dependência (pelo construtor) em vez de criá-la internamente:

```php
final readonly class ListarClientesAtivos
{
    // Repare: o tipo do parâmetro é a INTERFACE, não a implementação concreta.
    public function __construct(
        private RepositorioDeClientes $repositorio,
    ) {
    }

    /** @return list<Cliente> */
    public function executar(): array
    {
        return $this->repositorio->todosAtivos();
    }
}
```

```php
// Em produção:
$caso = new ListarClientesAtivos(new RepositorioDeClientesPdo($pdo));

// Em teste:
$caso = new ListarClientesAtivos(new RepositorioDeClientesEmMemoria());
```

É o "D" do SOLID (inversão de dependência) funcionando sem container sofisticado —
container simples só no Módulo 3.

## Na prática

### Gerador de relatório — resolvendo o exercício 4 da aula anterior

```php
<?php

declare(strict_types=1);

namespace App\Domain\Relatorio;

interface GeradorDeRelatorio
{
    /** @param list<array<string, scalar>> $linhas */
    public function gerar(string $titulo, array $linhas): string;

    public function extensaoArquivo(): string;
}
```

A implementação CSV está em
[GeradorDeRelatorioCsv.php](../crm-produto/src/Infrastructure/Relatorio/GeradorDeRelatorioCsv.php),
com teste. Qualquer código que recebe a interface funciona igual para CSV, PDF ou
formato futuro — **sem `if ($formato === 'csv')` espalhado pelo sistema**.

> Detalhe de implementação que apareceu ao rodar: no PHP 8.4, `fputcsv()` avisa se o
> parâmetro `$escape` não for passado explicitamente. O projeto passa
> `fputcsv($buffer, $linha, ',', '"', '\\')` para não depender do default.

### Exercício — `RemetenteDeEmail` com duas implementações

```php
<?php

declare(strict_types=1);

namespace App\Domain\Notificacao;

interface RemetenteDeEmail
{
    public function enviar(string $destinatario, string $assunto, string $corpo): void;
}
```

A de desenvolvimento (grava em log) está implementada em
[RemetenteDeEmailEmLog.php](../crm-produto/src/Infrastructure/Notificacao/RemetenteDeEmailEmLog.php).
A "de verdade" fica como esqueleto — o envio real é Módulo 6.

## Pontos de atenção

- **Interface para tudo, mesmo sem segunda implementação prevista** —
  over-engineering. Crie quando existe (ou logo existirá) mais de uma implementação,
  ou quando o contrato cruza fronteira de camada (domínio → infraestrutura).
- **Vazar detalhe de implementação no nome ou assinatura** — ex.:
  `buscarPorIdComPdo(PDO $pdo, int $id)` na interface. Ela não deveria saber que PDO
  existe.
- **Depender da classe concreta em vez da interface** na camada de aplicação — se
  `ListarClientesAtivos` tipasse `RepositorioDeClientesPdo`, perderia a troca e o
  teste unitário ficaria acoplado a banco real.
- **Interface "gorda"** com métodos que nem toda implementação precisa — prefira
  pequenas e focadas (o "I" do SOLID).

## Entrega da aula

- [x] Interfaces de repositório definidas: `RepositorioDeClientes`,
      `RepositorioDeContatos`, `RepositorioDeAtividades`
- [x] Versão **em memória** de cada uma implementada
- [x] Testes PHPUnit usando a implementação em memória para testar casos de uso, sem
      banco: `CadastrarClienteTest`, `CadastrarContatoTest`, `RegistrarAtividadeTest`
- [x] `composer quality`

## Aplicar no CRM de produto

Toda dependência externa do CRM entra por interface no domínio + implementação na
infraestrutura. A implementação PDO de cada repositório chega no Módulo 3/7; até lá,
as em memória seguram os testes.
