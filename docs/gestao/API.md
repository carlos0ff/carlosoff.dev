# API v1 de gerenciamento remoto

Base: `https://<painel>/api/v1`. Todas as rotas exigem assinatura HMAC. O projeto é identificado pelo **UUID** (nunca pelo id sequencial).

## Autenticação

| Cabeçalho | Conteúdo |
|---|---|
| `X-Api-Key` | `key_id` da credencial (`cg_...`) |
| `X-Timestamp` | Unix time em segundos (tolerância de ±300 s) |
| `X-Nonce` | 16 a 64 caracteres `[A-Za-z0-9_-]`, único por requisição (reuso → 409) |
| `X-Signature` | `hex(HMAC-SHA256(secret, canonical))` |

```
canonical = MÉTODO + "\n" + CAMINHO_COM_QUERY + "\n" + TIMESTAMP + "\n" + NONCE + "\n" + hex(SHA256(corpo_bruto))
```

`CAMINHO_COM_QUERY` é o caminho exatamente como chega ao Laravel (ex.: `/api/v1/projects/{uuid}/status`). Se houver proxy que reescreve prefixo, assine o caminho visto pelo Laravel.

Regras:

- **TLS obrigatório** (`GESTAO_API_REQUIRE_HTTPS`, ligado fora de `local`/`testing`), com resposta `403 tls_required`.
- **Rate limit**: `GESTAO_API_RATE_LIMIT` req/min por credencial (padrão 60), com resposta `429`.
- Credencial inexistente, revogada, expirada ou com assinatura errada: `401 unauthenticated`. A mensagem é a mesma nos três casos e não revela qual deles ocorreu.
- A autenticação roda **antes** da resolução do projeto: sem credencial válida não dá para descobrir se um UUID existe.
- Projeto de outro cliente: `404` (autorização por projeto).

### Tipos de credencial

| Tipo | Escopo | Permissões |
|---|---|---|
| Instalação (agente) | um único projeto | `status:read`, `health:read`, `health:write`, `billing:read`, `commands:pull` |
| Automação | equipe (todos os projetos) | escolhidas na emissão; inclui `commands:issue` |

Uma instalação **nunca** recebe `commands:issue`: o cliente não consegue enviar comandos a si mesmo nem alterar a própria situação financeira.

Rotação: `Gestão → Projeto → Credenciais → Rotacionar` gera uma credencial nova. A antiga continua válida por `rotation_grace_hours` (24 h) e depois expira. A revogação é imediata. O segredo é guardado criptografado com a `APP_KEY` e mostrado uma única vez.

## Erros

```json
{ "error": { "code": "not_allowed", "message": "…", "guard": { "allowed": false, "checks": [ … ] } } }
```

Códigos: `unauthenticated`, `stale_request`, `replayed_request`, `tls_required`, `forbidden`, `idempotency_key_required`, `idempotency_conflict`, `not_allowed`, `not_found`. A validação de campos retorna `422` no formato padrão do Laravel.

## Endpoints

### `GET /projects/{uuid}/status` · `status:read`

```json
{ "data": {
  "project": { "id": "4ac1…", "name": "Projeto Piloto" },
  "development_status": "staging",
  "contract_status": "active",
  "operational_status": "active",
  "restriction": { "active": false, "mode": null },
  "deployed_version": "1.0.0",
  "agent_last_seen_at": "2026-10-14T21:57:02+00:00"
} }
```

Os estados são independentes. Valores possíveis:

- `development_status`: `planning|development|staging|awaiting_approval|completed`
- `contract_status`: `none|draft|awaiting_signature|active|suspended|ended|cancelled`
- `operational_status`: `active|scheduled_maintenance|suspended|terminated`

### `GET /projects/{uuid}/health` · `health:read`

Último resultado da verificação externa (`probe`: HTTP, tempo de resposta, SSL), do agente (`agent`: versão, último backup, erros na última hora) e a quantidade de incidentes técnicos abertos.

### `POST /projects/{uuid}/health` · `health:write` (agente)

