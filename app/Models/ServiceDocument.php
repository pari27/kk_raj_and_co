<?php

namespace App\Models;

use Database\Factories\ServiceDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['service_id', 'name', 'instructions', 'input_type', 'allowed_formats', 'max_file_size_kb', 'is_mandatory'])]
class ServiceDocument extends Model
{
    /** @use HasFactory<ServiceDocumentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_mandatory' => 'boolean',
        ];
    }

    /**
     * Text documents are answered with a typed value (e.g. a PAN number)
     * instead of an uploaded file.
     */
    public function isText(): bool
    {
        return $this->input_type === 'text';
    }

    public function ticketDocuments(): HasMany
    {
        return $this->hasMany(TicketDocument::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * The max file size formatted for display, e.g. "5 MB" or "512 KB".
     */
    public function formattedMaxSize(): ?string
    {
        if (! $this->max_file_size_kb) {
            return null;
        }

        return $this->max_file_size_kb >= 1024
            ? rtrim(rtrim(number_format($this->max_file_size_kb / 1024, 1), '0'), '.').' MB'
            : $this->max_file_size_kb.' KB';
    }
}
