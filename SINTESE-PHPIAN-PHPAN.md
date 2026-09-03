# Síntese: do PHPIAN ao PHPAN

Um mapa do que foi aprendido nos dois cursos, e — mais importante — **onde cada
coisa do PHPIAN reaparece transformada no PHPAN**.

A ideia central é uma só:

> No PHPIAN você aprendeu a **fazer funcionar**.
> No PHPAN você aprendeu a **fazer aguentar** — mudança, usuário, tempo e outra pessoa mexendo.

---

## 1. O mapa inteiro

```mermaid
mindmap
  root((PHP<br/>do zero ao produto))
    PHPIAN — fundamentos
      Sintaxe
        variáveis, condicionais, loops
        funções, arrays, strings
      Web básico
        GET e POST
        request e response
        upload de arquivo
      Banco
        conexão PDO
        prepared statements
        INSERT UPDATE DELETE
      Segurança inicial
        hash de senha
        sessions e cookies
      Mini CRM
        arquivos soltos
        tudo em uma página
    PHPAN — produto
      Estrutura
        camadas
        PSR-4 e Composer
        interfaces e contratos
      Entrada HTTP
        front controller
        rotas e middleware
        controller fino
      Contrato de API
        recursos e verbos
        status HTTP
        envelope e versão
      Segurança séria
        sessão + token
        papéis e permissões
        rate limit e auditoria
      Operação
        migrações
        filas e worker
        logs e health check
        backup e rollback
      Negócio
        planos e limites
        webhook de pagamento
```

---

## 2. A escada de conceitos

Do que o usuário clica até onde o dado descansa. Cada degrau existe para responder
**uma** pergunta.

```mermaid
flowchart TD
    U["👤 Usuário<br/><i>clica, digita, envia</i>"]

    subgraph rede["A conversa pela rede"]
        REQ["<b>Requisição HTTP</b><br/>método + caminho + corpo<br/><i>GET /clientes/3</i>"]
        EP["<b>Endpoint</b><br/>o endereço que responde<br/><i>/api/v1/clientes</i>"]
    end

    subgraph app["Sua aplicação"]
        FC["<b>Front Controller</b><br/>porta única<br/><i>public/index.php</i>"]
        RT["<b>Router</b><br/>quem atende esta rota?"]
        MW["<b>Middleware</b><br/>pode passar?<br/><i>auth, CSRF, papel</i>"]
        CT["<b>Controller</b><br/>traduz HTTP ⇄ negócio<br/><i>fino</i>"]
        SV["<b>Service</b><br/>a regra do negócio<br/><i>gordo, sem HTTP</i>"]
        RP["<b>Repository</b><br/>o único que fala SQL"]
        EN["<b>Entidade</b><br/>o dado com suas regras"]
    end

    DB[("🗄️ Banco")]
    VW["<b>View</b> ou <b>JSON</b><br/><i>a resposta</i>"]

    U -->|1| REQ --> EP --> FC
    FC -->|2| RT -->|3| MW
    MW -->|"❌ barrou"| VW
    MW -->|"✅ 4"| CT
    CT -->|5| SV -->|6| RP -->|7| DB
    DB -.->|8| RP -.->|"objetos, não arrays"| SV
    SV -.->|9| CT
    EN -.-|"molda"| SV
    CT -->|10| VW --> U

    style U fill:#e8f4ff,stroke:#4a90d9
    style rede fill:#fff8e8,stroke:#d9a441
    style app fill:#f0f8f0,stroke:#5aa85a
    style DB fill:#ffeaea,stroke:#d95a5a
    style VW fill:#f4e8ff,stroke:#9a5ad9
```

### O que é cada coisa, em uma frase

