<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Airport extends Model
{
    use HasFactory;

    protected $table = 'airports';

    protected $fillable = [
        'iata_code',
        'icao_code',
        'name',
        'city',
        'country',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Search by IATA code, name, city, or country. Used by the
     * /resort/airports/search endpoint that powers the Select2 AJAX
     * dropdown on apply-leave + employee-detail.
     */
    public function scopeSearch($query, ?string $term)
    {
        $term = trim((string) $term);
        if ($term === '') return $query;

        // IATA codes are 3 letters; if the user typed exactly that, prefer
        // a code-prefix match (instant for the common case).
        if (preg_match('/^[A-Za-z]{2,3}$/', $term)) {
            return $query->where(function ($q) use ($term) {
                $q->where('iata_code', 'LIKE', strtoupper($term) . '%')
                  ->orWhere('city',    'LIKE', $term . '%')
                  ->orWhere('name',    'LIKE', '%' . $term . '%')
                  ->orWhere('country', 'LIKE', $term . '%');
            });
        }

        return $query->where(function ($q) use ($term) {
            $q->where('city',     'LIKE', '%' . $term . '%')
              ->orWhere('name',    'LIKE', '%' . $term . '%')
              ->orWhere('country', 'LIKE', '%' . $term . '%')
              ->orWhere('iata_code', 'LIKE', strtoupper($term) . '%');
        });
    }

    /**
     * Display label used by the dropdown — same shape as the legacy
     * config-driven version so existing reads of `leave_destination`
     * (stored as "CODE - Name") stay consistent.
     */
    public function getDisplayLabelAttribute(): string
    {
        return $this->iata_code . ' - ' . $this->name
            . ($this->city ? ' (' . $this->city . ($this->country ? ', ' . $this->country : '') . ')' : '');
    }

    /**
     * The value persisted to leave_destination — kept stable across
     * migrations so older records still resolve to a label cleanly.
     */
    public function getValueAttribute(): string
    {
        return $this->iata_code . ' - ' . $this->name;
    }
}
