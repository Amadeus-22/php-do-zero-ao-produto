# Checklist de segurança

Estado do projeto, item a item. Cada linha aponta onde o controle vive.

## Autenticação

- [x] **Senha nunca em texto puro** — `password_hash($senha, PASSWORD_DEFAULT)` em `usuario_criar()`, ponto único usado pela web e pelo seed. `src/auth.php`
- [x] **Verificação em tempo constante** — `password_verify()`, sem `==` de hash. `src/auth.php`
- [x] **Rehash automático** — `password_needs_rehash()` atualiza o algoritmo no login. `src/auth.php`
- [x] **Senha mínima de 8 caracteres** — `usuario_validar()`. `src/auth.php`
- [x] **Erro de login genérico** — "E-mail ou senha inválidos" não revela se o e-mail existe. `public/login.php`
- [x] **`session_regenerate_id(true)` no login** — bloqueia fixação de sessão. `src/auth.php`
- [x] **Cookie de sessão endurecido** — `httponly`, `samesite=Lax`, `secure` sob HTTPS. `sessao_iniciar()`
- [x] **Logout destrói sessão e cookie** — `auth_logout()`
- [x] **Logout só por POST + CSRF** — um `<img src="/logout.php">` não derruba a sessão. `public/logout.php`

## Controle de acesso

- [x] **`requireAuth()` no topo de toda página do CRM** — `contatos/index|criar|editar|excluir`
- [x] **Ownership no SELECT** — `contato_buscar($id, $userId)`
- [x] **Ownership no UPDATE e no DELETE** — o `AND user_id = ?` está no SQL, não numa checagem que a página pode esquecer. `src/contatos.php`
- [x] **Contato alheio devolve 404, não 403** — a resposta não confirma a existência do registro. `contatos/editar.php`
- [x] **Filtro obrigatório por assinatura** — nenhuma função de contato compila sem `$userId`

## Injeção

- [x] **Prepared statements em 100% das queries** — nenhuma concatenação de variável em SQL
- [x] **`PDO::ATTR_EMULATE_PREPARES => false`** — prepares reais no servidor. `src/db.php`
- [x] **`ERRMODE_EXCEPTION`** — falha explode em vez de seguir silenciosa. `src/db.php`
- [x] **Curingas de `LIKE` neutralizados** — `like_escape()` impede que `%` na busca vaze a lista inteira. `src/contatos.php`
- [x] **Ids convertidos para `int`** — `(int) $_REQUEST['id']`

## XSS

- [x] **`e()` em toda saída dinâmica** — `htmlspecialchars` com `ENT_QUOTES | ENT_SUBSTITUTE` e UTF-8. `src/helpers.php`
- [x] **Valores reexibidos após erro também escapados** — inclusive o termo de busca e os campos do formulário
- [x] **Sem `innerHTML`, sem JavaScript** — nenhuma superfície de DOM XSS

## CSRF

- [x] **Token de 32 bytes por sessão** — `random_bytes(32)`. `src/csrf.php`
- [x] **`hash_equals()` na comparação** — sem vazamento por tempo
- [x] **`csrf_check()` em todos os POST** — login, cadastro, logout, criar, editar, excluir
- [x] **Nenhuma mutação por GET** — excluir confirma em GET e apaga em POST

## Validação de entrada

- [x] **Validação única compartilhada** — `contato_validar()` serve criar e editar; `usuario_validar()` serve cadastro e seed
- [x] **Limites iguais aos da coluna** — nome 120, e-mail 180, telefone 30
- [x] **`FILTER_VALIDATE_EMAIL`** no e-mail de usuário e de contato
- [x] **Telefone por lista branca** — `/^[0-9()+\-\s.]+$/`
- [x] **Validação no servidor** — o `maxlength` do HTML é conforto, não defesa

## Configuração e exposição

- [x] **Só `public/` fica exposta** — `src/`, `config/`, `sql/` e `scripts/` ficam acima da raiz web
- [x] **`config/app.php` no `.gitignore`** — senha de banco fora do repositório
- [x] **`app.example.php` versionado sem credencial**
- [x] **`display_errors` amarrado ao `debug`** — desligue em produção. `src/bootstrap.php`
- [x] **Seed recusa execução via web** — `PHP_SAPI !== 'cli'` responde 403
- [x] **Nenhum `info.php` ou arquivo de debug em `public/`**
- [x] **Usuário MySQL dedicado com permissão só no `mini_crm`** — não o `root` da máquina
- [x] **Auto-cadastro desligável** — `'allow_registration' => false`

## Fora do escopo deste projeto

Consciente, não esquecido — vale citar numa entrevista:

- [ ] Rate limit / bloqueio após N tentativas de login
- [ ] Recuperação de senha por e-mail
- [ ] Log de auditoria (quem alterou o quê)
- [ ] Cabeçalhos `Content-Security-Policy` e `X-Frame-Options`
- [ ] Expiração de sessão por inatividade
