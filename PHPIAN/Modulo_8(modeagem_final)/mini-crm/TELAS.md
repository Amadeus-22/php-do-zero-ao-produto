# Esboço das telas

Quatro telas, quatro estados. Nada de modal, nada de JavaScript.

## 1. Login — `public/login.php`

```
┌──────────────────────────────────┐
│           Mini CRM               │
│                                  │
│  Entrar                          │
│  Acesse sua área de contatos.    │
│                                  │
│  E-mail                          │
│  [____________________________]  │
│  Senha                           │
│  [____________________________]  │
│                                  │
│  [ Entrar ]                      │
│                                  │
│  Ainda não tem conta? Cadastre-se│
└──────────────────────────────────┘
```

| Campo | Tipo | Regra |
|---|---|---|
| `email` | email | obrigatório |
| `senha` | password | obrigatório |
| `_csrf` | hidden | token da sessão |

Erro exibido é sempre genérico: *"E-mail ou senha inválidos."*

## 2. Lista — `public/contatos/index.php`

```
┌────────────────────────────────────────────────────────────┐
│ MiniCRM   Contatos  Novo contato          Maria    Sair    │
├────────────────────────────────────────────────────────────┤
│ Contatos                              [ + Novo contato ]   │
│ 12 contatos na sua agenda                                  │
│                                                            │
│ [ buscar por nome ou e-mail...    ] [Buscar] [Limpar]      │
│                                                            │
│ ┌────────────────────────────────────────────────────────┐ │
│ │ NOME        E-MAIL          TELEFONE    CRIADO   ...   │ │
│ ├────────────────────────────────────────────────────────┤ │
│ │ Ana Lima    ana@ex.com      11 90000... 12/08   Ed|Ex  │ │
│ │  cliente antigo, ligar 3ª…                             │ │
│ │ Bruno Sá    —               11 98888... 12/08   Ed|Ex  │ │
│ └────────────────────────────────────────────────────────┘ │
└────────────────────────────────────────────────────────────┘
```

| Campo | Tipo | Regra |
|---|---|---|
| `q` | search (GET) | opcional; casa com `nome` **ou** `email` |

Estados: lista cheia · "nenhum resultado para X" · "sua agenda está vazia".

## 3. Novo — `public/contatos/criar.php`

```
┌──────────────────────────────────┐
│ Novo contato                     │
│ Só o nome é obrigatório.         │
│                                  │
│ Nome *                           │
│ [____________________________]   │
│ E-mail                           │
│ [____________________________]   │
│ Telefone                         │
│ [(11) 90000-0000____________]    │
│ Notas                            │
│ [                            ]   │
│ [                            ]   │
│                                  │
│ [ Salvar contato ]  [ Cancelar ] │
└──────────────────────────────────┘
```

| Campo | Tipo | Regra |
|---|---|---|
| `nome` | text | **obrigatório**, até 120 |
| `email` | email | opcional, formato válido, até 180 |
| `telefone` | text | opcional, até 30, só `0-9 ( ) + - . espaço` |
| `notas` | textarea | opcional, até 5000 |
| `_csrf` | hidden | obrigatório |

Sucesso → redirect para a lista (POST/Redirect/GET).

## 4. Editar — `public/contatos/editar.php?id=N`

Mesmos campos da tela 3, pré-preenchidos, mais:

- subtítulo com a data de criação;
- botão **Excluir** no canto superior direito;
- rótulo do botão vira "Salvar alterações".

O formulário é **o mesmo arquivo** (`templates/form_contato.php`); mudam só `$acao` e `$rotuloBotao`.

## 4b. Excluir — `public/contatos/excluir.php?id=N`

```
┌──────────────────────────────────┐
│ Excluir contato                  │
│ Esta ação não pode ser desfeita. │
│                                  │
│ Excluir Ana Lima (ana@ex.com)?   │
│                                  │
│ [ Sim, excluir ]   [ Cancelar ]  │
└──────────────────────────────────┘
```

GET só mostra a confirmação. Quem apaga é o POST com `_csrf`.