| Termo | O que é | No projeto |
|---|---|---|
| **API** | um contrato para programas conversarem entre si — sem tela, só dados | `/api/v1` |
| **Endpoint** | um endereço específico dessa API | `POST /api/v1/clientes` |
| **Rota** | a regra que liga endereço + método a um código seu | `routes/api.php` |
| **Front Controller** | a porta única por onde toda requisição entra | `public/index.php` |
| **Router** | quem lê a rota e escolhe o controller | `src/Http/Router.php` |
| **Middleware** | filtro que roda **antes** e pode barrar | `AuthMiddleware`, `CsrfMiddleware` |
| **Controller** | traduz HTTP para chamada de negócio e vice-versa | `ClienteController` |
| **Service** | a regra do negócio, sem saber que HTTP existe | `ClienteService` |
| **Repository** | a única camada que sabe SQL | `RepositorioDeClientesPdo` |
| **Entidade** | o dado com as regras que sempre valem | `Cliente` |
| **View** | só apresentação, com escape obrigatório | `views/clientes/index.php` |

### A pergunta que decide a camada

```mermaid
flowchart LR
    Q{"Onde<br/>isso vai?"} --> A["Depende de $_GET,<br/>$_POST ou header?"] -->|sim| C[Controller]
    Q --> B["É regra do negócio,<br/>valha web ou API?"] -->|sim| S[Service]
    Q --> D["É SQL?"] -->|sim| R[Repository]
    Q --> E["É só dado<br/>com invariante?"] -->|sim| N[Entidade]
    Q --> F["É só HTML?"] -->|sim| V[View]

    style C fill:#e8f4ff
    style S fill:#f0f8f0
    style R fill:#ffeaea
    style N fill:#fff8e8
    style V fill:#f4e8ff
```

> **O teste definitivo do Service:** *a regra sobrevive se a chamada vier de um cron?*
> Se sim, é Service. Se ela precisa de `$_POST` para existir, é Controller.

---

## 3. A interseção: cada arquivo do PHPIAN tem um endereço no PHPAN

Este é o coração da síntese. Nada do que você aprendeu foi jogado fora — **tudo virou
outra coisa, maior**.

```mermaid
flowchart LR
    subgraph I["PHPIAN"]
        direction TB
        I1["conexão_PDO_segura.php"]
        I2["SELECT_prepared_statements.php"]
        I3["sessions_cookies.php"]
        I4["hash_de_senhas.php"]
        I5["request_response.php"]
        I6["json_dados_para_front_apis.php"]
        I7["upload_de_arquivos.php"]
        I8["validacao_sanitizacao.php"]
        I9["estrutura_de_pastas.txt"]
        I10["classe_objetos(intro).php"]
        I11["mini-crm/"]
    end

    subgraph A["PHPAN"]
        direction TB
        A1["Repository + interface"]
        A2["EMULATE_PREPARES=false"]
        A3["Auth\Sessao + TokenService"]
        A4["Usuario::senhaConfere()"]
        A5["Request / Response / Router"]
        A6["Resource + envelope {data}"]
        A7["UploadService + finfo"]
        A8["Validator + invariante"]
        A9["PSR-4 em 4 camadas"]
        A10["Entidade de domínio"]
        A11["crm-produto/"]
    end

    I1 --> A1
    I2 --> A2
    I3 --> A3
    I4 --> A4
    I5 --> A5
    I6 --> A6
    I7 --> A7
    I8 --> A8
    I9 --> A9
    I10 --> A10
    I11 --> A11

    style I fill:#fff4e6,stroke:#d9a441
    style A fill:#e8f7e8,stroke:#5aa85a
```

### O mesmo problema, as duas respostas

| Situação | PHPIAN | PHPAN | Por que mudou |
|---|---|---|---|
| Buscar cliente | `$pdo->query()` na página | `RepositorioDeClientes` (interface) | trocar de banco não deve mexer na regra |
| Evitar injeção | `prepare()` | `prepare()` + `EMULATE_PREPARES=false` | quem prepara passa a ser o banco |
| Login | `$_SESSION['id'] = 1` | sessão endurecida + `session_regenerate_id` | session fixation |
| API | `echo json_encode($linhas)` | `Resource` + envelope + status | contrato estável para quem integra |
| Formulário | `if ($_POST['nome'] == '')` | `Validator` + invariante na entidade | a regra vale nos dois pontos de entrada |
| Upload | conferir extensão | `finfo` no conteúdo + nome novo + fora de `public/` | extensão é escolhida pelo atacante |
| Erro | `die('erro')` | exceção de domínio + status HTTP | `die()` derruba a API inteira |
| Organização | `include 'header.php'` | PSR-4 + camadas | achar onde uma regra mora |
| Excluir | `DELETE FROM` | soft delete + lixeira + auditoria | o usuário vai clicar errado |

