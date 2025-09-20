<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

trait CreatesApplication
{
    public function createApplication(): Application
    {
        $app = require __DIR__ . '/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * Retrieve the hashed public identifier for the provided model instance.
     */
    protected function publicIdFor(Model $model): string
    {
        if (! $model->getAttribute('public_id')) {
            $model->refresh();
        }

        $publicId = $model->getAttribute('public_id');

        if (! is_string($publicId) || $publicId === '') {
            throw new RuntimeException(sprintf(
                'Model %s does not have a public identifier available for hashing tests.',
                $model::class
            ));
        }

        return $publicId;
    }

    /**
     * Map a list of models to their hashed public identifiers.
     *
     * @param iterable<Model> $models
     * @return array<int, string>
     */
    protected function publicIdsFor(iterable $models): array
    {
        $ids = [];

        foreach ($models as $model) {
            $ids[] = $this->publicIdFor($model);
        }

        return $ids;
    }

    /**
     * Resolve a hashed public identifier back to its underlying numeric id.
     *
     * @template TModel of Model
     *
     * @param class-string<TModel> $modelClass
     */
    protected function idFromPublicId(string $modelClass, string $publicId): int
    {
        /** @var TModel|null $model */
        $model = $modelClass::query()->where('public_id', $publicId)->first();

        $this->assertNotNull(
            $model,
            sprintf('Failed to resolve public identifier [%s] for model [%s].', $publicId, $modelClass)
        );

        $this->assertSame(
            $publicId,
            $model->getAttribute('public_id'),
            'Resolved model public identifier does not match the provided hash.'
        );

        return (int) $model->getKey();
    }
}
