<?php

namespace App\Entity;

use App\Repository\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TagRepository::class)]
#[ORM\Table(name: "tag")]
class Tag implements \JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(type: "string", unique: true, length: 190)]
    private string $name;

    /** @var Manga[]|ArrayCollection */
    #[ORM\ManyToMany(targetEntity: "Manga", mappedBy: "tags")]
    private $mangas;

    public function __construct()
    {
        $this->mangas = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function addManga(Manga ...$mangas): void
    {
        foreach ($mangas as $manga) {
            if (!$this->mangas->contains($manga)) {
                $this->mangas->add($mangas);
            }
        }
    }

    public function getMangas(): Collection
    {
        return $this->mangas;
    }

    public function removeManga(Manga $manga): self
    {
        if ($this->mangas->contains($manga)) {
            $this->mangas->removeElement($manga);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): string
    {
        // This entity implements JsonSerializable (http://php.net/manual/en/class.jsonserializable.php)
        // so this method is used to customize its JSON representation when json_encode()
        // is called, for example in tags|json_encode (app/Resources/views/form/fields.html.twig)

        return $this->name;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