---

## 4. As quatro camadas, e a regra da seta

```mermaid
flowchart TB
    subgraph P["🌐 Apresentação"]
        P1["Controllers, Views, JSON"]
        P2["<i>fala HTTP</i>"]
    end
    subgraph AP["⚙️ Aplicação"]
        AP1["Casos de uso"]
        AP2["<i>orquestra, sem saber quem chamou</i>"]
    end
    subgraph D["💎 Domínio"]
        D1["Entidades, enums, exceções"]
        D2["<b>Interfaces de repositório</b>"]
        D3["<i>não conhece nada externo</i>"]
    end
    subgraph IN["🔌 Infraestrutura"]
        IN1["PDO, e-mail, arquivo, PDF"]
        IN2["<i>implementa o que o domínio declarou</i>"]
    end

    P -->|chama| AP -->|usa| D
    IN -.->|"implementa ⬆"| D

    style P fill:#f4e8ff,stroke:#9a5ad9
    style AP fill:#e8f4ff,stroke:#4a90d9
    style D fill:#fff8e8,stroke:#d9a441
    style IN fill:#ffeaea,stroke:#d95a5a
```

**A seta da infraestrutura aponta para cima.** É o que permite trocar arquivo por MySQL
sem que domínio, service ou controller percebam — e foi cobrado na prática: quando o
banco entrou, a troca foi **uma linha** no `Container`.

---

## 5. Autenticação e autorização: perguntas diferentes

```mermaid
flowchart TD
    R["Requisição chega"] --> A{"Quem é você?<br/><b>autenticação</b>"}
    A -->|"sem credencial"| E401["❌ 401 unauthorized"]
    A -->|"sessão (painel)"| OK1["✅ identificado"]
    A -->|"Bearer (API)"| OK1
    OK1 --> B{"O que você pode?<br/><b>autorização — papel</b>"}
    B -->|"papel não permite"| E403["❌ 403 forbidden"]
    B -->|"pode"| C{"Quanto a conta<br/>pode ter?<br/><b>plano</b>"}
    C -->|"limite atingido"| E403P["❌ 403 plan_limit_reached"]
    C -->|"dentro do limite"| OK["✅ executa"]

    style E401 fill:#ffeaea
    style E403 fill:#ffeaea
    style E403P fill:#ffeaea
    style OK fill:#e8f7e8
```

Três perguntas independentes, três respostas diferentes. Confundi-las é o erro comum:
`401` faz o cliente tentar renovar o token — inútil quando o problema era permissão.

| Conceito | Pergunta | Onde vive |
|---|---|---|
| **Autenticação** | quem é você? | `Sessao`, `TokenService` |
| **Autorização (papel)** | o que **você** pode fazer? | `Gate` |
| **Plano** | quanto a **conta** pode ter? | `PlanLimiter` |

---

## 6. O que roda fora da requisição

Nem tudo acontece enquanto o usuário espera.

```mermaid
sequenceDiagram
    participant U as Usuário
    participant C as Controller
    participant F as Fila (jobs)
    participant W as Worker
    participant M as E-mail

    U->>C: POST /clientes
    C->>F: despacha job
    C-->>U: 201 Created (imediato)
    Note over U,C: o usuário já foi embora
    W->>F: pega o próximo
    W->>M: envia
    alt falhou
        W->>F: reagenda com backoff<br/>60s, 120s, 240s...
    else 5 falhas
        W->>F: marca "falhou" (dead-letter)
    end
```

O mesmo padrão serve para lembretes (cron despacha, worker envia) e para exportações
grandes (acima de 1000 registros vira job).

