<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\ODM\MongoDB\Types\Type;

#[MongoDB\Document(collection: 'reviews')]
class Review
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: Type::INT)]
    private int $userId;

    #[MongoDB\Field(type: Type::STRING)]
    private string $username;

    #[MongoDB\Field(type: Type::STRING, nullable: true)]
    private ?string $userAvatar = null;

    #[MongoDB\Field(type: Type::INT)]
    private int $gameId;

    #[MongoDB\Field(type: Type::STRING)]
    private string $gameName;

    #[MongoDB\Field(type: Type::INT)]
    private int $rating;

    #[MongoDB\Field(type: Type::STRING)]
    private string $title;

    #[MongoDB\Field(type: Type::STRING)]
    private string $content;

    #[MongoDB\Field(type: Type::DATE)]
    private \DateTime $createdAt;

    #[MongoDB\Field(type: Type::DATE)]
    private \DateTime $updatedAt;

    #[MongoDB\Field(type: Type::INT)]
    private int $helpfulCount = 0;

    #[MongoDB\Field(type: Type::BOOL)]
    private bool $isRecommended;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getUserAvatar(): ?string
    {
        return $this->userAvatar;
    }

    public function setUserAvatar(?string $userAvatar): self
    {
        $this->userAvatar = $userAvatar;
        return $this;
    }

    public function getGameId(): int
    {
        return $this->gameId;
    }

    public function setGameId(int $gameId): self
    {
        $this->gameId = $gameId;
        return $this;
    }

    public function getGameName(): string
    {
        return $this->gameName;
    }

    public function setGameName(string $gameName): self
    {
        $this->gameName = $gameName;
        return $this;
    }

    public function getRating(): int
    {
        return $this->rating;
    }

    public function setRating(int $rating): self
    {
        $this->rating = $rating;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getHelpfulCount(): int
    {
        return $this->helpfulCount;
    }

    public function incrementHelpfulCount(): self
    {
        $this->helpfulCount++;
        return $this;
    }

    public function isRecommended(): bool
    {
        return $this->isRecommended;
    }

    public function setIsRecommended(bool $isRecommended): self
    {
        $this->isRecommended = $isRecommended;
        return $this;
    }
}
