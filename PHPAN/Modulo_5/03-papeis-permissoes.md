# Aula 03 — Papéis e permissões: admin, vendedor, leitura

**Código:** [03-papeis-permissoes.php](03-papeis-permissoes.php) · **Aula:** [site](https://cursos.asllanmaciel.com.br/curso/phpan/aula/modulo-05-auth/03-papeis-permissoes)

## A ideia

Autenticação responde *"quem é você"*; autorização responde *"o que você pode fazer"*.
São checagens diferentes e **as duas são obrigatórias**: "o token é válido" não
significa "esse usuário pode excluir".

| Papel | O que faz |
|---|---|
| `admin` | tudo, inclusive excluir, gerenciar usuários e ver auditoria |
| `vendedor` | cria e edita; **não** exclui, não gerencia usuários |
| `leitura` | só consulta — investidor, suporte, integração somente-leitura |

**Princípio do menor privilégio:** cada papel tem só o necessário. Dar permissão a mais
"por via das dúvidas" é o erro mais comum e o mais caro de corrigir depois.

## Por que um Gate e não `if` espalhado

`if ($papel === 'admin')` dentro de cada controller funciona no começo e vira
inconsistência: um endpoint esquece de checar, outro checa errado, e ninguém consegue
responder "quem pode o quê?" sem ler o sistema inteiro.

Com a matriz em `Domain\Usuario\Gate`, essa pergunta se responde lendo **um arquivo** —
e o `.php` desta aula imprime a matriz completa direto dela.

Detalhe deliberado: ação desconhecida **nega**. `self::REGRAS[$acao] ?? []` devolve lista
vazia, então uma ação nova nasce fechada até alguém liberá-la explicitamente.

## 401 e 403 não são a mesma coisa

- **401 `unauthorized`** — não sei quem você é (token ausente/inválido).
- **403 `forbidden`** — sei quem você é, e você não pode fazer isso.

Confundir os dois quebra o cliente: um 401 costuma disparar "tente renovar o token", o
que não resolve nada quando o problema é permissão.

## Esconder botão é UX, não segurança

Esconder o botão "excluir" no HTML melhora a experiência. **Não protege nada**:
qualquer um com `curl` ou DevTools chama a rota direto. As duas coisas devem existir,
mas só a checagem no servidor impede o ataque.

A prova está no `.php`: o 403 vem de requisição crua, sem tela nenhuma envolvida.

## Painel e API pela mesma régua

É comum proteger a tela e esquecer que a mesma ação tem uma rota JSON equivalente. No
projeto, `AdminMiddleware` (painel) e `ExigirPapel` (API) consultam o **mesmo** `Gate`.
O `.php` verifica os dois caminhos.

## Papel × plano

Não confundir com o Módulo 8: **papel** define o que o usuário pode fazer dentro da
conta; **plano** define quanto a conta pode ter. Um vendedor numa conta no plano free
continua limitado pelo plano.
