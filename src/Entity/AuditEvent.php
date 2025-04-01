<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Table]
#[ORM\Entity]
class AuditEvent
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int|null $id = null;

    #[ORM\ManyToOne(targetEntity: Task::class, inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private Task|null $subjectTask = null;

    /**
     * @var Collection<int|string, AuditEventRelation>
     */
    #[ORM\OneToMany(targetEntity: AuditEventRelation::class, mappedBy: 'event', cascade: ['all'], indexBy: 'name')]
    private Collection $relations;

    #[ORM\Column(type: 'string', nullable: false)]
    private string $name;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: 'json', nullable: false)]
    private array $payload = [];

    #[ORM\Column(type: 'datetimetz_immutable', nullable: false)]
    private DateTimeImmutable $createdAt;

    /**
     * @var array<string, mixed>|null
     */
    private array|null $payloadComputed = null;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        AuditableInterface $subject,
        string $name,
        array $payload = [],
        DateTimeImmutable $createdAt = null,
    ) {
        $this->setSubject($subject);

        $this->name = $name;

        $this->relations = new ArrayCollection();
        foreach ($payload as $key => $value) {
            $this->setPayload($key, $value);
        }

        $this->createdAt = $createdAt ?: new DateTimeImmutable();
    }

    /**
     * @return Collection<int, AuditEventRelation>
     */
    public function getRelations(): Collection
    {
        return $this->relations;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        if ($this->payloadComputed === null) {
            $this->payloadComputed = $this->payload;
            /**
             * @var string             $var
             * @var AuditEventRelation $relation
             */
            foreach ($this->relations as $var => $relation) {
                $this->payloadComputed[$var] = $relation->getSubject();
            }
        }

        return $this->payloadComputed;
    }

    public function setPayload(string $name, mixed $value): void
    {
        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        } elseif ($value instanceof \UnitEnum) {
            $value = $value->name;
        } elseif ($value instanceof \DateTimeInterface) {
            $value = $value->format(\DATE_ATOM);
        }

        if (\is_object($value)) {
            if (isset($this->relations[$name])) {
                throw new \BadMethodCallException(
                    \sprintf('Unable to set payload relation "%s" as it is already defined.', $name),
                );
            }

            $this->relations[$name] = new AuditEventRelation($this, $name, $value);
        } else {
            if (\array_key_exists($name, $this->payload)) {
                throw new \BadMethodCallException(
                    \sprintf('Unable to set payload value "%s" as it is already defined.', $name),
                );
            }

            $this->payload[$name] = $value;
        }

        if ($this->payloadComputed !== null) {
            $this->payloadComputed[$name] = $value;
        }
    }

    public function unsetPayload(string $name): void
    {
        unset($this->relations[$name]);
        unset($this->payload[$name]);
    }

    public function getId(): int|null
    {
        return $this->id;
    }

    public function getSubject(): AuditableInterface
    {
        $subject = $this->subjectTask ?? null;

        if (!$subject instanceof AuditableInterface) {
            throw new InvalidArgumentException('Subject must be defined');
        }

        return $subject;
    }

    private function setSubject(AuditableInterface $object): void
    {
        switch (true) {
            case $object instanceof Task:
                $this->subjectTask = $object;
                break;

            default:
                throw new InvalidArgumentException('Object of class ' . $object::class . ' is not supported.');
        }
    }
}
