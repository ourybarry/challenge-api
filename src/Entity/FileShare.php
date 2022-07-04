<?php

namespace App\Entity;

use App\Repository\FileShareRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FileShareRepository::class)]
class FileShare
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: StorageItem::class, inversedBy: 'fileShares')]
    #[ORM\JoinColumn(nullable: false)]
    private $fileId;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'fileShares')]
    private $sharedWith;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'sharedItems')]
    #[ORM\JoinColumn(nullable: false)]
    private $owner;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFileId(): ?StorageItem
    {
        return $this->fileId;
    }

    public function setFileId(?StorageItem $fileId): self
    {
        $this->fileId = $fileId;

        return $this;
    }

    public function getSharedWith(): ?User
    {
        return $this->sharedWith;
    }

    public function setSharedWith(?User $sharedWith): self
    {
        $this->sharedWith = $sharedWith;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }
}