---

## 7. Produção: o que ninguém vê e todo mundo sente

```mermaid
flowchart LR
    subgraph antes["Antes de subir"]
        M1[".env por ambiente"]
        M2["migrações versionadas"]
        M3["backup"]
    end
    subgraph deploy["Subindo"]
        D1["release + symlink"]
        D2["--no-dev"]
        D3["reload do FPM"]
    end
    subgraph depois["Depois"]
        P1["/health"]
        P2["logs .jsonl"]
        P3["rollback: troca o link"]
    end

    antes --> deploy --> depois
    depois -.->|"algo quebrou"| P3

    style antes fill:#fff8e8
    style deploy fill:#e8f4ff
    style depois fill:#e8f7e8
```

**A lição mais dura:** backup sem restauração testada é crença, não backup. O RTO real
deste projeto — **1 segundo** — foi medido restaurando de verdade, não estimado.

---

## 8. Onde estudar cada coisa

| Quero entender | Rodar | Ler |
|---|---|---|
| camadas e o ciclo HTTP | `php PHPAN/Modulo_3/01-ciclo-http-camadas.php` | `.md` ao lado |
| rota, controller, middleware | `Modulo_3/02` e `Modulo_3/06` | idem |
| o que é API na prática | `Modulo_4/01-recursos-verbos-status.php` | idem |
| contrato JSON e mass assignment | `Modulo_4/02-json-contratos.php` | idem |
| token, papéis, auditoria | `Modulo_5/02`, `05/03`, `05/06` | idem |
| fila, logs, upload | `Modulo_6/01`, `06/02`, `06/03` | idem |
| migrações e backup | `Modulo_7/02` e `07/05` | idem |
| planos e webhook | `Modulo_8/01` e `08/02` | idem |

Cada aula é um arquivo que **roda e se verifica**: imprime o que está checando e falha
se o projeto divergir.

---

## 9. As sete lições que sobrevivem a qualquer linguagem

1. **Uma regra, um lugar.** Se ela existe em dois arquivos, um deles vai ficar
   desatualizado — e ninguém vai perceber.
2. **Fail closed.** Sem plano, o limite é zero, não infinito. Ação desconhecida no
   `Gate` nega. O padrão seguro é negar.
3. **Não confie no cliente.** Nome de arquivo, extensão, `Content-Type`, campo do JSON,
   token — tudo vem de fora e tudo é forjável.
4. **Verifique comportamento, não código.** Escrevi um teste que lia o código e passava
   com o limite de plano **desligado**. Só criar acima do teto provou que funcionava.
5. **Falhe cedo e alto.** Config ausente derruba no boot, com o nome da variável — não
   na primeira query, 40 minutos depois.
6. **O que não foi executado não está feito.** Serviço pronto sem rota é código que o
   usuário nunca alcança.
7. **Escreva o que ficou de fora.** Pendência com motivo é roadmap; pendência silenciosa
   é dívida que ninguém lembra.

---

## 10. Fechamento

```mermaid
flowchart LR
    A["PHPIAN<br/><i>faz funcionar</i>"] -->|"mini-crm"| B["PHPAN<br/><i>faz aguentar</i>"]
    B -->|"crm-produto"| C["PHPPRO<br/><i>faz escalar</i>"]

    style A fill:#fff4e6,stroke:#d9a441
    style B fill:#e8f7e8,stroke:#5aa85a
    style C fill:#e8f4ff,stroke:#4a90d9,stroke-dasharray: 5 5
```

O `mini-crm` do PHPIAN continua intocado como registro do ponto de partida. O
`crm-produto` é o mesmo domínio depois de atravessar 8 módulos.

**Estado:** 47 aulas executáveis · projeto com 137 testes e 320 asserções ·
`composer quality` verde (estilo, PHPStan level 5, testes, auditoria de dependências).

Pendências honestas em [`PHPAN/crm-produto/docs/rubrica-final.md`](PHPAN/crm-produto/docs/rubrica-final.md) —
todas por falta de servidor, nenhuma por falta de entendimento.
