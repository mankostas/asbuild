<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class UserNotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'category',
        'inapp',
        'email',
        'sms',
    ];

    public static array $categories = [
        'assignment',
        'sla',
        'upload',
        'comment',
        'system',
    ];

    public static function for(User $user, string $category): self
    {
        return static::firstOrCreate(
            ['user_id' => $user->id, 'category' => $category],
            ['inapp' => true, 'email' => true, 'sms' => false],
        );
    }

    public static function forUser(User $user): Collection
    {
        $prefs = static::where('user_id', $user->id)->get()->keyBy('category');
        foreach (self::$categories as $cat) {
            if (! isset($prefs[$cat])) {
                $prefs[$cat] = static::create([
                    'user_id' => $user->id,
                    'category' => $cat,
                    'inapp' => true,
                    'email' => true,
                    'sms' => false,
                ]);
            }
        }
        return $prefs->values();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
