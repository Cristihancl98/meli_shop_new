<?php

namespace App\Enums;

enum BatchItemStatus: string
{
    case Queued     = 'queued';
    case Scraping   = 'scraping';
    case Ready      = 'ready';
    case NotFound   = 'not_found';
    case Duplicate  = 'duplicate';
    case Publishing = 'publishing';
    case Published  = 'published';
    case Failed     = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Queued     => 'En cola',
            self::Scraping   => 'Buscando',
            self::Ready      => 'Listo para revisar',
            self::NotFound   => 'No encontrado',
            self::Duplicate  => 'Ya publicado',
            self::Publishing => 'Publicando',
            self::Published  => 'Publicado',
            self::Failed     => 'Falló',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Ready, self::Published         => 'active',
            self::Queued, self::Scraping,
            self::Publishing                     => 'pending',
            self::NotFound, self::Failed         => 'cancelled',
            self::Duplicate                      => 'paused',
        };
    }

    public function inProgress(): bool
    {
        return in_array($this, [self::Queued, self::Scraping, self::Publishing], true);
    }

    public function canPublish(): bool
    {
        return in_array($this, [self::Ready, self::Failed], true);
    }

    public function canRescrape(): bool
    {
        return in_array($this, [self::NotFound, self::Failed], true);
    }
}
