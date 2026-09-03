# Aula 01 — Recursos, verbos e status HTTP na prática

**Código executável:** [01-recursos-verbos-status.php](01-recursos-verbos-status.php) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-04-api/01-recursos-verbos-status)

## A ideia

Recurso é **substantivo** (`clientes`), nunca ação (`listarClientes`). A ação vem do
verbo HTTP. Isso não é purismo: quem consome a API já conhece a convenção, então
`DELETE /clientes/3` não precisa de documentação para ser entendido.

## Por que o status importa mais do que parece

O status responde "deu certo?" **sem abrir o corpo**. Uma API que devolve `200` com
`{"erro": true}` obriga todo cliente a fazer parse do JSON antes de saber se deve
tratar como falha — e nenhum retry automático, cache ou monitor funciona direito.

| Status | Significado |
|---|---|
| 200 | sucesso com corpo |
| 201 | recurso criado |
| 204 | sucesso sem corpo (remoção) |
| 400 / 422 | erro **do cliente** (malformado / validação) |
| 401 / 403 | não autenticado / sem permissão |
| 404 | não existe |
| 409 | conflito de estado (e-mail duplicado) |
| 500 | erro **seu** — nunca do usuário |

O erro mais caro: devolver `500` para entrada inválida. `500` dispara alerta de
plantão; validação de formulário não deveria acordar ninguém.

## GET não altera estado

Um link `<a href="/clientes/1/remover">` parece prático e é perigoso: crawler,
preload do navegador e antivírus corporativo "clicam" nele sozinhos. O `.php` desta
aula prova que a rota nem existe — e que a contagem de clientes não muda.

## Aqui a limitação do `<form>` não existe

O painel web usa `POST /clientes/{id}/remover` porque HTML não faz `DELETE`. A API não
tem essa restrição: quem chama é `fetch`, app ou outro backend. Por isso `PUT` e
`DELETE` de verdade aparecem só aqui.

## O que a aula já antecipa como problema

`Response::json($cliente)` serializaria o `DateTimeImmutable` como
`{"date":...,"timezone_type":3,...}` e vazaria qualquer campo interno futuro. A solução
é o Resource — aula 2.
