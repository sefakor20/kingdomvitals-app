<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchAnalytic extends Model
{
    use HasUuids;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'searched_all_branches' => 'boolean',
            'results_by_type' => 'array',
        ];
    }

    /**
     * Log a search query.
     *
     * @param  array<string, mixed>  $data
     */
    public static function log(string $query, array $data = []): self
    {
        return static::create([
            'user_id' => auth()->id(),
            'branch_id' => $data['branch_id'] ?? null,
            'query' => $query,
            'query_normalized' => strtolower(trim($query)),
            'searched_all_branches' => $data['searched_all_branches'] ?? false,
            'results_count' => $data['results_count'] ?? 0,
            'results_by_type' => $data['results_by_type'] ?? null,
            'selected_type' => $data['selected_type'] ?? null,
            'selected_id' => $data['selected_id'] ?? null,
        ]);
    }

    /**
     * Get the user who performed the search.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Get the branch context for the search.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
