<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class UserMangaFavorite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: "favorites")]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "manga_id", referencedColumnName: "id", nullable: false)]
    private Manga $manga;

    public function __construct(User $user, Manga $manga)
    {
        $this->user = $user;
        $this->manga = $manga;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getManga(): Manga
    {
        return $this->manga;
    }
}
