<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A named API key that belongs to a user.
 *
 * Only a SHA-256 hash of the key is persisted. The plaintext value is
 * returned exactly once, at creation time, and can never be retrieved again.
 */
#[Fillable(['name', 'scopes'])]
#[Hidden(['key_hash'])]
class ApiKey extends Model
{
    /** @use HasFactory<\Database\Factories\ApiKeyFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * Generate a new random plaintext key and its storable hash.
     *
     * @return array{plaintext: string, prefix: string, hash: string}
     */
    public static function generateSecret(): array
    {
        $random = Str::random(40);
        $prefix = 'sk_'.Str::lower(Str::random(8));
        $plaintext = $prefix.'_'.$random;

        return [
            'plaintext' => $plaintext,
            'prefix' => $prefix,
            'hash' => hash('sha256', $plaintext),
        ];
    }

    public static function hashSecret(string $plaintext): string
    {
        return hash('sha256', $plaintext);
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
