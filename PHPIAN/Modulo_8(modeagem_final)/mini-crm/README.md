# Mini CRM

Agenda de contatos em PHP 8 + MySQL, sem framework. Cada usuário vê e edita
apenas os próprios contatos.

**Funciona:** cadastro/login, área autenticada, CRUD de contatos, busca por
nome/e-mail, CSRF em toda mutação, senha com `password_hash`, PDO com prepared
statements reais.

## Requisitos

- PHP 8.2 ou superior (`pdo_mysql`, `mbstring`)
- MySQL 5.7+ / MariaDB 10.4+

## Instalação no localhost

### 1. Banco de dados

Crie o banco e um usuário dedicado (não use o `root` da máquina):

```sql
CREATE DATABASE IF NOT EXISTS mini_crm
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'minicrm'@'localhost' IDENTIFIED BY 'minicrm_local_2026';
GRANT ALL PRIVILEGES ON mini_crm.* TO 'minicrm'@'localhost';
FLUSH PRIVILEGES;
```

Importe as tabelas:

```bash
mysql -u minicrm -p mini_crm < sql/schema.sql
```

No XAMPP/Laragon dá para fazer o mesmo pelo phpMyAdmin: crie o banco `mini_crm`
e use **Importar** com o arquivo `sql/schema.sql`.

### 2. Configuração

```bash
cp config/app.example.php config/app.php
```

Edite `config/app.php` com host, banco, usuário e senha.
Esse arquivo está no `.gitignore` — a senha do banco nunca vai para o repositório.

### 3. Usuário administrador

```bash
php scripts/seed.php
```

Cria `admin@minicrm.local` com a senha `admin12345`. Para escolher os dados:

```bash
php scripts/seed.php --nome="Maria" --email=maria@exemplo.com --senha=segredo123
```

Esqueceu a senha? `php scripts/seed.php --email=maria@exemplo.com --senha=nova12345 --redefinir`

### 4. Subir o servidor

Servidor embutido do PHP (o mais rápido para testar):

```bash
php -S localhost:8000 -t public
```

Acesse **http://localhost:8000**

Com Apache (XAMPP/Laragon), coloque a pasta em `htdocs`/`www` e acesse
`http://localhost/mini-crm/public/`. O caminho base é detectado sozinho; se algo
sair torto, fixe em `config/app.php`:

```php
'base_url' => '/mini-crm/public',
```

## Estrutura

```
config/     app.php (ignorado pelo git) e app.example.php
sql/        schema.sql — as duas tabelas
src/        bootstrap, db, helpers, csrf, auth, contatos
templates/  header, footer, form_contato (compartilhado por criar e editar)
public/     única pasta exposta na web
scripts/    seed.php — cria o admin
```

`src/` guarda a lógica: as páginas em `public/` só orquestram
(`requireAuth()` → validar → repositório → redirect).

## Como funciona o isolamento entre usuários

Todas as funções de `src/contatos.php` exigem `$userId`, e o filtro entra no SQL
— inclusive no `UPDATE` e no `DELETE`:

```php
UPDATE contatos SET ... WHERE id = ? AND user_id = ?
DELETE FROM contatos     WHERE id = ? AND user_id = ?
```

Pedir `editar.php?id=` de um contato alheio devolve **404**, igual a um id que
não existe — a resposta não confirma que aquele registro existe.

## Testar com dois usuários

1. Crie o segundo usuário: `php scripts/seed.php --email=teste@exemplo.com --senha=teste12345`
2. Logue com o primeiro, cadastre um contato e anote o `id` da URL de edição.
3. Saia, entre com o segundo e abra `/contatos/editar.php?id=<aquele id>`.
4. Esperado: **404 Contato não encontrado**.

## Antes de publicar

- `'debug' => false` em `config/app.php`
- `'allow_registration' => false` depois de criar os usuários
- nenhum `info.php` ou arquivo de teste dentro de `public/`
- apontar o domínio para a pasta `public/`, nunca para a raiz do projeto
- HTTPS ligado (o cookie de sessão vira `secure` sozinho)
