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

    /**
     * An employee only sees enquiries that have a service assigned to them.
     */
    public function scopeVisibleTo(\Illuminate\Database\Eloquent\Builder $query, User $user): void
    {
        if ($user->isEmployee()) {
            $query->whereHas('tickets', fn ($tickets) => $tickets->where('assigned_to', $user->id));
        }
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

    /**
     * The fee figures worked out from the services that are left once $without is gone.
     * A service's own total includes its GST, so the net part is that total less the GST.
     *
     * @return array{subtotal: float, gst_total: float, total: float}
     */
    public function feesWithout(Ticket $without): array
    {
        $remaining = $this->tickets->reject(fn (Ticket $ticket) => $ticket->is($without));

        $subtotal = round((float) $remaining->sum(fn (Ticket $ticket) => (float) $ticket->total - (float) $ticket->gst_amount), 2);
        $gstTotal = round((float) $remaining->sum(fn (Ticket $ticket) => (float) $ticket->gst_amount), 2);

        return [
            'subtotal' => $subtotal,
            'gst_total' => $gstTotal,
            'total' => max(0.0, round($subtotal + $gstTotal - (float) $this->discount, 2)),
        ];
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
