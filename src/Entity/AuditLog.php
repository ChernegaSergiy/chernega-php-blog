<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'audit_logs')]
class AuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Admin::class)]
    #[ORM\JoinColumn(name: 'admin_id', referencedColumnName: 'id', nullable: true)]
    private ?Admin $admin = null;

    #[ORM\Column(length: 255)]
    private ?string $action = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $entity_type = null;

    #[ORM\Column(nullable: true)]
    private ?int $entity_id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $metadata = null;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ip_address = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTimeInterface $created_at;

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getAdmin(): ?Admin { return $this->admin; }
    public function setAdmin(?Admin $admin): static { $this->admin = $admin; return $this; }
    public function getAction(): ?string { return $this->action; }
    public function setAction(string $action): static { $this->action = $action; return $this; }
    public function getEntityType(): ?string { return $this->entity_type; }
    public function setEntityType(?string $entity_type): static { $this->entity_type = $entity_type; return $this; }
    public function getEntityId(): ?int { return $this->entity_id; }
    public function setEntityId(?int $entity_id): static { $this->entity_id = $entity_id; return $this; }
    public function getMetadata(): ?string { return $this->metadata; }
    public function setMetadata(?string $metadata): static { $this->metadata = $metadata; return $this; }
    public function getIpAddress(): ?string { return $this->ip_address; }
    public function setIpAddress(?string $ip_address): static { $this->ip_address = $ip_address; return $this; }
    public function getCreatedAt(): \DateTimeInterface { return $this->created_at; }
    public function setCreatedAt(\DateTimeInterface $created_at): static { $this->created_at = $created_at; return $this; }
}
