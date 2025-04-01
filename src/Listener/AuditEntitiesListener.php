<?php

declare(strict_types=1);

namespace App\Listener;

use App\Entity\AuditableInterface;
use App\Entity\AuditEvent;
use App\Entity\AuditEventRelation;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::onFlush)]
final class AuditEntitiesListener
{
    private string $mode = 'persist';

    public function onFlush(OnFlushEventArgs $event): void
    {
        $entityManager = $event->getObjectManager();
        $unitOfWork = $event->getObjectManager()->getUnitOfWork();
        $eventMetadata = $entityManager->getClassMetadata(AuditEvent::class);
        $eventRelationMetadata = $entityManager->getClassMetadata(AuditEventRelation::class);
        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof AuditableInterface) {
                continue;
            }

            $entityChangeSet = $unitOfWork->getEntityChangeSet($entity);
            $payload = [];
            foreach ($entityChangeSet as $property => [$new, $old]) {
                $payload["old_{$property}"] = $old;
                $payload["new_{$property}"] = $new;
            }

            $event = new AuditEvent($entity, 'updated', $payload);

            if ($this->mode === 'persist') {
                $entityManager->persist($event);
                $unitOfWork->computeChangeSet($eventMetadata, $event);
                foreach ($event->getRelations() as $relation) {
                    $entityManager->persist($relation);
                    $unitOfWork->computeChangeSet($eventRelationMetadata, $relation);
                }
            } elseif ($this->mode === 'record') {
                $entity->recordEvent($event);
                $entityMetadata = $entityManager->getClassMetadata($entity::class);
                $unitOfWork->recomputeSingleEntityChangeSet($entityMetadata, $entity);
                // still no idea how to do it here
            }
        }
    }

    /**
     * @param 'persist'|'record' $mode
     */
    public function mode(string $mode): void
    {
        $this->mode = $mode;
    }
}
