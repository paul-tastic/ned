<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class AlertChannel extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'config',
        'is_active',
        'notify_warning',
        'notify_critical',
        'last_notified_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'notify_warning' => 'boolean',
        'notify_critical' => 'boolean',
        'last_notified_at' => 'datetime',
    ];

    protected $hidden = [
        'config',
    ];

    /**
     * Encrypt/decrypt the config JSON.
     * Contains sensitive data like webhook URLs, API keys, etc.
     */
    protected function config(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => json_decode(Crypt::decryptString($value), true),
            set: fn (array $value) => Crypt::encryptString(json_encode($value)),
        );
    }

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForLevel($query, string $level)
    {
        return $query->where(
            $level === 'critical' ? 'notify_critical' : 'notify_warning',
            true
        );
    }

    // Config helpers for different channel types

    public function getWebhookUrl(): ?string
    {
        return $this->config['webhook_url'] ?? null;
    }

    public function getEmailAddress(): ?string
    {
        return $this->config['email'] ?? null;
    }

    public function getSlackChannel(): ?string
    {
        return $this->config['channel'] ?? null;
    }

    /**
     * Check if this channel should receive an alert of a given level.
     */
    public function shouldNotify(string $level): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return $level === 'critical'
            ? $this->notify_critical
            : $this->notify_warning;
    }

    /**
     * Mark this channel as having sent a notification.
     */
    public function markNotified(): void
    {
        $this->update(['last_notified_at' => now()]);
    }
}
