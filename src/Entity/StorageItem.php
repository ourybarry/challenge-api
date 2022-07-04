<?php

namespace App\Entity;

use App\Repository\StorageItemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A StorageItem entity represents a folder or file
 */

#[ORM\Entity(repositoryClass: StorageItemRepository::class)]
class StorageItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $name;

    #[ORM\Column(type: 'string', length: 12)]
    private $itemType;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $mimeType;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    private $parentDirectory;

    #[ORM\OneToMany(mappedBy: 'parentDirectory', targetEntity: self::class)]
    private $children;

    #[ORM\Column(type: 'datetime_immutable')]
    private $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private $updatedAt;

    #[ORM\Column(type: 'string', length: 64)]
    private $fileId;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'storageItems')]
    private $owner;

    #[ORM\Column(type: 'string', length: 32)]
    private $status;

    #[ORM\Column(type: 'integer')]
    private $size;

    #[ORM\OneToMany(mappedBy: 'fileId', targetEntity: FileShare::class)]
    private $fileShares;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $deleted;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->fileShares = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getItemType(): ?string
    {
        return $this->itemType;
    }

    public function setItemType(string $itemType): self
    {
        $this->itemType = $itemType;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(string | null $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getParentDirectory(): ?self
    {
        return $this->parentDirectory;
    }

    public function setParentDirectory(?self $parentDirectory): self
    {
        $this->parentDirectory = $parentDirectory;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(self $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children[] = $child;
            $child->setParentDirectory($this);
        }

        return $this;
    }

    public function removeChild(self $child): self
    {
        if ($this->children->removeElement($child)) {
            // set the owning side to null (unless already changed)
            if ($child->getParentDirectory() === $this) {
                $child->setParentDirectory(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getFileId(): ?string
    {
        return $this->fileId;
    }

    public function setFileId(string $fileId): self
    {
        $this->fileId = $fileId;

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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    /**
     * @return Collection<int, FileShare>
     */
    public function getFileShares(): Collection
    {
        return $this->fileShares;
    }

    public function addFileShare(FileShare $fileShare): self
    {
        if (!$this->fileShares->contains($fileShare)) {
            $this->fileShares[] = $fileShare;
            $fileShare->setFileId($this);
        }

        return $this;
    }

    public function removeFileShare(FileShare $fileShare): self
    {
        if ($this->fileShares->removeElement($fileShare)) {
            // set the owning side to null (unless already changed)
            if ($fileShare->getFileId() === $this) {
                $fileShare->setFileId(null);
            }
        }

        return $this;
    }

    public function isDeleted(): ?bool
    {
        return $this->deleted;
    }

    public function setDeleted(?bool $deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }
}
