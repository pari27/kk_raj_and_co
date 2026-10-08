<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Models\Concerns\HasHashedRouteKey;
use Database\Factories\EnquiryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['number', 'customer_id', 'created_by', 'notes', 'subtotal', 'gst_total', 'discount', 'total', 'status'])]
class Enquiry extends Model
{
    /** @use HasFactory<EnquiryFactory> */
    use HasFactory, HasHashedRouteKey;

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'gst_total' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function amountPaid(): float
    {
        return (float) $this->payments->sum('amount');
    }

    public function balanceDue(): float
    {
        return max(0.0, (float) $this->total - $this->amountPaid());
    }

    public function paymentStatus(): PaymentStatus
    {
        return match (true) {
            $this->amountPaid() <= 0 => PaymentStatus::Unpaid,
            $this->balanceDue() > 0 => PaymentStatus::PartiallyPaid,
            default => PaymentStatus::FullyPaid,
        };
    }
}
