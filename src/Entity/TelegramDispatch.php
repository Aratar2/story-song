<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'telegram_dispatches')]
class TelegramDispatch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: SongRequest::class, inversedBy: 'dispatches')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private SongRequest $request;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $dispatchedAt;

    public function __construct(SongRequest $request)
    {
        $this->request = $request;
        $this->dispatchedAt = new \DateTimeImmutable();
        $request->addDispatch($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRequest(): SongRequest
    {
        return $this->request;
    }

    public function setRequest(SongRequest $request): void
    {
        $this->request = $request;
    }

    public function getDispatchedAt(): \DateTimeImmutable
    {
        return $this->dispatchedAt;
    }
}
