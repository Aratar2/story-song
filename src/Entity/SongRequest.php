<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'song_requests')]
class SongRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private string $contact;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $occasion = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tone = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $story = null;

    #[ORM\Column(type: 'boolean')]
    private bool $storyLater;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, TelegramDispatch>
     */
    #[ORM\OneToMany(mappedBy: 'request', targetEntity: TelegramDispatch::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $dispatches;

    public function __construct(
        ?string $name,
        string $contact,
        ?string $occasion,
        ?string $tone,
        ?string $story,
        bool $storyLater,
        ?\DateTimeImmutable $createdAt = null
    ) {
        $this->name = $name;
        $this->contact = $contact;
        $this->occasion = $occasion;
        $this->tone = $tone;
        $this->story = $story;
        $this->storyLater = $storyLater;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
        $this->dispatches = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getContact(): string
    {
        return $this->contact;
    }

    public function setContact(string $contact): void
    {
        $this->contact = $contact;
    }

    public function getOccasion(): ?string
    {
        return $this->occasion;
    }

    public function setOccasion(?string $occasion): void
    {
        $this->occasion = $occasion;
    }

    public function getTone(): ?string
    {
        return $this->tone;
    }

    public function setTone(?string $tone): void
    {
        $this->tone = $tone;
    }

    public function getStory(): ?string
    {
        return $this->story;
    }

    public function setStory(?string $story): void
    {
        $this->story = $story;
    }

    public function shouldTellStoryLater(): bool
    {
        return $this->storyLater;
    }

    public function isStoryLater(): bool
    {
        return $this->storyLater;
    }

    public function setStoryLater(bool $storyLater): void
    {
        $this->storyLater = $storyLater;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, TelegramDispatch>
     */
    public function getDispatches(): Collection
    {
        return $this->dispatches;
    }

    public function hasBeenDispatched(): bool
    {
        return !$this->dispatches->isEmpty();
    }

    public function addDispatch(TelegramDispatch $dispatch): void
    {
        if (!$this->dispatches->contains($dispatch)) {
            $this->dispatches->add($dispatch);
            $dispatch->setRequest($this);
        }
    }
}
