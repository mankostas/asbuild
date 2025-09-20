<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
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
            return $this->resolveNumericIdentifier($modelClass, $identifier);
        }

        if (is_string($identifier) && ctype_digit($identifier)) {
            return $this->resolveNumericIdentifier($modelClass, (int) $identifier);
        }

        if (! is_string($identifier)) {
            return null;
        }

        return $this->resolvePublicIdentifier($modelClass, $identifier);
    }

    /**
     * Resolve a numeric identifier by looking up the model in the database.
     */
    protected function resolveNumericIdentifier(string $modelClass, int $identifier): ?int
    {
        $cacheKey = $modelClass.'|#|'.$identifier;

        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $query = $this->newQueryForModel($modelClass);

        return $this->cache[$cacheKey] = $query
            ->whereKey($identifier)
            ->value('id');
    }

    /**
     * Resolve a hashed public identifier by looking up the model in the database.
     */
    protected function resolvePublicIdentifier(string $modelClass, string $identifier): ?int
    {
        $cacheKey = $modelClass.'|'.$identifier;

        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $query = $this->newQueryForModel($modelClass);

        return $this->cache[$cacheKey] = $query
            ->where('public_id', $identifier)
            ->value('id');
    }

    /**
     * Build a new query instance for the given model.
     *
     * @param class-string<Model> $modelClass
     * @return Builder<Model>
     */
    protected function newQueryForModel(string $modelClass): Builder
    {
        $query = $modelClass::query();

        if (in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)) {
            $query->withTrashed();
        }

        return $query;
    }
}
