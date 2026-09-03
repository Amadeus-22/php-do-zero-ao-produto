# Aula 06 — Auditoria: quem fez o quê

**Código:** [06-auditoria.php](06-auditoria.php) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-05-auth/06-auditoria)

## A ideia

Auditoria **não** é log de debug. São coisas diferentes com públicos diferentes:

| | Log de aplicação | Auditoria |
|---|---|---|
| Público | devs | admin / negócio / compliance |
| Conteúdo | erro, stack trace, contexto técnico | ação de negócio: quem, o quê, quando |
| Mutabilidade | pode rotacionar e sumir | **append-only** |
| Retenção | dias/semanas | meses/anos |

"Quem excluiu o cliente X" precisa estar disponível daqui a um ano, para responder a
pergunta de um cliente, investigar um problema ou cumprir exigência legal.

## Append-only é o que dá valor de prova

Se a tabela pode ser editada ou apagada por qualquer papel — inclusive admin, na
prática —, ela deixa de servir como prova: quem fez algo errado apaga o próprio rastro.

Por isso o schema não tem coluna de atualização, e o `AuditLogger` só faz `INSERT`. O
`.php` (e um teste automatizado) faz `grep` no código-fonte procurando `UPDATE` ou
`DELETE` em `auditoria` — se aparecer, falha.

## Chamada explícita, sem mágica

A aula recomenda chamar o logger **no ponto onde a ação acontece**, sem eventos
escondidos. O motivo é prático nesta fase: explícito é mais fácil de garantir que
ninguém esqueceu. Um sistema de eventos automáticos parece elegante e esconde a
pergunta "essa ação está sendo auditada?".

No projeto isso virou `ClienteService::criar($dados, $usuarioLogadoId)` — o parâmetro
opcional deixa claro quem fez, e `AuditoriaNula` (null object) cobre o teste de unidade
sem espalhar `if ($auditoria !== null)`.

## A exclusão é a mais esquecida

E é a mais sensível. Passa despercebida porque parece "só um `UPDATE deletado_em`" — o
soft delete do Módulo 6 esconde o registro, e sem rastro ninguém sabe quem escondeu.

## Campo sensível nunca entra

`senha_hash`, `token`, `cartao`, `cvv` são removidos antes de gravar. Auditoria fica
anos no banco e é lida por mais gente que a tabela de usuários — um hash de senha ali
é um vazamento com prazo longo.

## Duas escolhas que ficaram em aberto

**Transação.** O ideal é gravar auditoria e mudança de dado na mesma transação: senão
dá para ter mudança sem rastro (auditoria falhou) ou rastro sem mudança (ação falhou
depois). No projeto isso vale para o caminho do `ClienteService`.

**Falha da auditoria derrubando a ação.** Se o `INSERT` de auditoria falhar por banco
fora do ar, a edição do cliente deveria travar? A aula sugere que não nesta fase — e
volta ao tema com filas no Módulo 6. Ainda não decidi isso no projeto; hoje uma falha
na auditoria propaga.

## Consulta restrita

O histórico é visível só para `admin`, via `Gate` (`auditoria.ver`). Rastro de quem fez
o quê é informação sensível sobre pessoas, não só sobre dados.
