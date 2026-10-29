<?php

namespace App\Enums;

enum AuditAction: string
{
    case Create = 'CREATE';
    case Update = 'UPDATE';
    case Delete = 'DELETE';
    case Restore = 'RESTORE';
    case Publish = 'PUBLISH';
    case Unpublish = 'UNPUBLISH';
    case Feature = 'FEATURE';
    case Unfeature = 'UNFEATURE';
    case Generate = 'GENERATE';
    case Lock = 'LOCK';
    case Send = 'SEND';
    case Sign = 'SIGN';
    case Approve = 'APPROVE';
    case Reject = 'REJECT';
    case Cancel = 'CANCEL';
    case Payment = 'PAYMENT';
    case Refund = 'REFUND';
    case Dispute = 'DISPUTE';
    case Notify = 'NOTIFY';
    case Command = 'COMMAND';
    case Suspend = 'SUSPEND';
    case Reactivate = 'REACTIVATE';
    case Revoke = 'REVOKE';
    case Rotate = 'ROTATE';
    case Export = 'EXPORT';
    case Anonymize = 'ANONYMIZE';
    case Access = 'ACCESS';
    case Webhook = 'WEBHOOK';
    case Api = 'API';

    public function label(): string
    {
        return match ($this) {
            self::Create => 'Criou',
            self::Update => 'Atualizou',
            self::Delete => 'Excluiu',
            self::Restore => 'Restaurou',
            self::Publish => 'Publicou',
            self::Unpublish => 'Despublicou',
            self::Feature => 'Destacou',
            self::Unfeature => 'Removeu destaque de',
            self::Generate => 'Gerou',
            self::Lock => 'Congelou',
            self::Send => 'Enviou',
            self::Sign => 'Assinou',
            self::Approve => 'Aprovou',
            self::Reject => 'Recusou',
            self::Cancel => 'Cancelou',
            self::Payment => 'Registrou pagamento de',
            self::Refund => 'Estornou',
            self::Dispute => 'Registrou contestação de',
            self::Notify => 'Notificou',
            self::Command => 'Registrou',
            self::Suspend => 'Suspendeu',
            self::Reactivate => 'Reativou',
            self::Revoke => 'Revogou',
            self::Rotate => 'Rotacionou',
            self::Export => 'Exportou dados de',
            self::Anonymize => 'Anonimizou',
            self::Access => 'Acessou',
            self::Webhook => 'Recebeu webhook de',
            self::Api => 'Chamada de API em',
        };
    }
}
