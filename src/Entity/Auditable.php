<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Couvre la majeure partie de {@see AuditableInterface}.
 */
trait Auditable
{
    public function recordEvent(AuditEvent $event): void
    {
        $this->events->add($event);
    }
}
