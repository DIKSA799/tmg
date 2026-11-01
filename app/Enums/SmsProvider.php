<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum SmsProvider: string
{
    use HasLabel;

    case Termii = 'termii';
    case Sendchamp = 'sendchamp';

    public function label(): string
    {
        return match ($this) {
            self::Termii => 'Termii',
            self::Sendchamp => 'Sendchamp',
        };
    }

    /**
     * The sender id configured for this provider, or null when it is unset.
     */
    public function senderId(): ?string
    {
        $senderId = config("services.{$this->value}.sender_id");

        return is_string($senderId) && $senderId !== '' ? $senderId : null;
    }

    /**
     * Whether this provider is enabled and has both a key and a sender id.
     */
    public function configured(): bool
    {
        return (bool) config("services.{$this->value}.enabled")
            && filled(config("services.{$this->value}.key"))
            && $this->senderId() !== null;
    }
}
