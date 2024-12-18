<?php

namespace App\Entity;

use App\Repository\LanguageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LanguageRepository::class)]
#[ORM\Table(name: "language")]
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

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(type: "string", unique: true, length: 190)]
    private string $name;

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
