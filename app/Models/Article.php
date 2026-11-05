<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Article extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'namespace',
        'protection',
        'current_rev',
    ];

    public function currentRevision(): BelongsTo
    {
        return $this->belongsTo(Revision::class, 'current_rev');
    }

    public function revisions(): HasMany
    {
        // `id` breaks ties between revisions saved within the same second.
        return $this->hasMany(Revision::class)->latest()->latest('id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Return the subset of $slugs that exist as articles (used for redlink detection).
     *
     * @param  array<int, string>  $slugs
     * @return array<int, string>
     */
    public static function existingSlugs(array $slugs): array
    {
        $slugs = array_values(array_unique($slugs));

        if ($slugs === []) {
            return [];
        }

        $found = [];

        // Chunked to stay below SQLite's bound-parameter limit on very link-heavy pages.
        foreach (array_chunk($slugs, 500) as $chunk) {
            foreach (static::query()->whereIn('slug', $chunk)->pluck('slug') as $slug) {
                $found[] = (string) $slug;
            }
        }

        return $found;
    }
}
