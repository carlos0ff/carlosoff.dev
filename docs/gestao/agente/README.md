# Agente central (referência)

Instalado em cada projeto de cliente. Usa PHP 8.1+, ext-curl e nenhuma dependência.

## Instalação

1. Copie a pasta `agente/` para o servidor do projeto (fora do diretório público).
2. No painel: `Gestão → Projeto → Credenciais da API → Emitir`. Ou pela linha de comando: `php artisan gestao:credential <uuid> servidor-prod`.
3. Configure as variáveis de ambiente do servidor. **Nunca** coloque o segredo no frontend nem no repositório.

```dotenv
CENTRAL_API_URL=https://painel.suaempresa.com.br/api/v1
CENTRAL_PROJECT_ID=<uuid>
CENTRAL_KEY_ID=cg_...
CENTRAL_SECRET=...
AGENT_STATE_DIR=/var/lib/central-agent           # gravável pelo usuário do cron e legível pelo PHP-FPM
AGENT_ALLOWED_COMMANDS=maintenance,suspend,reactivate
AGENT_ALLOWED_MODES=maintenance_page,restrict_non_essential
AGENT_DEPLOYED_VERSION=1.4.2
AGENT_BACKUP_MARKER=/var/backups/app/last-success  # arquivo tocado pelo job de backup
AGENT_BYPASS_PATHS=/up,/webhooks
AGENT_TIMEZONE=America/Sao_Paulo
```

4. Agende no cron:

```cron
* * * * * www-data /opt/central-agent/bin/central-agent run >> /var/log/central-agent.log 2>&1
```

5. Proteja o front controller. No topo do `public/index.php`:

```php
require '/opt/central-agent/src/State.php';
require '/opt/central-agent/src/MaintenanceGate.php';
\CentralAgent\MaintenanceGate::handle('/var/lib/central-agent');
```

6. Para restringir só funcionalidades não essenciais, consulte o estado local na aplicação:

```php
if (\CentralAgent\State::load('/var/lib/central-agent')->isRestricted()) { /* esconda o recurso X */ }
```

## Comandos

`central-agent heartbeat | sync | run | status | state`

## Garantias

- Requisições assinadas com HMAC, timestamp e nonce.
- Respostas de comandos assinadas e verificadas antes de executar qualquer coisa.
- HTTPS obrigatório, com verificação de certificado. `AGENT_ALLOW_INSECURE=1` só deve ser usado em desenvolvimento local.
- Comandos fora de `AGENT_ALLOWED_*` são **recusados** e confirmados como `rejected`.
- Se o painel estiver indisponível, o estado atual é mantido e nada é suspenso por falha de rede. Há timeouts (5 s para conectar, 15 s no total) e até 3 tentativas com backoff para 5xx e 429.
- O agente apenas alterna `state.json`. Nada apaga dados, mexe em backups ou degrada desempenho, e a reativação devolve o estado `active`.
- A janela de manutenção (`starts_at`/`ends_at`) é respeitada localmente, mesmo sem rede.
