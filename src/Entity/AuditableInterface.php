<?php

declare(strict_types=1);

namespace App\Entity;

interface AuditableInterface
{
    public function recordEvent(AuditEvent $event): void;
}
