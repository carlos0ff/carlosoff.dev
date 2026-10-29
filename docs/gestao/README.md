# Gestão de clientes, projetos, contratos e cobranças

Módulo integrado ao painel existente (`/admin`), com a mesma autenticação, layout, auditoria e fila.

| Área | URL |
|---|---|
| Painel da equipe | `/admin/gestao` |
| Portal do cliente | `/portal` |
| API v1 | `/api/v1` (ver [API.md](API.md)) |
| Agente de referência | [agente/](agente/README.md) |
| Plano e decisões | [PLANO.md](PLANO.md) |

## Configuração (`.env`)

```dotenv
GESTAO_COMPANY_NAME="Sua Empresa Ltda"
GESTAO_COMPANY_DOCUMENT=00000000000000
GESTAO_COMPANY_EMAIL=contato@suaempresa.com.br   # vira signatário padrão (contratada)
GESTAO_COMPANY_CITY="São Paulo"
GESTAO_TIMEZONE=America/Sao_Paulo                 # fuso dos campos de data/hora do painel
GESTAO_API_REQUIRE_HTTPS=true                     # padrão: true fora de local/testing
GESTAO_API_RATE_LIMIT=60

SIGNATURE_DRIVER=clicksign
CLICKSIGN_BASE_URL=https://sandbox.clicksign.com/api/v3   # produção: https://app.clicksign.com/api/v3 (confirme na documentação)
CLICKSIGN_ACCESS_TOKEN=
CLICKSIGN_WEBHOOK_SECRET=

PAYMENT_DRIVER=manual
```

Rotinas obrigatórias:

```bash
php artisan migrate
php artisan db:seed --class=ContractTemplateSeeder   # 7 modelos base (pendentes de revisão jurídica)
php artisan queue:work                                # notificações, envio à Clicksign, download do PDF assinado, monitoramento
php artisan schedule:work                             # ou cron: * * * * * php artisan schedule:run
```

Tarefas agendadas (`routes/console.php`):

| Comando | Quando | O que faz |
|---|---|---|
| `gestao:generate-charges` | diário 06:00 | mensalidades dos contratos vigentes (idempotente por competência) |
| `gestao:dunning` | diário 09:00 | régua de cobrança e propostas de restrição |
| `gestao:monitor` | a cada 5 min | verificação HTTP + SSL |
| `gestao:check-domains` | diário | expiração de domínio via RDAP |
| `gestao:sync-signatures` | de hora em hora | consulta o provedor caso algum webhook tenha se perdido |

Utilitários:

- `gestao:credential {uuid} {nome}`: emite a credencial do agente.
- `gestao:clicksign-check`: valida o token e mostra a URL de webhook.

## Papéis

`users.role` define o acesso. Usuários existentes continuam `admin`.

| Papel | Pode |
|---|---|
| `admin` | tudo, incluindo aprovar restrições, congelar e enviar documentos, revisão jurídica de modelos, credenciais de automação e LGPD |
| `financeiro` | cobranças, baixas, estornos, contestação, negociação, política financeira e exceções; **propõe** restrição (não aprova) |
| `tecnico` | comandos de manutenção e reativação, credenciais de instalação, monitoramento |
| `cliente` | somente `/portal`, somente dados da própria empresa |

## Fluxos

**Cobrança.** Emitir uma cobrança não conta como receita. "Recebido" soma apenas pagamentos confirmados:

- baixa manual com responsável e evidência obrigatória (comprovante ou descrição verificável); ou
- webhook validado de um gateway (camada pronta, nenhum driver real ativo).

O comprovante enviado pelo cliente no portal **não** dá baixa: ele só avisa o financeiro para conferir.

**Régua (por contrato, valores configuráveis):**

1. lembrete antes do vencimento;
2. aviso no vencimento (a cobrança segue pendente);
3. avisos de atraso periódicos;
4. tolerância, durante a qual o serviço é mantido;
5. alerta interno ao fim da tolerância;
6. aviso prévio ao cliente, com data, forma de restrição, como regularizar e como contestar;
7. proposta de restrição.

A proposta passa pelo `SuspensionGuard` (10 verificações: fonte financeira, cobrança vencida, contrato vinculado e vigente, cláusula, tolerância, contestação, negociação, exceção autorizada, aviso prévio e estado do projeto). Por padrão ela fica **aguardando aprovação de um administrador**, e as verificações são refeitas no momento da aprovação.

O contrato nasce com a suspensão **desabilitada**. Para habilitar, é preciso marcar a opção e informar a cláusula contratual.

