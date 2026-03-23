<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'email_sender_name',
    ];

    /**
     * Get the user that owns the setting.
     *
     * @return BelongsTo<User, UserSetting>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
