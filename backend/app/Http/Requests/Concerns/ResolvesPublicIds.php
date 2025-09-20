<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Database\Eloquent\Model;

trait ResolvesPublicIds
{
    /**
     * Cache for resolved public IDs.
     *
     * @var array<string, int|null>
     */
    protected array $resolvedPublicIds = [];

    /**
     * Resolve a model's public identifier to its internal numeric id.
     */
    protected function resolvePublicId(string $modelClass, ?string $publicId): ?int
    {
        if ($publicId === null || $publicId === '') {
            return null;
        }

        $cacheKey = $modelClass.'|'.$publicId;

        if (array_key_exists($cacheKey, $this->resolvedPublicIds)) {
            return $this->resolvedPublicIds[$cacheKey];
        }

        /** @var Model $modelClass */
        return $this->resolvedPublicIds[$cacheKey] = $modelClass::query()
            ->where('public_id', $publicId)
            ->value('id');
    }
}
