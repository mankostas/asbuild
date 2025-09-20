<?php

namespace App\Support;

use Illuminate\Support\Str;

class PublicIdGenerator
{
    public static function generate(): string
    {
        return (string) Str::ulid();
    }
}
