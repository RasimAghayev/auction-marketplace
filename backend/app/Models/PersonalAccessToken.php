<?php

namespace App\Models;

use App\Models\Concerns\MongodbConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\PersonalAccessToken as BasePersonalAccessToken;

class PersonalAccessToken extends BasePersonalAccessToken
{
    use HasFactory, MongodbConnection;

    protected $collection = 'personal_access_tokens';

    protected $primaryKey = '_id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        '_id',
        'tokenable_type',
        'tokenable_id',
        'name',
        'token',
        'abilities',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'abilities' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $hidden = [
        'token',
    ];

    public function tokenable()
    {
        return $this->morphTo();
    }

    public static function findToken($token): ?self
    {
        // Token format: tokenId|plainTextToken
        $parts = explode('|', $token, 2);
        $plainTextToken = $parts[1] ?? $token;
        $hash = hash('sha256', $plainTextToken);

        return static::where('token', $hash)->first();
    }
}
