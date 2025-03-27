<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity]
#[ORM\Table]
class AuditEventRelation
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int|null $id = null;

    #[ORM\ManyToOne(targetEntity: AuditEvent::class, inversedBy: 'relations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected AuditEvent $event;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private User|null $subjectUser = null;

    #[ORM\Column(type: 'string')]
    protected string $name;

    final public function __construct(AuditEvent $event, string $name, object $relation)
    {
        $this->name = $name;
        $this->event = $event;
        $this->setSubject($relation);
    }

    public function getId(): int|null
    {
        return $this->id;
    }

    public function getEvent(): AuditEvent
    {
        return $this->event;
    }

    public function getSubject(): object
    {
        $subject = $this->subjectUser
            ?? null;
        if (!\is_object($subject)) {
            throw new InvalidArgumentException('Subject must be defined');
        }

        return $subject;
    }

    protected function setSubject(object $object): void
    {
        switch (true) {
            case $object instanceof User:
                $this->subjectUser = $object;
                break;

            default:
                throw new InvalidArgumentException('Object of class ' . $object::class . ' is not supported.');
        }
    }
}
