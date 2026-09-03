# PHP: do zero ao produto

Estudos de PHP acompanhando os cursos **PHPIAN** (fundamentos) e **PHPAN**
(intermediário) da plataforma [cursos.asllanmaciel.com.br](https://cursos.asllanmaciel.com.br),
de Asllan Maciel.

O fio condutor:

> No PHPIAN você aprende a **fazer funcionar**.
> No PHPAN você aprende a **fazer aguentar** — mudança, usuário, tempo e outra
> pessoa mexendo no código.

## O que tem aqui

| Pasta | O que é |
|---|---|
| [PHPIAN/](PHPIAN/) | fundamentos, com o `mini-crm` preservado como registro do ponto de partida |
| [PHPAN/](PHPAN/) | 47 aulas (`.php` que roda + `.md` que explica) e o **`crm-produto`**, o projeto |
| [SINTESE-PHPIAN-PHPAN.md](SINTESE-PHPIAN-PHPAN.md) | mapa mental e a interseção entre os dois cursos |

## O projeto

[`PHPAN/crm-produto`](PHPAN/crm-produto) é o `mini-crm` do PHPIAN reescrito ao longo
de 8 módulos: domínio tipado, MVC próprio sem framework, API v1 versionada,
autenticação por sessão e token, papéis, filas com worker, migrações, backup e
webhook de pagamento.

```bash
cd PHPAN/crm-produto
composer install
composer quality   # PHP-CS-Fixer + PHPStan level 5 + 137 testes + composer audit
```

## Sobre o conteúdo

As anotações em `.md` são **minhas**, escritas com minhas palavras para registrar o
raciocínio por trás de cada decisão. Os exercícios e exemplos de código seguem o
material dos cursos, cujo crédito é do autor — se você quer aprender isso a sério,
faça os cursos. Este repositório é o meu caderno, não um substituto deles.

---

| Curso  | Nível         | Situação          | Acesso até |
|--------|---------------|-------------------|------------|
| PHPIAN | Iniciante     | 40/40 — concluído | 03/08/2027 |
| PHPAN  | Intermediário | 47/47 — concluído | 09/08/2027 |
| PHPPRO | Avançado      | não matriculado   | —          |

## Desafios

- `mini-git-php-function` — Validar e-mail em PHP e entregar no GitHub
  (curso *Git e GitHub: do commit ao PR*, 3 critérios, 125 XP)
- `openclaw-skill-contract` — Escrever o contrato de uma skill OpenClaw
  (curso *OpenClaw Mastery*, 4 critérios, 125 XP)

## Síntese

[SINTESE-PHPIAN-PHPAN.md](SINTESE-PHPIAN-PHPAN.md) — mapa mental dos dois cursos, a
escada de conceitos (API, endpoint, rota, controller, service, repository) e a
interseção arquivo a arquivo entre PHPIAN e PHPAN. 9 diagramas Mermaid.

## Onde paramos

PHPAN concluído em 01/09/2026 — retomada, estado e pendências em
[PHPAN/ONDE-PARAMOS.md](PHPAN/ONDE-PARAMOS.md).

Em 01/09/2026 os dois cursos ganharam um `aulas.json`: o metadado das aulas saiu de
dentro do PHP e virou índice. Cada curso segue o **seu** formato — PHPAN usa
`modulo-slug/NN-slug` e tem um `.php` + um `.md` por aula; PHPIAN usa `M-N`, tem duração
em minutos e o conteúdo da aula, e seus `.php` são exercícios por módulo.

## Convenção

Cada curso usa `Modulo_N/` com um arquivo por aula.

- **PHPIAN** — registro do que foi entregue lá; o código não muda. Ganhou índice das
  40 aulas em [PHPIAN/aulas.json](PHPIAN/aulas.json), extraído do HTML da plataforma
  por `PHPIAN/bin/importar-aula.php`. A aula é identificada por `#M-N` e os `.php` são
  exercícios do módulo — lógica do PHPIAN, diferente da do PHPAN.
- **PHPAN** — nota da aula em `.md` (mesmo formato da plataforma) + o projeto real em
  `PHPAN/crm-produto/`, onde o código é escrito, testado e rodado
  (`composer quality`). É ali que o Mini CRM do PHPIAN evolui para produto.
