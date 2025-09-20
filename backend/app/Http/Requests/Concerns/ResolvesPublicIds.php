<?php

namespace App\Http\Requests\Concerns;

use App\Support\PublicIdResolver;

trait ResolvesPublicIds
{
    protected ?PublicIdResolver $publicIdResolverInstance = null;

    /**
     * Resolve a model identifier (hashed public id or numeric id) to its internal primary key.
     */
    protected function resolvePublicId(string $modelClass, string|int|null $identifier): ?int
    {
        if ($identifier === null || $identifier === '') {
            return null;
        }

        return $this->publicIdResolver()->resolve($modelClass, $identifier);
    }

    protected function publicIdResolver(): PublicIdResolver
    {
        return $this->publicIdResolverInstance ??= app(PublicIdResolver::class);
    }
}
