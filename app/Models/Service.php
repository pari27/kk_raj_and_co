<?php

namespace App\Models;

use App\Models\Concerns\HasHashedRouteKey;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['name', 'description', 'default_price', 'gst_percent', 'price_includes_gst', 'is_active', 'created_by'])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory, HasHashedRouteKey;

    protected function casts(): array
    {
        return [
            'default_price' => 'decimal:2',
            'gst_percent' => 'decimal:2',
            'price_includes_gst' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ServiceDocument::class);
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'subject')->latest('created_at');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The GST amount in rupees, whether the default price is GST-inclusive or not.
     */
    public function gstAmount(): float
    {
        $price = (float) $this->default_price;
        $gstPercent = (float) $this->gst_percent;

        if ($this->price_includes_gst) {
            return round($price - ($price / (1 + $gstPercent / 100)), 2);
        }

        return round($price * $gstPercent / 100, 2);
    }

    /**
     * The total fee in rupees: the default price already includes GST when
     * price_includes_gst is set, otherwise GST is added on top.
     */
    public function totalFee(): float
    {
        $price = (float) $this->default_price;

        return $this->price_includes_gst ? $price : round($price + $this->gstAmount(), 2);
    }

    /**
     * Same GST math as gstAmount()/totalFee(), but for an arbitrary base price
     * (e.g. an admin-overridden price on an enquiry line) instead of this
     * service's own default_price.
     *
     * @return array{price: float, gst_amount: float, total: float}
     */
    public function priceBreakdown(float $price): array
    {
        $gstPercent = (float) $this->gst_percent;

        $gstAmount = $this->price_includes_gst
            ? round($price - ($price / (1 + $gstPercent / 100)), 2)
            : round($price * $gstPercent / 100, 2);

        $total = $this->price_includes_gst ? $price : round($price + $gstAmount, 2);

        return ['price' => $price, 'gst_amount' => $gstAmount, 'total' => $total];
    }
}
