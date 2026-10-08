<?php

namespace App\Models;

use Database\Factories\ServiceDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['service_id', 'name', 'instructions', 'allowed_formats', 'max_file_size_kb', 'is_mandatory'])]
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
