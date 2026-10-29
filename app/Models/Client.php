<?php

namespace App\Models;

use App\Enums\ClientStatus;
use App\Models\Concerns\HasUuid;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'person_type', 'legal_name', 'trade_name', 'document', 'email', 'phone', 'whatsapp',
    'address_street', 'address_number', 'address_complement', 'address_district', 'address_city', 'address_state', 'address_zip',
    'billing_email', 'billing_notes', 'relationship_started_at', 'status', 'internal_notes',
])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory, HasUuid, SoftDeletes;

    /**
     * Campos que nunca aparecem no portal do cliente.
     *
     * @var list<string>
     */
    public const INTERNAL_FIELDS = ['internal_notes'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ClientStatus::class,
            'relationship_started_at' => 'date',
            'anonymized_at' => 'datetime',
        ];
    }

    public function displayName(): string
    {
        return $this->trade_name ?: $this->legal_name;
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(ClientContact::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(ClientProject::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function charges(): HasMany
    {
        return $this->hasMany(Charge::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function portalUsers(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * E-mail para avisos financeiros: o de cobrança, se houver.
     */
    public function billingRecipient(): string
    {
        return $this->billing_email ?: $this->email;
    }
}
