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
 * @ORM\Entity(repositoryClass="App\Repository\MangaRepository")
 * @ORM\Table(name="manga")
 *
 * Defines the properties of the Manga entity to represent the blog mangas.
 *
 * See https://symfony.com/doc/current/book/doctrine.html#creating-an-entity-class
 *
 * Tip: if you have an existing database, you can generate these entity class automatically.
 * See https://symfony.com/doc/current/cookbook/doctrine/reverse_engineering.html
 *
 */
class Manga
{
    /**
     * Use constants to define configuration options that rarely change instead
     * of specifying them under parameters section in config/services.yaml file.
     *
     * See https://symfony.com/doc/current/best_practices/configuration.html#constants-vs-configuration-options
     */
    public const NUM_ITEMS = 20;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(type="integer",nullable=true, unique=true)
     */
    private $externalId;

    /**
     * @var string
     *
     * @ORM\Column(type="string",nullable=true)
     */
    private $externalToken;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     */
    private $title;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $countPages;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $publishedAt;

    /**
     * @var Author[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Author")
     * @ORM\JoinTable(name="mangas_authors",
     *      joinColumns={@ORM\JoinColumn(name="manga_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="author_id", referencedColumnName="id")}
     *      )
     */
    private $authors;


    /**
     * @var Tag[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Tag", inversedBy="mangas")
     * @ORM\JoinTable(name="mangas_tags",
     *      joinColumns={@ORM\JoinColumn(name="manga_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="tag_id", referencedColumnName="id")}
     *      )
     */
    private $tags;

    /**
     * @var Language[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Language")
     * @ORM\JoinTable(name="mangas_languages",
     *      joinColumns={@ORM\JoinColumn(name="manga_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="language_id", referencedColumnName="id")}
     *      )
     */
    private $languages;

    /**
     * @var Parody[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Parody")
     * @ORM\JoinTable(name="mangas_parodies",
     *      joinColumns={@ORM\JoinColumn(name="manga_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="language_id", referencedColumnName="id")}
     *      )
     */
    private $parodies;

    /**
     * @ORM\Column(type="integer")
     */
    private $countViews = 0;

    /**
     * @ORM\Column(type="boolean", options={"default" : 0})
     */
    private bool $isOld = false;

    /**
     * @ORM\Column(type="boolean", options={"default" : 0})
     */
    private bool $isBlocked = false;

    /**
     * @ORM\Column(type="boolean", options={"default" : 0})
     */
    private bool $isCorrupted = false;

    public function __construct()
    {
        $this->publishedAt = new \DateTime();
        $this->tags = new ArrayCollection();
        $this->authors = new ArrayCollection();
        $this->languages = new ArrayCollection();
        $this->parodies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return ?int
     */
    public function getExternalId(): ?int
    {
        return $this->externalId;
    }

    /**
     * @param int $externalId
     */
    public function setExternalId(int $externalId): void
    {
        $this->externalId = $externalId;
    }

    /**
     * @return ?string
     */
    public function getExternalToken(): ?string
    {
        return $this->externalToken;
    }

    /**
     * @param string $externalToken
     */
    public function setExternalToken(string $externalToken): void
    {
        $this->externalToken = $externalToken;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getCountPages(): ?int
    {
        return $this->countPages;
    }

    public function setCountPages(int $countPages): void
    {
        $this->countPages = $countPages;
    }

    public function getPublishedAt(): \DateTime
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(\DateTime $publishedAt): void
    {
        $this->publishedAt = $publishedAt;
    }

    public function addAuthor(Author ...$authors): void
    {
        foreach ($authors as $author) {
            if (!$this->authors->contains($author)) {
                $this->authors->add($author);
            }
        }
    }

    public function getAuthors(): Collection
    {
        return $this->authors;
    }

    public function removeAuthor(Author $author): self
    {
        if ($this->authors->contains($author)) {
            $this->authors->removeElement($author);
        }

        return $this;
    }

    public function addTag(Tag ...$tags): void
    {
        foreach ($tags as $tag) {
            if (!$this->tags->contains($tag)) {
                $this->tags->add($tag);
            }
        }
    }

    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function removeTag(Tag $tag): self
    {
        if ($this->tags->contains($tag)) {
            $this->tags->removeElement($tag);
        }

        return $this;
    }

    public function addLanguage(Language ...$languages): void
    {
        foreach ($languages as $language) {
            if (!$this->languages->contains($language)) {
                $this->languages->add($language);
            }
        }
    }

    public function getLanguages(): Collection
    {
        return $this->languages;
    }

    public function removeLanguage(Language $language): self
    {
        if ($this->languages->contains($language)) {
            $this->languages->removeElement($language);
        }

        return $this;
    }

    public function addParody(Parody ...$parodies): void
    {
        foreach ($parodies as $parody) {
            if (!$this->parodies->contains($parody)) {
                $this->parodies->add($parody);
            }
        }
    }

    public function getParodies(): Collection
    {
        return $this->parodies;
    }

    public function removeParody(Parody $parody): self
    {
        if ($this->parodies->contains($parody)) {
            $this->parodies->removeElement($parody);
        }

        return $this;
    }

    public function getCountViews(): ?int
    {
        return $this->countViews;
    }

    public function setCountViews(int $countViews): self
    {
        $this->countViews = $countViews;

        return $this;
    }

    /**
     * @return bool
     */
    public function isOld(): bool
    {
        return $this->isOld;
    }

    /**
     * @param bool $isOld
     */
    public function setIsOld(bool $isOld): void
    {
        $this->isOld = $isOld;
    }

    /**
     * @return bool
     */
    public function isBlocked(): bool
    {
        return $this->isBlocked;
    }

    /**
     * @param bool $isBlocked
     */
    public function setIsBlocked(bool $isBlocked): void
    {
        $this->isBlocked = $isBlocked;
    }

    /**
     * @return bool
     */
    public function isCorrupted(): bool
    {
        return $this->isCorrupted;
    }

    /**
     * @param bool $isCorrupted
     */
    public function setIsCorrupted(bool $isCorrupted): void
    {
        $this->isCorrupted = $isCorrupted;
    }

}
