# Gestão de Clientes, Projetos, Contratos e Cobranças — plano de implementação

## Análise do projeto existente

`~/carlos0ff.dev` é um Laravel 13 (PHP 8.5, Blade + Tailwind 4, MySQL via Sail) com:

- painel `/admin` com login por sessão, `throttle:5,1`, middleware `admin` (`users.is_admin`);
- componentes Blade do painel (`x-admin.*`) e tema de cores (`.admin-theme` em `app.css`);
- auditoria em `audit_logs` (`AuditLog::record()` + enum `AuditAction`);
- fila `database`, cache `database`, e-mail `log` em desenvolvimento.

O módulo novo **entra nesse painel**: reaproveita autenticação, layout, componentes, auditoria e fila.
Não existe API nem portal de cliente, então eles são criados aqui.

`App\Models\Project` já é o projeto do **portfólio**. Os projetos dos clientes são `ClientProject`
(tabela `client_projects`) e o módulo vive em `/admin/gestao/*`.

## Decisões

| Tema | Decisão |
|---|---|
| Papéis | `users.role`: `admin`, `financeiro`, `tecnico` (equipe, `is_admin = true`) e `cliente` (portal, `client_id`). Gates por área. |
| Status do projeto | 4 colunas independentes: `development_status`, `contract_status`, `financial_status`, `operational_status`. |
| Dinheiro | Inteiros em centavos. "Contratado", "faturado", "recebido", "vencido" e "previsto" são calculados separadamente. |
| Cobrança | Interface `PaymentGateway`. Hoje só existe o driver `manual`: baixa com responsável, evidência e auditoria. Rota de webhook genérica pronta para um driver real. |
| Assinatura | Interface `SignatureProvider` + driver **Clicksign API v3** (envelopes). O driver `fake` só existe nos testes automatizados e não pode ser escolhido em produção. |
| PDF | `barryvdh/laravel-dompdf`. O PDF enviado para assinatura é imutável e tem o SHA-256 registrado. |
| API | `/api/v1/projects/{uuid}/...`, HMAC-SHA256 por credencial (key id + secret criptografado), timestamp e nonce contra replay, `Idempotency-Key` nos comandos, rate limit por credencial, HTTPS obrigatório fora de `local/testing`. |
| Agente | Pull: o agente busca comandos pendentes (não exige porta aberta no servidor do cliente), valida a assinatura da resposta, aplica a política local e confirma com `ack`. |
| Suspensão | Nunca é automática por padrão: `SuspensionGuard` verifica a fonte financeira, o contrato, a política, a contestação, a negociação e as exceções, e exige aviso prévio. O resultado vira um comando `pending_approval`. |
| Monitoramento | Separado do financeiro: falha técnica abre um incidente e nunca dispara suspensão. |

## Etapas

1. Base: config `gestao`, enums, migrations reversíveis, models e factories, papéis e gates, auditoria estendida.
2. Cadastros: clientes (+ contatos e anexos), projetos, domínios e hospedagem.
3. Contratos: contrato + versões, política financeira, modelos com variáveis, aprovação jurídica do modelo.
4. Documentos: os 7 tipos, geração do PDF, hash, signatários, envio à Clicksign, webhook, sincronização.
5. Financeiro: cobranças (avulsas e recorrentes), pagamentos manuais, estornos, contestação e negociação, régua de cobrança, inadimplência.
6. Operacional: comandos remotos, suspensão e reativação com guard, API v1, credenciais (emitir, rotacionar, revogar), agente de referência.
7. Monitoramento: probe HTTP/SSL, heartbeat do agente, RDAP de domínio, incidentes.
8. Portal do cliente: projetos, documentos, cobranças, validações, chamados, solicitações e orçamentos, notificações.
9. Dashboard com indicadores e filtros, auditoria e LGPD (exportação e anonimização).
10. Testes automatizados, documentação, piloto (seeder) e teste real (servidor + agente).
