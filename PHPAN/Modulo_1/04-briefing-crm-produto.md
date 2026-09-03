# Aula 04 — Briefing do CRM de produto

**Curso:** PHPAN (intermediário) · **Módulo 1** — Mapa do intermediário e revisão ativa
**Aula:** https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-01-mapa/04-briefing-crm-produto

Status: [x] assistida · [x] anotada · [ ] praticada

## Objetivo

Ler o escopo completo do projeto do curso — o **CRM de produto** — e traduzi-lo em
algo acionável: o que existe hoje (o Mini CRM do PHPIAN), o que falta, e em que
módulo cada peça é resolvida.

## Conceito

Todo o curso gira em torno de **um projeto só**, que evolui módulo a módulo. Em vez
de 8 mini-exercícios desconectados, um produto real construído incrementalmente —
como acontece no mercado: ninguém entrega um sistema inteiro de uma vez.

O briefing oficial vive em `projeto/BRIEFING.md`, na raiz do repositório do curso.
Nome sugerido do projeto: `crm-produto`. A origem é o Mini CRM do PHPIAN (clientes,
CRUD, login por sessão, PDO); o PHPAN pega o mesmo domínio e o transforma em produto.

### O mapa briefing → currículo

| Área do briefing | Resumo | Onde no currículo |
|---|---|---|
| Domínio | `Cliente`, `Contato`, `Atividade` + papéis de usuário | Módulo 2 (OOP) |
| Web (MVC) | Painel com listar/criar/editar, layouts, validação, CSRF | Módulo 3 (MVC) |
| API | `/api/v1` JSON, no mínimo CRUD de clientes | Módulo 4 (API) |
| Auth | Sessão no painel + token na API, papéis admin/vendedor/leitura | Módulo 5 (Auth) |
| Produto | Upload, fila de e-mail, logs, soft delete, busca, export CSV | Módulo 6 (Recursos) |
| Produção | `.env`, migrações, staging × produção, health check | Módulo 7 (Produção) |
| Monetização | Plano/limite + checkout/webhook (pode ser sandbox) | Módulo 8 (Final) |

### Fora de escopo (explícito no briefing)

Laravel · multi-tenant completo · billing de produção maduro · cache distribuído ·
DDD pesado. Tudo isso é PHPPRO.

> Se bater vontade de "fazer certo com framework" durante o curso, **resista** — é
> desvio de objetivo pedagógico, não upgrade.

### Por que papéis de usuário já aparecem no domínio

O domínio prevê `admin`, `vendedor` e `leitura` desde a área "Domínio", mesmo com
auth séria só chegando no Módulo 5. É intencional: **modelar o conceito de papel**
(um `enum Papel`) é diferente de **implementar a autorização** (checar permissão em
cada rota). Desenha-se o conceito agora e liga-se a fiscalização depois — evita
retrabalho estrutural lá na frente.

## Na prática

### A rubrica de entrega (critério de pronto do curso inteiro)

```markdown
- [ ] MVC com rotas e middleware
- [ ] API v1 documentada (mesmo que em Markdown curto)
- [ ] Auth web + API + pelo menos 2 papéis
- [ ] Upload + fila de e-mail + logs
- [ ] `.env` + migração + deploy em staging ou produção
- [ ] README do produto (como instalar e usar)
```

Essa lista **não muda** ao longo do curso — só vai sendo preenchida. Vale colar em
lugar visível.

### Esboçando o domínio inicial

Sem implementar regra nenhuma ainda, só para fixar o vocabulário que reaparece o
curso inteiro:

```php
<?php

declare(strict_types=1);

namespace App\Domain\Usuario;

enum Papel: string
{
    case ADMIN = 'admin';
    case VENDEDOR = 'vendedor';
    case LEITURA = 'leitura';

    public function podeEditar(): bool
    {
        return match ($this) {
            self::ADMIN, self::VENDEDOR => true,
            self::LEITURA => false,
        };
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\Domain\Atividade;

enum TipoAtividade: string
{
    case LIGACAO = 'ligacao';
    case EMAIL = 'email';
    case REUNIAO = 'reuniao';
    case NOTA = 'nota';
}

final class Atividade
{
    public function __construct(
        private ?int $id,
        private int $clienteId,
        private TipoAtividade $tipo,
        private string $descricao,
        private \DateTimeImmutable $ocorridaEm,
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function clienteId(): int
    {
        return $this->clienteId;
    }

    public function tipo(): TipoAtividade
    {
        return $this->tipo;
    }

    public function descricao(): string
    {
        return $this->descricao;
    }

    public function ocorridaEm(): \DateTimeImmutable
    {
        return $this->ocorridaEm;
    }
}
```

Formalizadas de verdade (com testes, validação e persistência) no Módulo 2 e 3. Por
ora o objetivo é só "ver o formato".

### Traduzindo o briefing em backlog pessoal

Cruzar as 3 gambiarras anotadas na Aula 1 com a tabela do briefing. Exemplo de
raciocínio:

> "Meu Mini CRM tinha SQL direto na página e sem validação de e-mail." → resolvido
> pela camada de domínio (Módulo 2) mais o padrão repositório (Módulo 2/3). Vira item
> de backlog: *criar `RepositorioDeClientes` com validação no construtor de `Cliente`*.

| Gambiarra (Aula 1) | Área do briefing | Módulo | Item de backlog |
|---|---|---|---|
| | | | |
| | | | |
| | | | |

## Pontos de atenção

- **Achar que precisa implementar tudo hoje** — o briefing é o **norte**, não a
  tarefa da semana. Cada módulo destrava uma fatia.
- **Ignorar a coluna "fora de escopo"** e meter Laravel ou multi-tenant "porque seria
  mais profissional" — quebra o propósito pedagógico e atrasa.
- **Não copiar o starter antes de codar** — o briefing pede explicitamente: copie
  `php-profissional-base/` para uma pasta com o nome do projeto (`crm-produto/`).
  Codar dentro do starter mistura "template" com "projeto real".
- **Achar que papéis precisam de sistema de permissão completo agora** — no domínio,
  por enquanto, é só um `enum` com um método de conveniência. Autorização de verdade
  é Módulo 5.

## Entrega da aula

- [ ] Ler `projeto/BRIEFING.md` até o fim
- [ ] Copiar `php-profissional-base/` para `crm-produto/`, **fora** da pasta `curso/`
- [ ] Escrever no `README.md` do projeto duas frases: quem é o usuário do CRM e qual
      dor ele resolve (pedido explícito da seção "Como começar" do briefing)
- [ ] Confirmar que a cópia manteve `src/`, `tests/` e `composer.json` intactos —
      `composer install` roda nela na próxima aula

**Quem é o usuário do meu CRM:**

**Qual dor ele resolve:**

## Aplicar no CRM de produto

Esta aula **é** o contrato do projeto. As duas frases do README acima são o que
define o escopo de tudo que vem depois — se uma feature não serve esse usuário nessa
dor, ela não entra.
