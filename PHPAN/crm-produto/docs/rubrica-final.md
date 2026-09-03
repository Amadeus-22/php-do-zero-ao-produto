# Rubrica de entrega — CRM de produto

Cada linha é **feito** ou **não feito**, com evidência. "Quase funciona" e "não
funciona" têm o mesmo efeito no cliente.

| Área | Critério | Status | Evidência |
|---|---|:--:|---|
| Domínio | `Cliente`, `Contato`, `Atividade` com relacionamento coerente | ✅ | `migrations/*.up.sql`, `src/Domain/` |
| Web | Painel lista/cria/vê cliente, layout consistente | ✅ | `views/`, `Modulo_3/*.php` |
| Web | Validação com feedback por campo + old input | ✅ | `Modulo_3/05-validacao-formularios.php` (14 checks) |
| Web | CSRF em todo formulário de escrita | ✅ | `Modulo_8/04-hardening-final.php` (formulários × tokens) |
| API | `/api/v1` com CRUD completo de clientes | ✅ | `docs/api.md`, `tests/Http/RotasTest.php` |
| API | Paginação, filtro e erro padronizado | ✅ | `Modulo_4/04-paginacao-filtros-erros.php` (17 checks) |
| Auth | Sessão no painel + token na API | ✅ | `Modulo_5/01`, `Modulo_5/02` |
| Auth | Dois papéis com permissão diferente | ✅ | 3 papéis; matriz testada em `tests/Auth/PapeisTest.php` |
| Produto | Upload com validação de tipo real e tamanho | ✅ | `UploadService` + rota `POST /clientes/{id}/anexos` e download protegido |
| Produto | Job de e-mail em fila, não no request | ✅ | `bin/worker.php`, `tests/Filas/FilaTest.php` |
| Produto | Logs estruturados e soft delete | ✅ | `var/logs/app.jsonl`, `Modulo_6/06` |
| Produto | Busca e exportação CSV **e PDF** | ✅ | `?formato=pdf` com dompdf |
| Produto | Lembretes com tela e conclusão | ✅ | `GET /lembretes`, `POST /clientes/{id}/lembretes` |
| Auth | Reset de senha com telas | ✅ | `/esqueci-senha` e `/redefinir-senha` |
| Auth | Consulta de auditoria (só admin) | ✅ | `GET /auditoria/{entidade}/{id}` |
| Produção | `.env` + `.env.example` sincronizados | ✅ | verificado em `Modulo_7/01` |
| Produção | Migrações versionadas do zero ao schema atual | ✅ | `php bin/migrate.php up` em banco vazio |
| Produção | Backup + `/health` + rollback testado | ✅ (parcial) | backup **restaurado** (RTO 1s); rollback de código é script não executado |
| Produção | Staging e produção distintos | ❌ | ambiente único — plano em `docs/staging.md` |
| Produção | Deploy com HTTPS, document root correto | ❌ | scripts em `deploy/`, sem servidor |
| Monetização | Dois planos com limite **aplicado** | ✅ | bloqueia no Service e devolve 403 `plan_limit_reached` na API |
| Monetização | Webhook com assinatura e idempotência | ✅ | `tests/Billing/PlanoEWebhookTest.php` |
| Documentação | README de instalação e uso | ✅ | `README.md` |

## Pendências, com motivo

| Pendência | Por quê | Quando |
|---|---|---|
| Staging e produção | não há VPS nem domínio | quando houver hospedagem |
| Deploy + HTTPS | idem | idem |
| Domínio, SPF/DKIM/DMARC | idem — plano em `docs/plano-dominio-email.md` | idem |
| Rate limit no webhook | endpoint público sem limite de frequência | próxima iteração |
| `UNIQUE` + soft delete | e-mail de excluído ainda ocupa a constraint | decisão de schema pendente |
| Multi-tenant real | `conta_id` existe em `clientes`, mas `usuarios` ainda é conta única | fora de escopo do PHPAN |

## Portão de qualidade

```
composer quality   # estilo + PHPStan level 5 + PHPUnit + composer audit
```

**137 testes, 320 asserções.** As 47 aulas executáveis rodam com 0 falhas.
