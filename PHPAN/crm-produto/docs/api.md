# API do CRM — v1

Base URL: `/api/v1`

**Autenticação:** `Authorization: Bearer <access_token>` em toda rota de `/clientes`.
O access token dura 15 minutos; renove com `/auth/refresh` (o refresh dura 30 dias e
**rotaciona**: o antigo é revogado a cada uso).

**Autorização:** por papel — `admin`, `vendedor`, `leitura`.

| Ação | admin | vendedor | leitura |
|---|:--:|:--:|:--:|
| listar / ver | ✅ | ✅ | ✅ |
| criar / editar | ✅ | ✅ | ❌ |
| excluir | ✅ | ❌ | ❌ |

Sem token → `401 unauthorized`. Com token válido mas sem permissão → `403 forbidden`.

**Formato de data:** `DATE_ATOM` (`2026-08-31T23:48:21+00:00`) em toda a API.

**Envelope:** sucesso em `{"data": ...}`; erro em `{"error": {"code","message","details"?}}`.

| code | HTTP | Quando |
|---|---|---|
| `validation_failed` | 422 | campo ausente ou em formato inválido — `details` traz erro por campo |
| `not_found` | 404 | recurso não existe |
| `conflict` | 409 | e-mail já cadastrado |
| `unauthorized` | 401 | token ausente, inválido ou expirado |
| `forbidden` | 403 | autenticado, mas o papel não permite a ação |
| `rate_limited` | 429 | tentativas de login demais — veja o cabeçalho `Retry-After` |
| `plan_limit_reached` | 403 | o **plano** da conta não permite (diferente de `forbidden`, que é papel) |

---

## `POST /auth/login`

Troca e-mail e senha por um par de tokens.

**Corpo:** `{ "email": "admin@exemplo.com", "senha": "..." }`

**Resposta 200:** `{"data": {"access": "<64 hex>", "refresh": "<64 hex>"}}`

**Erros:** `401 unauthorized` (credenciais inválidas — a mesma mensagem para e-mail
inexistente e senha errada, para não permitir enumerar contas) · `429 rate_limited`
(5 tentativas por 15 minutos, por e-mail + IP).

---

## `POST /auth/refresh`

Renova o par de tokens **e revoga o refresh usado** (rotação).

**Corpo:** `{ "refresh": "<64 hex>" }`

**Resposta 200:** `{"data": {"access": "...", "refresh": "..."}}`

**Erros:** `401 unauthorized` — inclusive ao reapresentar um refresh já usado.

---

## `POST /auth/logout`

Revoga **todos** os tokens ativos do usuário no servidor. Apagar o token só no cliente
não é logout: ele continuaria válido até expirar.

**Requer:** `Authorization: Bearer <access>`

**Resposta 200:** `{"data": {"revogados": 3}}`

---

## `GET /auth/eu`

Quem é o dono do token e o que ele pode fazer.

**Resposta 200**

```json
{"data": {"id": 1, "nome": "Ana Admin", "email": "admin@exemplo.com",
          "papel": "admin", "pode": ["cliente.listar", "cliente.criar", "..."]}}
```

---

## `GET /clientes`

Lista clientes, com paginação e busca.

**Query params**

| Nome | Tipo | Obrigatório | Descrição |
|---|---|---|---|
| `page` | int | não | Página, 1-based (padrão 1; valor < 1 vira 1) |
| `per_page` | int | não | Itens por página (padrão 20, **máx. 100**) |
| `q` | string | não | Busca parcial em nome e e-mail |
| `ativo` | bool | não | Filtra por status ativo/inativo |

**Resposta 200**

```json
{
  "data": [
    {
      "id": 1,
      "nome": "Ana Souza",
      "email": "ana@exemplo.com",
      "telefone": "11999990000",
      "ativo": true,
      "criado_em": "2026-08-31T23:48:21+00:00"
    }
  ],
  "meta": { "page": 1, "per_page": 20, "total": 25, "total_pages": 2 }
}
```

---

## `GET /clientes/{id}`

Mostra um cliente.

**Resposta 200:** `{"data": { ...mesmo objeto de item da lista... }}`

**Erros:** `404 not_found`.

---

## `POST /clientes`

Cria um cliente.

**Corpo (JSON)**

```json
{ "nome": "Ana Souza", "email": "ana@exemplo.com", "telefone": "11999990000" }
```

Campos não listados são **ignorados** (proteção contra mass assignment): enviar `id`
ou `senha_hash` não tem efeito.

