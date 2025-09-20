<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PublicIdResolver
{
    /**
     * Cache of resolved identifiers to internal ids.
     *
     * @var array<string, int|null>
     */
    protected array $cache = [];

    /**
     * Resolve a model identifier that may be a hashed public id or numeric id.
     */
    public function resolve(string $modelClass, string|int|null $identifier): ?int
    {
        if ($identifier === null || $identifier === '') {
            return null;
        }

        if (is_int($identifier)) {
            return $identifier;
        }

        if (is_string($identifier) && ctype_digit($identifier)) {
            return (int) $identifier;
        }

        if (! is_string($identifier)) {
            return null;
        }

        $cacheKey = $modelClass.'|'.$identifier;

        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        /** @var class-string<Model> $modelClass */
        $query = $modelClass::query();

        if (in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)) {
            $query->withTrashed();
        }

        return $this->cache[$cacheKey] = $query
            ->where('public_id', $identifier)
            ->value('id');
    }
}