**Formas de restrição.** Todas são transparentes, reversíveis e não tocam em dados nem backups:

- bloquear novas solicitações (painel);
- restringir funcionalidades não essenciais (agente: `isRestricted()`);
- página de manutenção transparente (agente, HTTP 503);
- suspensão de hospedagem administrada por nós (execução manual registrada pela equipe);
- suspensão de serviços adicionais (painel).

**Reativação.** Quando a cobrança é paga ou cancelada, o sistema reativa automaticamente ou deixa aguardando aprovação, conforme a política do contrato. É idempotente: no máximo uma reativação por suspensão. Uma suspensão ainda não entregue ao agente é cancelada.

**Documentos.** O fluxo é:

1. Rascunho gerado do modelo, com variáveis preenchidas e escapadas; campos vazios ficam destacados.
2. Revisão.
3. Congelamento: gera o PDF e registra o SHA-256. Exige modelo com revisão jurídica registrada e nenhum campo vazio.
4. Envio à Clicksign pela fila, com retry e conferência do hash antes do envio.
5. Webhooks com HMAC registram a evidência de cada signatário.
6. No fechamento do envelope, o PDF assinado é arquivado com o próprio hash.

O contrato de desenvolvimento assinado ativa o contrato. O termo de escopo assinado registra o consentimento.

**Modelos de documento.** Os 7 modelos base (`database/seeders/gestao-templates/*.html`) trazem texto completo, com cláusulas numeradas e bloco de assinatura. Contratos novos já vêm com as cláusulas padrão preenchidas (`App\Support\DefaultContractClauses`: garantia, reajuste IPCA, multa/juros, LGPD, PI, foro etc.) e com SLA sugerido, tudo editável por contrato. Com o cadastro completo, os documentos saem sem campos vazios; o que ainda faltar aparece destacado e bloqueia o congelamento. O seeder atualiza os modelos base só enquanto **não** tiverem revisão jurídica registrada. Configure `GESTAO_COMPANY_NAME`, `GESTAO_COMPANY_DOCUMENT`, `GESTAO_COMPANY_EMAIL` e `GESTAO_COMPANY_CITY` para preencher os dados da contratada.

**Escopo adicional.** Orçamento enviado ao cliente. A aprovação no portal exige o aceite explícito e grava usuário, data, IP, navegador e o hash do texto exato apresentado. Se o orçamento mudar depois que a página foi aberta, a decisão é recusada. A equipe não consegue marcar uma solicitação como "aprovada".

**Validação de versão.** Fica pendente até o cliente responder. A ausência de resposta nunca é tratada como aceite.

**Monitoramento.** Usa HTTP, tempo de resposta, validade do SSL, RDAP, heartbeat do agente (versão, último backup, dependências) e incidentes técnicos após 3 falhas seguidas. Fica totalmente separado do financeiro: um incidente nunca gera suspensão.

## LGPD e retenção

- `Cliente → Exportar dados (JSON)`: portabilidade, sem as anotações internas.
- `Cliente → Anonimizar`: só funciona sem cobranças em aberto e sem contrato vigente. Substitui os dados pessoais e remove os contatos, mas preserva cobranças, pagamentos e documentos assinados pelo prazo legal (`config/gestao.php → retention`, confirme os prazos com seu contador e jurídico).
- A auditoria é append-only (o model bloqueia `update` e `delete`) e registra IP e user-agent.

## O que foi testado e o que depende de você

- **Automatizado:** 70 testes em `tests/Feature/Gestao`. Cobrem permissões, API (HMAC, replay, rotação, revogação, rate limit, isolamento), régua completa, bloqueios de suspensão, suspensão e reativação, documentos (hash, webhook inválido, duplicado), formato das chamadas à Clicksign (HTTP simulado), portal (isolamento, consentimento, SLA) e LGPD.
- **Real (MySQL + servidor + agente):** executado de ponta a ponta no projeto piloto (`php artisan db:seed --class=GestaoPilotSeeder`).
- **Não testado contra serviços reais:**
  - **Clicksign:** é preciso um token de sandbox. Rode `gestao:clicksign-check`, cadastre o webhook e envie um documento de teste. Os campos dos eventos v3 foram implementados conforme a documentação pública e devem ser conferidos com um evento real.
  - **Gateway de pagamento:** você escolheu baixa manual por enquanto. Um driver real deve implementar `App\Services\Billing\Gateways\PaymentGateway` e ser registrado no `PaymentGatewayManager`.
  - **Modelos de documento:** são rascunhos e **precisam de revisão jurídica** antes do uso comercial.
