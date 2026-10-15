<?php

namespace App\Models;

use App\Models\Concerns\HasHashedRouteKey;
use Database\Factories\TicketDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['ticket_id', 'service_document_id', 'version', 'file_path', 'original_filename', 'text_value', 'status', 'uploaded_by', 'uploaded_by_customer_id', 'verified_by', 'verified_at', 'rejection_reason'])]
class TicketDocument extends Model
{
    /** @use HasFactory<TicketDocumentFactory> */
    use HasFactory, HasHashedRouteKey;

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function serviceDocument(): BelongsTo
    {
        return $this->belongsTo(ServiceDocument::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function uploadedByCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'uploaded_by_customer_id');
    }

    /**
     * Who put this version in: a staff member's name, or "Client" for a portal upload.
     */
    public function uploaderLabel(): string
    {
        return $this->uploadedBy?->name ?? ($this->uploaded_by_customer_id ? 'Client' : 'Staff');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
