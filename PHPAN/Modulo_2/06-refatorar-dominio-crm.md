# Aula 06 — Refatorar o Mini CRM: extrair Modelos e Serviços

**Curso:** PHPAN (intermediário) · **Módulo 2** — OOP de verdade (sem framework)
**Aula:** https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-02-oop/06-refatorar-dominio-crm

Status: [x] assistida · [x] anotada · [x] praticada
**Código:** o projeto inteiro em [crm-produto/](../crm-produto/) — `composer quality` passando

> **Nota sobre o Mini CRM do PHPIAN:** ele fica intocado em
> `PHPIAN/Modulo_8(modeagem_final)/mini-crm/`, como registro do que foi entregue lá.
> A evolução acontece aqui, no `crm-produto/` — a refatoração é *para dentro do PHPAN*.

## Objetivo

Consolidar o módulo — classes tipadas, composição, interfaces, PSR-4 e exceções de
domínio — no domínio completo do CRM: `Cliente`, `Contato`, `Atividade` e os casos de
uso que os conectam.

## Conceito

Aula de juntar as peças, sem conceito novo. A meta prática: sair daqui com uma base
que o Módulo 3 (MVC) **apenas consome**, sem redesenhar nada estrutural.

### O que "extrair Model e Service" significa aqui

Evitamos o termo genérico "Model" (associado a ActiveRecord, onde a classe mistura
dado + acesso a banco) e somos explícitos sobre a camada:

| Peça | Papel |
|---|---|
| **Entidade de domínio** (`Cliente`, `Contato`, `Atividade`) | Dado + regras que sempre valem, sem saber de banco |
| **Caso de uso / serviço de aplicação** (`CadastrarCliente`, `RegistrarAtividade`) | Orquestra entidades e repositórios para realizar uma ação completa |
| **Repositório** (interface + implementação) | A ponte para a persistência |

"Refatorar o Mini CRM" = pegar a lógica que estava misturada em páginas PHP soltas e
separar essas três responsabilidades.

### O domínio integrado

Tudo o que a aula monta está implementado no projeto:

| Aula mostra | Onde está no projeto |
|---|---|
| `ErroDeDominio` | [src/Domain/ErroDeDominio.php](../crm-produto/src/Domain/ErroDeDominio.php) |
| `Cliente` + `StatusCliente` + `ClienteInvalido` | [src/Domain/Cliente/](../crm-produto/src/Domain/Cliente/) |
| `Contato` + `CanalPreferido` | [src/Domain/Contato/](../crm-produto/src/Domain/Contato/) |
| `Atividade` + `TipoAtividade` | [src/Domain/Atividade/](../crm-produto/src/Domain/Atividade/) |
| Interfaces de repositório | uma por agregado, junto da entidade |
| `CadastrarCliente`, `CadastrarContato`, `RegistrarAtividade` | [src/Application/](../crm-produto/src/Application/) |

**Duas divergências deliberadas em relação ao texto da aula**, ambas seguindo a regra
de "uma classe por arquivo" da aula 4:

1. A aula declara `ClienteInexistente` dentro de `CadastrarContato.php` "só para
   caber no exemplo" — e ela mesma manda mover. No projeto isso é
   `ClienteNaoEncontrado`, em `src/Domain/Cliente/`, reaproveitada por
   `CadastrarContato`; `RegistrarAtividade` usa `AtividadeInvalida::clienteInexistente()`.
2. `RegistrarAtividade` não instancia `ErroDeDominio` diretamente (ela é `abstract`) —
   usa a fábrica nomeada da exceção concreta.

## Na prática

### Roteiro de refatoração (ordem que minimiza retrabalho)

1. **Identificar as entidades implícitas.** Procurar arrays associativos que
   representam "a mesma coisa" em vários lugares (`['id' => ..., 'nome' => ...]`) —
   cada padrão desses é candidato a classe de domínio.
2. **Extrair a entidade primeiro, sem repositório.** Criar a classe com validação na
   fábrica/construtor. Rodar os testes para confirmar que o comportamento visível não
   mudou.
3. **Extrair a interface de repositório**, com os métodos que o código já usa hoje
   (mesmo que hoje seja um `$pdo->query(...)` solto).
4. **Implementar o repositório** por trás da interface, movendo o SQL disperso para
   dentro dela.
