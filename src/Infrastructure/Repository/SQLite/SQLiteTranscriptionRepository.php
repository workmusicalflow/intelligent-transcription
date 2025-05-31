<?php

namespace Infrastructure\Repository\SQLite;

use Domain\Transcription\Repository\TranscriptionRepository;
use Domain\Transcription\Entity\Transcription;
use Domain\Transcription\ValueObject\TranscriptionId;
use Domain\Transcription\Collection\TranscriptionCollection;
use Domain\Common\ValueObject\UserId;
use Domain\Common\Specification\SpecificationInterface;
use Domain\Transcription\Repository\Criteria\TranscriptionSearchCriteria;
use Domain\Common\Specification\Specification;
use Domain\Transcription\ValueObject\AudioFile;
use Domain\Transcription\ValueObject\Language;
use Domain\Transcription\ValueObject\TranscriptionStatus;
use Domain\Transcription\ValueObject\TranscribedText;
use Domain\Transcription\ValueObject\YouTubeMetadata;
use Domain\Common\ValueObject\Money;
use Infrastructure\Persistence\SQLiteConnection;
use PDO;
use Exception;

/**
 * Implémentation SQLite du Repository Transcription
 */
class SQLiteTranscriptionRepository implements TranscriptionRepository
{
    private PDO $connection;
    
    public function __construct(SQLiteConnection $connection)
    {
        $this->connection = $connection->getConnection();
    }
    
