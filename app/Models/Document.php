<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'client_id', 'client_project_id', 'contract_id', 'scope_request_id', 'project_validation_id', 'contract_template_id',
    'template_version', 'type', 'title', 'version', 'supersedes_id', 'body_html', 'visible_to_client', 'created_by',
])]
class Document extends Model
{
    use HasUuid;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'status' => DocumentStatus::class,
            'visible_to_client' => 'boolean',
            'locked_at' => 'datetime',
            'sent_at' => 'datetime',
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ClientProject::class, 'client_project_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function scopeRequest(): BelongsTo
    {
        return $this->belongsTo(ScopeRequest::class);
    }

    public function validation(): BelongsTo
    {
        return $this->belongsTo(ProjectValidation::class, 'project_validation_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ContractTemplate::class, 'contract_template_id');
    }

    public function signers(): HasMany
    {
        return $this->hasMany(DocumentSigner::class)->orderBy('sign_order');
    }

    public function events(): HasMany
    {
        return $this->hasMany(SignatureEvent::class)->latest('id');
    }

    /**
     * Depois de congelado (PDF + hash), o conteúdo não pode mais ser editado.
     */
    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }

    public function isEditable(): bool
    {
        return ! $this->isLocked() && in_array($this->status, [DocumentStatus::Draft, DocumentStatus::UnderReview], true);
    }

    public function isFinal(): bool
    {
        return in_array($this->status, [DocumentStatus::Signed, DocumentStatus::Refused, DocumentStatus::Cancelled, DocumentStatus::Expired], true);
    }
}
