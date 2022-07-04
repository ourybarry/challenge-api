<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private $email;

    #[ORM\Column(type: 'json')]
    private $roles = [];

    #[ORM\Column(type: 'string')]
    private $password;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: StorageItem::class)]
    private $storageItems;

    #[ORM\OneToMany(mappedBy: 'sharedWith', targetEntity: FileShare::class)]
    private $fileShares;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: FileShare::class)]
    private $sharedItems;

    #[ORM\Column(type: 'string', length: 16)]
    private $uuid;

    public function __construct()
    {
        $this->storageItems = new ArrayCollection();
        $this->fileShares = new ArrayCollection();
        $this->sharedItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection<int, StorageItem>
     */
    public function getStorageItems(): Collection
    {
        return $this->storageItems;
    }

    public function addStorageItem(StorageItem $storageItem): self
    {
        if (!$this->storageItems->contains($storageItem)) {
            $this->storageItems[] = $storageItem;
            $storageItem->setOwner($this);
        }

        return $this;
    }

    public function removeStorageItem(StorageItem $storageItem): self
    {
        if ($this->storageItems->removeElement($storageItem)) {
            // set the owning side to null (unless already changed)
            if ($storageItem->getOwner() === $this) {
                $storageItem->setOwner(null);
            }
        }

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
            $fileShare->setSharedWith($this);
        }

        return $this;
    }

    public function removeFileShare(FileShare $fileShare): self
    {
        if ($this->fileShares->removeElement($fileShare)) {
            // set the owning side to null (unless already changed)
            if ($fileShare->getSharedWith() === $this) {
                $fileShare->setSharedWith(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, FileShare>
     */
    public function getSharedItems(): Collection
    {
        return $this->sharedItems;
    }

    public function addSharedItem(FileShare $sharedItem): self
    {
        if (!$this->sharedItems->contains($sharedItem)) {
            $this->sharedItems[] = $sharedItem;
            $sharedItem->setOwner($this);
        }

        return $this;
    }

    public function removeSharedItem(FileShare $sharedItem): self
    {
        if ($this->sharedItems->removeElement($sharedItem)) {
            // set the owning side to null (unless already changed)
            if ($sharedItem->getOwner() === $this) {
                $sharedItem->setOwner(null);
            }
        }

        return $this;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }
}
