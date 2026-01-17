<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasFilterableQuery
{
    /**
     * Apply a search filter across multiple fields.
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  array<string>  $fields
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    protected function applySearch(Builder $query, array $fields): Builder
    {
        if (! property_exists($this, 'search') || $this->search === '' || $this->search === '0') {
            return $query;
        }

        $search = $this->search;

        return $query->where(function ($q) use ($search, $fields): void {
            foreach ($fields as $field) {
                $q->orWhere($field, 'like', "%{$search}%");
            }
        });
    }

    /**
     * Apply an enum/status filter.
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    protected function applyEnumFilter(Builder $query, string $filterProperty, string $column): Builder
    {
        if (! property_exists($this, $filterProperty)) {
            return $query;
        }

        $value = $this->{$filterProperty};

        if ($value === '' || $value === '0' || $value === null) {
            return $query;
        }

        return $query->where($column, $value);
    }

    /**
     * Apply a boolean toggle filter (converts string to boolean).
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    protected function applyBooleanFilter(Builder $query, string $filterProperty, string $column, string $trueValue): Builder
    {
        if (! property_exists($this, $filterProperty)) {
            return $query;
        }

        $value = $this->{$filterProperty};

        if ($value === '' || $value === null) {
            return $query;
        }

        return $query->where($column, $value === $trueValue);
    }

    /**
     * Apply date range filters.
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    protected function applyDateRange(Builder $query, string $column, ?string $dateFrom = null, ?string $dateTo = null): Builder
    {
        $from = $dateFrom ?? (property_exists($this, 'dateFrom') ? $this->dateFrom : null);
        $to = $dateTo ?? (property_exists($this, 'dateTo') ? $this->dateTo : null);

        if ($from) {
            $query->whereDate($column, '>=', $from);
        }

        if ($to) {
            $query->whereDate($column, '<=', $to);
        }

        return $query;
    }

    /**
     * Check if a filter value is active (not empty).
     */
    protected function isFilterActive(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return $value !== '' && $value !== '0';
        }

        return true;
    }
}