**Resposta 201:** `{"data": { ... }}` com o `id` atribuído.

**Erros**

| Status | Quando |
|---|---|
| 422 | `nome` vazio, `nome` > 120 caracteres, `email` ausente/inválido, `telefone` > 20 |
| 409 | já existe cliente com este e-mail |
| 403 | `plan_limit_reached` — a conta atingiu o teto de clientes do plano |

---

## `PUT /clientes/{id}`

Substitui os dados do cliente. Mesmos campos e mesmas validações do `POST`.
Preserva `criado_em` e o status do cliente.

**Resposta 200:** `{"data": { ... }}`

**Erros:** `422`, `409` (e-mail de **outro** cliente), `404`.

---

## `DELETE /clientes/{id}`

Remove o cliente.

**Resposta:** `204`, sem corpo.

**Erros:** `404 not_found`.

---

## `GET /clientes-lixeira`

Lista os clientes excluídos (soft delete) que ainda podem ser restaurados.

**Papel exigido:** `admin` (mesma permissão de excluir — quem exclui, restaura).

**Resposta 200:** `{"data": [ ...mesmos campos do item de lista... ]}`

---

## `POST /clientes/{id}/restaurar`

Tira o cliente da lixeira. A ação vai para a auditoria, como a exclusão.

**Papel exigido:** `admin`

**Resposta 200:** `{"data": { ... }}` · **Erros:** `404 not_found`

---

## `GET /clientes-exportar`

Exporta os clientes ativos em CSV (`;` como separador, com BOM UTF-8 para o Excel).

**Papel exigido:** `admin` ou `vendedor` — exportação tira dado do controle do
sistema, então `leitura` não pode.

**Query params**

| Nome | Valores | Descrição |
|---|---|---|
| `formato` | `csv` (padrão) ou `pdf` | CSV sai em streaming; PDF é gerado com dompdf |

**Resposta 200:** `text/csv` ou `application/pdf`, com `Content-Disposition: attachment`.

**Resposta 202:** acima de 1000 registros a exportação vira job e a resposta é
`{"data": {"mensagem": "...", "total": 1234}}` — o arquivo fica pronto em background.

---

## `POST /webhooks/pagamento`

Recebe eventos do gateway de pagamento. **Não usa token**: quem chama é o gateway, não
um usuário. A autenticação é a assinatura HMAC do corpo.

**Cabeçalho obrigatório:** `X-Assinatura: <hmac-sha256 do corpo cru, com WEBHOOK_SECRET>`

**Corpo (JSON)**

```json
{
  "id": "evt_12345",
  "type": "payment.succeeded",
  "data": { "assinatura_id": 1 }
}
```

Tipos tratados: `payment.succeeded`, `payment.failed`, `subscription.canceled`.
Qualquer outro tipo é ignorado com **200** — o gateway envia muitos eventos que não
interessam, e responder erro faria ele reenviar para sempre.

**Respostas**

| Status | Corpo | Quando |
|---|---|---|
| 200 | `{"status":"processado"}` | evento novo, aplicado |
| 200 | `{"status":"ja_processado"}` | evento repetido (idempotência) |
| 400 | `{"erro":"payload_invalido"}` | JSON inválido ou sem `id`/`type` |
| 401 | `{"erro":"assinatura_invalida"}` | HMAC não confere |
| 500 | `{"erro":"falha_interna"}` | erro nosso — o gateway deve reenviar |

> A idempotência é garantida pelo `UNIQUE` em `eventos_webhook.evento_externo_id`,
> não por um `if` na aplicação.

---

## Exemplos com curl

```bash
BASE=http://localhost:8000/api/v1

curl -s "$BASE/clientes?per_page=5&q=silva"

curl -s -X POST "$BASE/clientes" \
  -H 'Content-Type: application/json' \
  -d '{"nome":"Ana Souza","email":"ana@exemplo.com"}'

curl -s -X PUT "$BASE/clientes/1" \
  -H 'Content-Type: application/json' \
  -d '{"nome":"Ana S. Souza","email":"ana@exemplo.com"}'

curl -s -o /dev/null -w '%{http_code}\n' -X DELETE "$BASE/clientes/1"
```

> **Manutenção:** se um commit muda a entrada ou a saída de um endpoint,
> o mesmo commit atualiza a seção correspondente aqui. Doc desatualizada é pior que doc nenhuma —
> engana quem confia nela. O teste `DocumentacaoApiTest` falha se uma rota existir sem
> estar documentada.
