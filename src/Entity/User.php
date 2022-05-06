<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @UniqueEntity(fields={"username"}, message="There is already an account with this username")
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const MAX_LAST_MANGAS_READ = 5;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $username;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @ORM\Column(type="integer")
     */
    private $timeSpent = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $countMangasRead = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $countMangasDownload = 0;

    /**
     * @var Manga[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Manga")
     * @ORM\JoinTable(name="user_manga_read",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="cascade")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="manga_id", referencedColumnName="id", onDelete="cascade")}
     *      )
     */
    private $lastMangasRead;

    /**
     * @ORM\Column(type="integer")
     */
    private $points = 0;

    /**
     * @var Manga[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Manga")
     * @ORM\JoinTable(name="user_manga_favorite",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="manga_id", referencedColumnName="id")}
     *      )
     */
    private $favoriteMangas;

    /**
     * @ORM\Column(type="integer")
     */
    private $bonusPoints = 0;

    public function __construct()
    {
        $this->lastMangasRead = new ArrayCollection();
        $this->favoriteMangas = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string)$this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
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
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string)$this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getTimeSpent(): ?int
    {
        return $this->timeSpent;
    }

    public function setTimeSpent(int $timeSpent): self
    {
        $this->timeSpent = $timeSpent;

        return $this;
    }

    public function getCountMangasRead(): ?int
    {
        return $this->countMangasRead;
    }

    public function setCountMangasRead(int $countMangasRead): self
    {
        $this->countMangasRead = $countMangasRead;

        return $this;
    }

    public function incrementCountMangasRead(): self
    {
        $this->countMangasRead++;

        return $this;
    }

    public function getCountMangasDownload(): ?int
    {
        return $this->countMangasDownload;
    }

    public function setCountMangasDownload(int $countMangasDownload): self
    {
        $this->countMangasDownload = $countMangasDownload;

        return $this;
    }

    public function incrementCountMangasDownload(): self
    {
        $this->countMangasDownload++;

        return $this;
    }

    /**
     * @return Collection|Manga[]
     */
    public function getLastMangasRead(): Collection
    {
        return $this->lastMangasRead;
    }

    public function addLastMangasRead(Manga $lastMangasRead): self
    {
        if (!$this->lastMangasRead->contains($lastMangasRead)) {
            $this->lastMangasRead[] = $lastMangasRead;
        }

        if ($this->lastMangasRead->count() > self::MAX_LAST_MANGAS_READ) {
            // First = oldest
            // Last = newest
            $this->removeLastMangasRead($this->lastMangasRead->first());
        }

        return $this;
    }

    public function removeLastMangasRead(Manga $lastMangasRead): self
    {
        if ($this->lastMangasRead->contains($lastMangasRead)) {
            $this->lastMangasRead->removeElement($lastMangasRead);
        }

        return $this;
    }

    public function getPoints(): ?int
    {
        return $this->points;
    }

    public function setPoints(int $points): self
    {
        $this->points = $points;

        return $this;
    }

    /**
     * @return Collection|Manga[]
     */
    public function getFavoriteMangas(): Collection
    {
        return $this->favoriteMangas;
    }

    public function addFavoriteManga(Manga $favoriteManga): self
    {
        if (!$this->favoriteMangas->contains($favoriteManga)) {
            $this->favoriteMangas[] = $favoriteManga;
        }

        return $this;
    }

    public function removeFavoriteManga(Manga $favoriteManga): self
    {
        if ($this->favoriteMangas->contains($favoriteManga)) {
            $this->favoriteMangas->removeElement($favoriteManga);
        }

        return $this;
    }

    public function getBonusPoints(): ?int
    {
        return $this->bonusPoints;
    }

    public function setBonusPoints(int $bonusPoints): self
    {
        $this->bonusPoints = $bonusPoints;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }
}
