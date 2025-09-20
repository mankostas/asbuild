<?php

namespace App\Models\Concerns;

use App\Support\PublicIdGenerator;
use Illuminate\Database\Eloquent\Model;

trait HasPublicId
{
    public static function bootHasPublicId(): void
    {
        static::creating(function (Model $model): void {
            if (empty($model->public_id)) {
                $model->public_id = static::generatePublicId();
            }
        });
    }

    protected function initializeHasPublicId(): void
    {
        $casts = $this->casts ?? [];
        $casts['public_id'] = 'string';
        $this->casts = $casts;

        $hidden = $this->hidden ?? [];
        $hidden[] = 'id';
        $this->hidden = array_values(array_unique($hidden));

        if (is_array($this->fillable) && ! in_array('public_id', $this->fillable, true)) {
            $this->fillable[] = 'public_id';
        }
    }

    public static function generatePublicId(): string
    {
        return PublicIdGenerator::generate();
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
