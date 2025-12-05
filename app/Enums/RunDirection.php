<?php

namespace App\Enums;

enum RunDirection: string
{
    case INBOUND = 'INBOUND';
    case OUTBOUND = 'OUTBOUND';

    public function isInbound(): bool
    {
        return $this === self::INBOUND;
    }

    public function isOutbound(): bool
    {
        return $this === self::OUTBOUND;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $direction): string => $direction->value,
            self::cases()
        );
    }
}
