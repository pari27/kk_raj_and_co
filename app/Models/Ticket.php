<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\TicketStatus;
use App\Models\Concerns\HasHashedRouteKey;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['number', 'enquiry_id', 'service_id', 'customer_id', 'assigned_to', 'created_by', 'price', 'gst_percent', 'gst_amount', 'total', 'status', 'completed_at'])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory, HasHashedRouteKey;

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'gst_percent' => 'decimal:2',
            'gst_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'status' => TicketStatus::class,
            'completed_at' => 'datetime',
        ];
    }

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(Enquiry::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TicketDocument::class);
    }

    /**
     * Payment is tracked per enquiry, not per ticket — an enquiry's total can
     * include a discount shared across several tickets/services, so these
     * delegate to the owning enquiry rather than keeping their own figures.
     */
    public function amountPaid(): float
    {
        return $this->enquiry->amountPaid();
    }

    public function balanceDue(): float
    {
        return $this->enquiry->balanceDue();
    }

    public function paymentStatus(): PaymentStatus
    {
        return $this->enquiry->paymentStatus();
    }
}
