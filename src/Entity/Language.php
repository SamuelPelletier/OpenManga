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
use ApiPlatform\Metadata\ApiResource;
#[ApiResource]
/**
 * @ORM\Entity
 * @ORM\Table(name="language")
 *
 * Defines the properties of the Author entity to represent the blog authors.
 * See https://symfony.com/doc/current/book/doctrine.html#creating-an-entity-class
 *
 * Tip: if you have an existing database, you can generate these entity class automatically.
 * See https://symfony.com/doc/current/cookbook/doctrine/reverse_engineering.html
 *
 */
class Language implements \JsonSerializable
{
    const ISO_CODE = [
        'english' => 'gb',
        'french' => 'fr',
        'spanish' => 'es',
        'german' => 'de',
        'chinese' => 'cn',
        'japanese' => 'jp',
        'arabic' => 'ae',
        'russian' => 'ru',
        'portuguese' => 'pt',
        'italian' => 'it',
        'dutch' => 'nl',
        'korean' => 'kp',
        'turkish' => 'tr',
        'polish' => 'pl',
        'swedish' => 'se',
        'norwegian' => 'no',
        'danish' => 'da',
        'finnish' => 'fi',
        'greek' => 'gr',
        'hindi' => 'in',
        'indonesian' => 'id',
        'malay' => 'ms',
        'thai' => 'th',
        'vietnamese' => 'vi',
        'ukrainian' => 'uk',
        'hungarian' => 'hu',
        'czech' => 'cs',
        'romanian' => 'ro',
        'hebrew' => 'he',
        'persian' => 'fa',
        'bengali' => 'bn',
        'urdu' => 'ur',
        'tagalog' => 'tl',
        'malayalam' => 'ml',
        // Add other languages here.
    ];

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
     * @ORM\Column(type="string", unique=true, length=190)
     */
    private $name;

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

    public function getCode(): ?string
    {
        return self::ISO_CODE[$this->name] ?? null;
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
