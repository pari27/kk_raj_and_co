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

#[Fillable(['number', 'enquiry_id', 'service_id', 'customer_id', 'assigned_to', 'created_by', 'price', 'gst_percent', 'gst_amount', 'total', 'status', 'status_comment', 'status_comment_for', 'completed_at'])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory, HasHashedRouteKey;

    /**
     * The comment typed with the current status, or null once the status has moved on from it.
     */
    public function currentStatusComment(): ?string
    {
        return $this->status_comment !== null && $this->status_comment_for === $this->status?->value
            ? $this->status_comment
            : null;
    }

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

    /**
     * An employee only sees services assigned to them; admins see everything.
     */
    /**
     * Why this service can't be deleted, or null when it can. A service can go until a document has
     * been submitted for it, as long as the enquiry keeps another service and the payments already
     * recorded still fit inside what the enquiry would then total.
     */
    public function deletionBlocker(): ?string
    {
        if ($this->documents()->exists()) {
            return 'Documents have already been submitted for this service, so it can no longer be deleted.';
        }

        if ($this->status->isClosed()) {
            return "A completed service can't be deleted.";
        }

        $enquiry = $this->enquiry()->with(['tickets', 'payments'])->first();

        if ($enquiry->tickets->count() <= 1) {
            return "This is the only service in its enquiry, so it can't be deleted.";
        }

        $newTotal = $enquiry->feesWithout($this)['total'];

        if ($enquiry->amountPaid() > $newTotal) {
            return 'Payments of ₹'.number_format($enquiry->amountPaid(), 2).' are already recorded, which is more than the enquiry would total (₹'.number_format($newTotal, 2).') without this service.';
        }

        return null;
    }

    public function scopeVisibleTo(\Illuminate\Database\Eloquent\Builder $query, User $user): void
    {
        if ($user->isEmployee()) {
            $query->where('assigned_to', $user->id);
        }
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
