<?php

namespace Infrastructure\GraphQL\Type;

use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\SourceField;
use Domain\Transcription\Entity\Transcription;

/**
 * Type GraphQL pour Transcription
 * 
 * @Type(class=Transcription::class)
 * @SourceField(name="id", outputType="ID!")
 * @SourceField(name="status")
 * @SourceField(name="createdAt")
 * @SourceField(name="updatedAt")
 */
class TranscriptionType
{
    /**
     * @Field
     */
    public function getId(Transcription $transcription): string
    {
        return $transcription->id()->value();
    }
    
    /**
     * @Field
     */
    public function getStatus(Transcription $transcription): string
    {
        return $transcription->status()->value();
    }
    
    /**
     * @Field
     */
    public function getLanguage(Transcription $transcription): LanguageType
    {
        return new LanguageType(
            $transcription->language()->code(),
            $transcription->language()->name()
        );
    }
    
    /**
     * @Field
     */
    public function getText(Transcription $transcription): ?string
    {
        if (!$transcription->status()->isCompleted()) {
            return null;
        }
        
        return $transcription->transcribedText()->value();
    }
    
    /**
     * @Field
     */
    public function getCost(Transcription $transcription): ?CostType
    {
        $cost = $transcription->cost();
        if (!$cost) {
            return null;
        }
        
        return new CostType(
            $cost->amount(),
            $cost->currency()
        );
    }
    
    /**
     * @Field
     */
    public function getYoutube(Transcription $transcription): ?YouTubeMetadataType
    {
        $metadata = $transcription->youtubeMetadata();
        if (!$metadata) {
            return null;
        }
        
        return new YouTubeMetadataType(
            $metadata->title(),
            $metadata->videoId(),
            $metadata->duration()
        );
    }
    
    /**
     * @Field
     */
    public function getCreatedAt(Transcription $transcription): \DateTimeInterface
    {
        return $transcription->createdAt();
    }
    
    /**
     * @Field
     */
    public function getUpdatedAt(Transcription $transcription): \DateTimeInterface
    {
        return $transcription->updatedAt();
    }
    
    /**
     * @Field
     */
    public function getProcessingProgress(Transcription $transcription): ?int
    {
        if ($transcription->status()->isPending()) {
            return 0;
        } elseif ($transcription->status()->isProcessing()) {
            return 50; // Pourrait être dynamique
        } elseif ($transcription->status()->isCompleted()) {
            return 100;
        }
        
        return null;
    }
}

/**
 * @Type
 */
class LanguageType
{
    public function __construct(
        private string $code,
        private string $name
    ) {}
    
    /**
     * @Field
     */
    public function getCode(): string
    {
        return $this->code;
    }
    
    /**
     * @Field
     */
    public function getName(): string
    {
        return $this->name;
    }
}

/**
 * @Type
 */
class CostType
{
    public function __construct(
        private float $amount,
        private string $currency
    ) {}
    
    /**
     * @Field
     */
    public function getAmount(): float
    {
        return $this->amount;
    }
    
    /**
     * @Field
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }
    
    /**
     * @Field
     */
    public function getFormatted(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }
}

/**
 * @Type
 */
class YouTubeMetadataType
{
    public function __construct(
        private string $title,
        private string $videoId,
        private int $duration
    ) {}
    
    /**
     * @Field
     */
    public function getTitle(): string
    {
        return $this->title;
    }
    
    /**
     * @Field
     */
    public function getVideoId(): string
    {
        return $this->videoId;
    }
    
    /**
     * @Field
     */
    public function getDuration(): int
    {
        return $this->duration;
    }
    
    /**
     * @Field
     */
    public function getUrl(): string
    {
        return "https://www.youtube.com/watch?v={$this->videoId}";
    }
    
    /**
     * @Field
     */
    public function getFormattedDuration(): string
    {
        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;
        return sprintf('%02d:%02d', $minutes, $seconds);
    }
}