5. **Extrair o caso de uso**, movendo a orquestração ("só cadastra contato se cliente
   existir") de dentro da página PHP para a classe de aplicação.
6. **Rodar `composer quality` a cada passo** — não acumular os 5 passos antes de testar.

### Checklist de qualidade do domínio final — verificado

Rodado no projeto, item por item:

| # | Item | Resultado |
|---|---|---|
| 1 | Nenhuma classe de `src/Domain/` importa `PDO`, `$_POST`, `$_SESSION` ou HTTP | ✅ nenhuma ocorrência |
| 2 | Toda entidade valida invariantes na criação | ✅ `Cliente` (nome, e-mail), `Contato` (nome, e-mail), `Atividade` (descrição) |
| 3 | Não existem setters genéricos (`setX`) | ✅ nenhum — só `renomear()`, `desativar()`, `alterarCanalPreferido()` |
| 4 | Toda interface de repositório tem ao menos uma implementação | ✅ 3 interfaces, 3 implementações em memória |
| 5 | Erros de negócio usam exceções de domínio, não `die()`/`var_dump()` | ✅ nenhum `die`/`var_dump`/`exit` no código |
| 6 | `composer analyse` sem erro | ✅ PHPStan level 5: **No errors** |
| 7 | Ao menos 1 teste por entidade e por caso de uso | ✅ 8 arquivos de teste, **24 testes / 46 asserções** |

Comandos usados na verificação:

```bash
grep -rnE 'PDO|\$_POST|\$_SESSION|\$_GET|header\(' src/Domain/   # item 1
grep -rnE 'function set[A-Z]' src/                                # item 3
grep -rnE '\b(die|var_dump|exit)\s*\(' src/                       # item 5
composer quality                                                  # itens 6 e 7
```

## Pontos de atenção

- **Refatorar tudo de uma vez, sem teste de segurança** — sem teste cobrindo o
  comportamento atual, a refatoração vira aposta. Escreva ao menos um teste de
  caminho feliz antes de mexer em código legado.
- **Entidade anêmica** (só getters/setters, lógica toda em Services externos) —
  antipadrão conhecido (*Anemic Domain Model*). Regra que diz respeito só àquela
  entidade ("e-mail precisa ser válido") pertence à entidade.
- **Caso de uso fazendo trabalho de repositório** (montando SQL dentro de
  `CadastrarCliente`) — a aplicação está vazando para infraestrutura.
- **Achar que está "pronto" sem rodar `composer quality`** — vale mais ainda aqui, com
  mais código em jogo.

## Entrega da aula (fecha o Módulo 2)

- [x] `Cliente`, `Contato` e `Atividade` como entidades tipadas, com invariantes
      protegidas
- [x] Interfaces de repositório para os três, com uma implementação em memória cada
- [x] `CadastrarCliente`, `CadastrarContato` e `RegistrarAtividade` implementados
      (+ `ListarClientesAtivos`)
- [x] Exceções de domínio cobrindo e-mail inválido e cliente inexistente ao criar
      contato/atividade
- [x] Testes PHPUnit cobrindo entidades e casos de uso, com repositórios em memória
- [x] `composer quality` passando limpo no projeto inteiro

## Quiz do Módulo 2 — respostas

| # | Pergunta | Resposta |
|---|---|---|
| 1 | Relação `Cliente` × `Contato` | **Composição**: Cliente tem contatos |
| 2 | Erro em `buscarPorIdComPdo(PDO $pdo, int $id)` numa interface | **Nome e assinatura vazam implementação**; o contrato não deveria conhecer PDO |
| 3 | Quando criar uma interface | Quando **há mais de uma implementação prevista**, ou o contrato cruza fronteira de camada |
| 4 | Por que `die("e-mail inválido")` é ruim | **Encerra o processo** e não dá para distinguir o erro por tipo |
| 5 | O que é entidade "anêmica" | Entidade **só com getters/setters, sem regras próprias** |
| 6 | Por que capitalização de namespace e pasta precisam bater | **Funciona no Windows e quebra no Linux** (case-sensitive) |

## Aplicar no CRM de produto

Domínio pronto para ser exposto por MVC (Módulo 3) e por API (Módulo 4) — sem
redesenho, só consumido por novas camadas de apresentação. O que falta na
infraestrutura: as implementações **PDO** dos três repositórios (chegam junto com as
migrações, Módulo 7) e o remetente de e-mail real (Módulo 6).