    public function save(Transcription $transcription): void
    {
        $sql = $this->exists($transcription->transcriptionId()) ? $this->getUpdateQuery() : $this->getInsertQuery();
        
        $stmt = $this->connection->prepare($sql);
        $this->bindTranscriptionData($stmt, $transcription);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to save transcription: " . implode(', ', $stmt->errorInfo()));
        }
    }
    
    public function findById(TranscriptionId $id): ?Transcription
    {
        $sql = "SELECT * FROM transcriptions WHERE id = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$id->value()]);
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? $this->hydrate($data) : null;
    }
    
    public function findByUser(UserId $userId): TranscriptionCollection
    {
        $sql = "SELECT * FROM transcriptions WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$userId->value()]);
        
        $transcriptions = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $transcriptions[] = $this->hydrate($data);
        }
        
        return new TranscriptionCollection($transcriptions);
    }
    
    public function findAll(): TranscriptionCollection
    {
        $sql = "SELECT * FROM transcriptions ORDER BY created_at DESC";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
        
        $transcriptions = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $transcriptions[] = $this->hydrate($data);
        }
        
        return new TranscriptionCollection($transcriptions);
    }
    
    public function delete(TranscriptionId $id): void
    {
        $sql = "DELETE FROM transcriptions WHERE id = ?";
        $stmt = $this->connection->prepare($sql);
        
        if (!$stmt->execute([$id->value()])) {
            throw new Exception("Failed to delete transcription: " . implode(', ', $stmt->errorInfo()));
        }
    }
    
    public function exists(TranscriptionId $id): bool
    {
        $sql = "SELECT COUNT(*) FROM transcriptions WHERE id = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$id->value()]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    public function count(): int
    {
        $sql = "SELECT COUNT(*) FROM transcriptions";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
        
        return (int) $stmt->fetchColumn();
    }
    
    public function findByUserPaginated(UserId $userId, int $page = 1, int $perPage = 10): TranscriptionCollection
    {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT * FROM transcriptions WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$userId->value(), $perPage, $offset]);
        
        $transcriptions = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $transcriptions[] = $this->hydrate($data);
        }
        
        return new TranscriptionCollection($transcriptions);
    }
    
    public function findByStatus(TranscriptionStatus $status): TranscriptionCollection
    {
        $sql = "SELECT * FROM transcriptions WHERE status = ? ORDER BY created_at DESC";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$status->value()]);
        
        $transcriptions = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $transcriptions[] = $this->hydrate($data);
        }
        
        return new TranscriptionCollection($transcriptions);
    }
    
    public function findByUserAndStatus(UserId $userId, TranscriptionStatus $status): TranscriptionCollection
    {
        $sql = "SELECT * FROM transcriptions WHERE user_id = ? AND status = ? ORDER BY created_at DESC";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$userId->value(), $status->value()]);
        
        $transcriptions = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $transcriptions[] = $this->hydrate($data);
        }
        
        return new TranscriptionCollection($transcriptions);
    }
    
    public function countByUser(UserId $userId): int
    {
        $sql = "SELECT COUNT(*) FROM transcriptions WHERE user_id = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$userId->value()]);
        
        return (int) $stmt->fetchColumn();
    }
    
    public function countByUserAndStatus(UserId $userId, TranscriptionStatus $status): int
    {
        $sql = "SELECT COUNT(*) FROM transcriptions WHERE user_id = ? AND status = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$userId->value(), $status->value()]);
        
        return (int) $stmt->fetchColumn();
    }
    
    public function findRecentByUser(UserId $userId, int $limit = 10): TranscriptionCollection
    {
        $sql = "SELECT * FROM transcriptions WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$userId->value(), $limit]);
        
        $transcriptions = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $transcriptions[] = $this->hydrate($data);
        }
        
        return new TranscriptionCollection($transcriptions);
    }
    
    public function findYouTubeTranscriptionsByUser(UserId $userId): TranscriptionCollection
    {
        $sql = "SELECT * FROM transcriptions WHERE user_id = ? AND youtube_url IS NOT NULL ORDER BY created_at DESC";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$userId->value()]);
        
        $transcriptions = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $transcriptions[] = $this->hydrate($data);
        }
        
        return new TranscriptionCollection($transcriptions);
    }
    
    public function nextIdentity(): TranscriptionId
    {
        return TranscriptionId::generate();
    }
    
    public function search(TranscriptionSearchCriteria $criteria): TranscriptionCollection
    {
        // Implémentation simplifiée - dans une vraie application, 
        // on construirait la requête dynamiquement selon les critères
        $sql = "SELECT * FROM transcriptions WHERE 1=1";
        $params = [];
        
        // Ajoutez ici la logique pour construire la requête selon les critères
        
        $sql .= " ORDER BY created_at DESC";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        
        $transcriptions = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $transcriptions[] = $this->hydrate($data);
        }
        
        return new TranscriptionCollection($transcriptions);
    }
    
    public function findBySpecification(Specification $specification): TranscriptionCollection
    {
        // Pour cette implémentation, on va faire une requête générale et filtrer en mémoire
        // Dans une vraie application, on convertirait la Specification en SQL
        $sql = "SELECT * FROM transcriptions ORDER BY created_at DESC";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
        
        $transcriptions = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $transcription = $this->hydrate($data);
            if ($specification->isSatisfiedBy($transcription)) {
                $transcriptions[] = $transcription;
            }
        }
        
        return new TranscriptionCollection($transcriptions);
    }
    
    private function getInsertQuery(): string
    {
        return "
            INSERT INTO transcriptions (
                id, user_id, original_filename, file_path, file_size, duration,
                language, status, transcribed_text, cost_amount, cost_currency,
                youtube_url, youtube_title, youtube_video_id, youtube_duration,
                created_at, updated_at, processing_started_at, processing_completed_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
    }
    
    private function getUpdateQuery(): string
    {
        return "
            UPDATE transcriptions SET
                user_id = ?, original_filename = ?, file_path = ?, file_size = ?, 
                duration = ?, language = ?, status = ?, transcribed_text = ?,
                cost_amount = ?, cost_currency = ?, youtube_url = ?, youtube_title = ?,
                youtube_video_id = ?, youtube_duration = ?, updated_at = ?,
                processing_started_at = ?, processing_completed_at = ?
            WHERE id = ?
        ";
    }
    
    private function bindTranscriptionData(\PDOStatement $stmt, Transcription $transcription): void
    {
        $audioFile = $transcription->audioFile();
        $status = $transcription->status();
        $cost = $transcription->cost();
        $youtubeMetadata = $transcription->youtubeMetadata();
        $transcribedText = $transcription->transcribedText();
        
        $params = [
            $transcription->id(),
            $transcription->userId()->value(),
            $audioFile->originalName(),
            $audioFile->path(),
            $audioFile->size(),
            $audioFile->duration(),
            $transcription->language()->code(),
            $status->value(),
            $transcribedText,
            $cost['amount'] ?? null,
            $cost['currency'] ?? 'USD',
            $youtubeMetadata?->originalUrl(),
            $youtubeMetadata?->title(),
            $youtubeMetadata?->videoId(),
            $youtubeMetadata?->duration(),
            date('Y-m-d H:i:s'),
            $status->isProcessing() ? date('Y-m-d H:i:s') : null,
            $status->isCompleted() ? date('Y-m-d H:i:s') : null
        ];
        
        // Pour l'update, on place l'ID à la fin
        if (str_contains($stmt->queryString, 'UPDATE')) {
            $id = array_shift($params);
            $params[] = $id;
        }
        
        foreach ($params as $index => $value) {
            $stmt->bindValue($index + 1, $value);
        }
    }
    
    private function hydrate(array $data): Transcription
    {
        // Créer les Value Objects
        $transcriptionId = TranscriptionId::fromString($data['id']);
        $userId = UserId::fromString($data['user_id']);
        
        $audioFile = AudioFile::create(
            $data['file_path'],
            $data['original_filename'],
            'audio/mpeg', // Par défaut, à améliorer
            (int) $data['file_size'],
            (float) $data['duration']
        );
        
        $language = Language::fromCode($data['language']);
        $status = TranscriptionStatus::fromString($data['status']);
        
        $youtubeMetadata = null;
        if ($data['youtube_url']) {
            $youtubeMetadata = YouTubeMetadata::create(
                $data['youtube_url'],
                $data['youtube_title'] ?? '',
                $data['youtube_video_id'] ?? '',
                (float) ($data['youtube_duration'] ?? 0)
            );
        }
        
        // Créer la transcription
        $transcription = Transcription::create(
            $transcriptionId,
            $userId,
            $audioFile,
            $language,
            $status,
            null, // text - will be set below if present
            $youtubeMetadata
        );
        
        // Restaurer le texte transcrit si présent
        if (!empty($data['transcribed_text'])) {
            $transcribedText = TranscribedText::fromContent($data['transcribed_text']);
            $this->setPrivateProperty($transcription, 'text', $transcribedText);
        }
        
        // Restaurer le coût estimé
        if ($data['cost_amount'] && $data['cost_currency']) {
            $costData = [
                'amount' => (float) $data['cost_amount'],
                'currency' => $data['cost_currency']
            ];
            $this->setPrivateProperty($transcription, 'metadata', ['cost' => $costData]);
        }
        
        return $transcription;
    }
    
    private function setPrivateProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new \ReflectionClass($object);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue($object, $value);
    }
}