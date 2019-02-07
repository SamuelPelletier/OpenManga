<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="author")
 *
 * Defines the properties of the Author entity to represent the blog authors.
 * See https://symfony.com/doc/current/book/doctrine.html#creating-an-entity-class
 *
 * Tip: if you have an existing database, you can generate these entity class automatically.
 * See https://symfony.com/doc/current/cookbook/doctrine/reverse_engineering.html
 *
 */
class Author
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $name;

    /**
     * @var Manga[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Manga", mappedBy="manga")
     * @ORM\JoinTable(name="manga")
     * @ORM\OrderBy({"name": "ASC"})
     */
    private $mangas;

    public function __construct()
    {
        $this->mangas = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function addManga(Manga ...$mangas): void
    {
        foreach ($mangas as $manga) {
            if (!$this->mangas->contains($manga)) {
                $this->mangas->add($manga);
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
            // set the owning side to null (unless already changed)
            if ($manga->getManga() === $this) {
                $manga->setManga(null);
            }
        }

        return $this;
    }
}
