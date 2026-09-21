<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'posts')]
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column(length: 255)]
    private ?string $category = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ["default" => "CURRENT_TIMESTAMP"])]
    private \DateTimeInterface $updated_at;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ["default" => "CURRENT_TIMESTAMP"])]
    private \DateTimeInterface $created_at;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $article_image = null;

    #[ORM\Column(length: 255, nullable: true, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $meta_title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $meta_description = null;

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
        $this->updated_at = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }
    public function getContent(): ?string { return $this->content; }
    public function setContent(string $content): static { $this->content = $content; return $this; }
    public function getCategory(): ?string { return $this->category; }
    public function setCategory(string $category): static { $this->category = $category; return $this; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updated_at; }
    public function setUpdatedAt(\DateTimeInterface $updated_at): static { $this->updated_at = $updated_at; return $this; }
    public function getCreatedAt(): \DateTimeInterface { return $this->created_at; }
    public function setCreatedAt(\DateTimeInterface $created_at): static { $this->created_at = $created_at; return $this; }
    public function getArticleImage(): ?string { return $this->article_image; }
    public function setArticleImage(?string $article_image): static { $this->article_image = $article_image; return $this; }
    public function getSlug(): ?string { return $this->slug; }
    public function setSlug(?string $slug): static { $this->slug = $slug; return $this; }
    public function getMetaTitle(): ?string { return $this->meta_title; }
    public function setMetaTitle(?string $meta_title): static { $this->meta_title = $meta_title; return $this; }
    public function getMetaDescription(): ?string { return $this->meta_description; }
    public function setMetaDescription(?string $meta_description): static { $this->meta_description = $meta_description; return $this; }
}
