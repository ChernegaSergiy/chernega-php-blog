<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'settings')]
class Setting
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $setting_name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $setting_value = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSettingName(): ?string
    {
        return $this->setting_name;
    }

    public function setSettingName(string $setting_name): static
    {
        $this->setting_name = $setting_name;
        return $this;
    }

    public function getSettingValue(): ?string
    {
        return $this->setting_value;
    }

    public function setSettingValue(?string $setting_value): static
    {
        $this->setting_value = $setting_value;
        return $this;
    }
}