```json
{ "state": "active", "deployed_version": "1.4.2", "agent_version": "1.0.0", "php_version": "8.4.1",
  "last_backup_at": "2026-10-14T03:00:00-03:00", "app_errors_last_hour": 0,
  "resources": { "cpu_load": 0.4, "memory_used_pct": 61, "disk_used_pct": 70 },
  "dependencies": [ { "name": "mysql", "ok": true, "latency_ms": 3 } ] }
```

Resposta `202 { "data": { "received_at": "…" } }`. **Somente essas chaves são gravadas.** Qualquer outro campo é descartado, para que segredos e dados pessoais não entrem nas métricas.

### `GET /projects/{uuid}/billing-status` · `billing:read`

- Instalação: `{ "financial_status": "overdue", "restricted": true, "regularization_url": "…/portal/cobrancas" }`
- Automação: inclui também `open_cents`, `overdue_cents` e `next_due_date`.

`financial_status` (`up_to_date|pending|overdue|negotiating|regularized`) é **sempre derivado das cobranças** e não é gravável pela API.

### `POST /projects/{uuid}/commands/{maintenance|suspend|reactivate}` · `commands:issue` (somente automação)

Cabeçalho obrigatório: `Idempotency-Key` (8 a 100 caracteres `[A-Za-z0-9:_-.]`).

```json
{ "reason": "Atualização do servidor", "message": "Voltamos às 23h",
  "starts_at": "2026-10-20T22:00:00-03:00", "ends_at": "2026-10-20T23:30:00-03:00",
  "charge_id": "<uuid da cobrança — obrigatório em suspend>" }
```

- `201` cria o comando; `200` devolve o mesmo comando quando a chave e o conteúdo se repetem; `409` quando a chave já foi usada com outro conteúdo.
- `suspend` passa pelo **SuspensionGuard**. Se alguma verificação falhar, retorna `422 not_allowed` com o relatório completo. A forma de restrição vem do contrato, não da requisição.
- Datas com offset são respeitadas. Sem offset, valem no fuso `GESTAO_TIMEZONE`.

```json
{ "data": { "id": "uuid", "type": "suspend", "status": "pending_approval", "channel": "agent",
  "mode": "maintenance_page", "reason": "…", "created_at": "…", "acknowledged_at": null } }
```

`status`: `pending_approval|queued|delivered|executed|rejected|failed|cancelled`.

`channel` define quem executa:

- `agent`: o agente do projeto.
- `central`: aplicado no próprio painel (bloqueio de novas solicitações ou de serviços adicionais).
- `operator`: a equipe executa manualmente e registra a evidência (suspensão de hospedagem).

### `GET /projects/{uuid}/commands/pending` · `commands:pull` (somente instalação)

```json
{ "data": [ { "id": "uuid", "type": "maintenance", "mode": "maintenance_page",
  "message": "Voltamos às 23h", "starts_at": "…", "ends_at": "…", "issued_at": "…" } ] }
```

A resposta é **assinada**: `X-Response-Timestamp` e `X-Response-Signature = hex(HMAC-SHA256(secret, timestamp + "\n" + hex(SHA256(corpo))))`. O agente só executa comandos depois de validar essa assinatura. Comandos entregues e ainda sem confirmação voltam a aparecer até receberem o `ack`.

### `POST /projects/{uuid}/commands/{commandUuid}/ack` · `commands:pull`

```json
{ "status": "executed", "message": "Estado local: maintenance." }
```

`status` aceita `executed|rejected|failed`. É idempotente: a primeira confirmação vale e as seguintes são ignoradas. Só depois do `executed` o estado operacional muda no painel.

## Webhooks

| Rota | Autenticidade |
|---|---|
| `POST /api/webhooks/assinatura/clicksign` | `Content-Hmac: sha256=<hex>` = HMAC-SHA256 do corpo bruto com `CLICKSIGN_WEBHOOK_SECRET` |
| `POST /api/webhooks/pagamento/{provedor}` | definida pelo driver (nenhum driver de pagamento ativo hoje, então retorna `404`) |

Todo evento de assinatura fica registrado em `signature_events`, inclusive os inválidos, mas só eventos autênticos alteram documentos. A deduplicação usa o SHA-256 do corpo e considera apenas eventos válidos.
