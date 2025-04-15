<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "payment")]
class Payment implements \JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(type: "string")]
    private string $uuid;

    #[ORM\Column(type: "float")]
    private float $amount;

    #[ORM\Column(type: "string")]
    private string $currency;

    #[ORM\Column(type: "datetime")]
    private \DateTime $createdAt;

    #[ORM\ManyToOne(targetEntity: "User", inversedBy: "payments")]
    private User $user;

    #[ORM\Column(type: "string")]
    private string $target = "subscribe";

    #[ORM\ManyToOne(targetEntity: "Manga")]
    private ?Manga $manga;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @param mixed $uuid
     */
    public function setUuid($uuid): void
    {
        $this->uuid = $uuid;
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     */
    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt(\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user): void
    {
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * @param string $target
     */
    public function setTarget(string $target): void
    {
        $this->target = $target;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): string
    {
        // This entity implements JsonSerializable (http://php.net/manual/en/class.jsonserializable.php)
        // so this method is used to customize its JSON representation when json_encode()
        // is called, for example in tags|json_encode (app/Resources/views/form/fields.html.twig)

        return $this->uuid;
    }

    public function __toString(): string
    {
        return $this->uuid;
    }

    /**
     * @return Manga|null
     */
    public function getManga(): ?Manga
    {
        return $this->manga;
    }

    /**
     * @param Manga|null $manga
     */
    public function setManga(?Manga $manga): void
    {
        $this->manga = $manga;
    }
}
