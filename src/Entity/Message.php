<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\HasLifecycleCallbacks()]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\OneToOne(mappedBy: 'lastMessage', targetEntity: Conversation::class, cascade: ['persist', 'remove'])]
    private $lastMessageId;

    #[ORM\ManyToOne(targetEntity: Conversation::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    private $conversation;

    #[ORM\Column(type: 'text')]
    private $content;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    private $user;

    #[ORM\Column(type: 'datetime')]
    private $createdAt;

    private $mine;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLastMessageId(): ?Conversation
    {
        return $this->lastMessageId;
    }

    public function setLastMessageId(Conversation $lastMessageId): self
    {
        // set the owning side of the relation if necessary
        if ($lastMessageId->getLastMessage() !== $this) {
            $lastMessageId->setLastMessage($this);
        }

        $this->lastMessageId = $lastMessageId;

        return $this;
    }

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function setConversation(?Conversation $conversation): self
    {
        $this->conversation = $conversation;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Set the value of createdAt
     *
     * @return  self
     */ 
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    #[ORM\PrePersist()]
    public function prePersist()
    {
        $this->createdAt = new \Datetime();
    }

    /**
     * Get the value of mine
     */ 
    public function getMine()
    {
        return $this->mine;
    }

    /**
     * Set the value of mine
     *
     * @return  self
     */ 
    public function setMine($mine)
    {
        $this->mine = $mine;

        return $this;
    }
}
