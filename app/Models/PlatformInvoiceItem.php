<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformInvoiceItem extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The database connection that should be used by the model.
     *
     * @var string
     */
    protected $connection = 'mysql';

    protected $fillable = [
        'platform_invoice_id',
        'description',
        'quantity',
        'unit_price',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (PlatformInvoiceItem $item): void {
            $item->total = $item->quantity * (float) $item->unit_price;
        });

        static::saved(function (PlatformInvoiceItem $item): void {
            $item->invoice->recalculateFromItems();
        });

        static::deleted(function (PlatformInvoiceItem $item): void {
            $item->invoice->recalculateFromItems();
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PlatformInvoice::class, 'platform_invoice_id');
    }
}
