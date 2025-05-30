# 🏗️ Guide d'Architecture Clean & SOLID

## 📋 Vue d'ensemble

Ce guide définit l'architecture cible du projet Intelligent Transcription, basée sur les principes Clean Architecture, SOLID et DDD (Domain-Driven Design).

**Note** : Ce document est une référence technique détaillée destinée à l'équipe de développement. Pour la roadmap MVP veuillez consulter le document `MVP_ROADMAP.md`.

## 🎯 Objectifs Architecturaux

1. **Découplage** : Indépendance entre domaine métier et détails techniques
2. **Testabilité** : Tests unitaires sans infrastructure
3. **Évolutivité** : Ajout de features sans impact sur l'existant
4. **Maintenabilité** : Code auto-documenté et prévisible
5. **Performance** : Optimisations ciblées par couche

## 📐 Structure du Projet

### Organisation des Dossiers

```
intelligent-transcription/
├── src/
│   ├── Domain/                 # Cœur métier pur
│   │   ├── Common/            # Shared Kernel
│   │   │   ├── ValueObject/
│   │   │   ├── Event/
│   │   │   └── Exception/
│   │   ├── Transcription/
│   │   │   ├── Entity/
│   │   │   ├── ValueObject/
│   │   │   ├── Repository/
│   │   │   ├── Service/
│   │   │   └── Event/
│   │   ├── Chat/
│   │   │   ├── Entity/
│   │   │   ├── ValueObject/
│   │   │   └── Repository/
│   │   └── Analytics/
│   │       ├── Entity/
│   │       └── Service/
│   │
│   ├── Application/           # Use Cases / Services Applicatifs
│   │   ├── Command/
│   │   ├── Query/
│   │   ├── DTO/
│   │   └── EventHandler/
│   │
│   └── Infrastructure/        # Détails techniques
│       ├── Persistence/       # Implémentations Repository
│       │   ├── Doctrine/
│       │   └── InMemory/
│       ├── API/              # Controllers & Resolvers
│       │   ├── REST/
│       │   ├── GraphQL/
│       │   └── WebSocket/
│       ├── External/         # Services externes
│       │   ├── OpenAI/
│       │   ├── Google/
│       │   └── Storage/
│       └── DependencyInjection/
│
├── public/                   # Point d'entrée web
├── bin/                      # Scripts CLI
├── config/                   # Configuration
├── tests/                    # Tests organisés par type
│   ├── Unit/
│   ├── Integration/
│   └── E2E/
└── docker/                   # Environnement Docker
```

## 🧩 Couches de l'Architecture

### 1. Domain Layer (Entités et Logique Métier)

#### Entités

```php
namespace Domain\Transcription\Entity;

use Domain\Common\Entity\AggregateRoot;
use Domain\Transcription\ValueObject\TranscriptionId;
use Domain\Transcription\ValueObject\AudioFile;
use Domain\Transcription\ValueObject\TranscribedText;
use Domain\Transcription\Event\TranscriptionCompleted;

final class Transcription extends AggregateRoot
{
    private TranscriptionStatus $status;
    private ?TranscribedText $text = null;
    private array $metadata = [];

    private function __construct(
        private TranscriptionId $id,
        private AudioFile $audioFile,
        private Language $language,
        private UserId $userId
    ) {
        $this->status = TranscriptionStatus::PENDING();
        $this->recordEvent(new TranscriptionCreated($id, $userId));
    }

    public static function create(
        AudioFile $audioFile,
        Language $language,
        UserId $userId
    ): self {
        return new self(
            TranscriptionId::generate(),
            $audioFile,
            $language,
            $userId
        );
    }

    public function complete(TranscribedText $text, array $metadata = []): void
    {
        if (!$this->status->isPending()) {
            throw new DomainException('Cannot complete non-pending transcription');
        }

        $this->text = $text;
        $this->metadata = $metadata;
        $this->status = TranscriptionStatus::COMPLETED();

        $this->recordEvent(new TranscriptionCompleted(
            $this->id,
            $text->wordCount(),
            $text->duration()
        ));
    }

    public function fail(string $reason): void
    {
        $this->status = TranscriptionStatus::FAILED();
        $this->recordEvent(new TranscriptionFailed($this->id, $reason));
    }
}
```

#### Value Objects

```php
namespace Domain\Transcription\ValueObject;

final class TranscribedText
{
    private string $content;
    private array $segments;

    public function __construct(string $content, array $segments = [])
    {
        if (empty(trim($content))) {
            throw new InvalidArgumentException('Transcribed text cannot be empty');
        }

        $this->content = $content;
        $this->segments = $segments;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function wordCount(): int
    {
        return str_word_count($this->content);
    }

    public function duration(): ?float
    {
        if (empty($this->segments)) {
            return null;
        }

        $lastSegment = end($this->segments);
        return $lastSegment['end'] ?? null;
    }

    public function excerpt(int $length = 100): string
    {
        if (strlen($this->content) <= $length) {
            return $this->content;
        }

        return substr($this->content, 0, $length) . '...';
    }
}
```

#### Domain Services

```php
namespace Domain\Transcription\Service;

interface TranscriptionPricingService
{
    public function calculatePrice(
        AudioFile $file,
        Language $language,
        bool $isPriority = false
    ): Money;
}

final class StandardPricingService implements TranscriptionPricingService
{
    private const BASE_RATE_PER_MINUTE = 0.006; // $0.006 per minute
    private const PRIORITY_MULTIPLIER = 2.5;

    public function calculatePrice(
        AudioFile $file,
        Language $language,
        bool $isPriority = false
    ): Money {
        $minutes = ceil($file->duration() / 60);
        $basePrice = $minutes * self::BASE_RATE_PER_MINUTE;

        if ($isPriority) {
            $basePrice *= self::PRIORITY_MULTIPLIER;
        }

        // Language complexity factor
        $languageFactor = $this->getLanguageFactor($language);
        $finalPrice = $basePrice * $languageFactor;

        return Money::USD($finalPrice);
    }

    private function getLanguageFactor(Language $language): float
    {
        return match($language->code()) {
            'en', 'es', 'fr' => 1.0,
            'zh', 'ja', 'ar' => 1.5,
            default => 1.2
        };
    }
}
```

### 2. Application Layer (Use Cases)

#### Commands & Handlers

```php
namespace Application\Transcription\Command;

final class TranscribeAudioCommand
{
    public function __construct(
        public readonly string $audioFilePath,
        public readonly string $userId,
        public readonly ?string $language = null,
        public readonly bool $priority = false,
        public readonly array $options = []
    ) {}
}

final class TranscribeAudioHandler
{
    public function __construct(
        private TranscriptionRepository $repository,
        private TranscriberInterface $transcriber,
        private FileStorage $storage,
        private EventBus $eventBus,
        private TranscriptionPricingService $pricingService
    ) {}

    public function handle(TranscribeAudioCommand $command): TranscriptionId
    {
        // 1. Validate and prepare audio file
        $audioFile = $this->storage->retrieve($command->audioFilePath);
        if (!$audioFile->isValid()) {
            throw new InvalidAudioFileException();
        }

        // 2. Detect or validate language
        $language = $command->language
            ? Language::fromCode($command->language)
            : $this->transcriber->detectLanguage($audioFile);

        // 3. Calculate pricing
        $price = $this->pricingService->calculatePrice(
            $audioFile,
            $language,
            $command->priority
        );

        // 4. Create transcription entity
        $transcription = Transcription::create(
            $audioFile,
            $language,
            UserId::fromString($command->userId)
        );

        // 5. Persist
        $this->repository->save($transcription);

        // 6. Dispatch for async processing
        $this->eventBus->dispatch(
            new ProcessTranscriptionCommand(
                $transcription->id()->toString(),
                $command->priority
            )
        );

        return $transcription->id();
    }
}
```

#### Queries & Handlers

```php
namespace Application\Transcription\Query;

final class GetTranscriptionQuery
{
    public function __construct(
        public readonly string $transcriptionId,
        public readonly string $userId
    ) {}
}

final class GetTranscriptionHandler
{
    public function __construct(
        private TranscriptionRepository $repository,
        private TranscriptionReadModel $readModel
    ) {}

    public function handle(GetTranscriptionQuery $query): ?TranscriptionDTO
    {
        $transcription = $this->repository->findById(
            TranscriptionId::fromString($query->transcriptionId)
        );

        if (!$transcription || !$transcription->belongsTo($query->userId)) {
            return null;
        }

        // Use read model for optimized queries
        return $this->readModel->getDetails($transcription->id());
    }
}
```

### 3. Infrastructure Layer

#### Repository Implementation

```php
namespace Infrastructure\Persistence\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Domain\Transcription\Repository\TranscriptionRepository;

final class DoctrineTranscriptionRepository implements TranscriptionRepository
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    public function save(Transcription $transcription): void
    {
        $this->em->persist($transcription);
        $this->em->flush();

        // Dispatch domain events
        foreach ($transcription->pullDomainEvents() as $event) {
            $this->em->getEventManager()->dispatchEvent(
                $event::class,
                new DomainEventArgs($event)
            );
        }
    }

    public function findById(TranscriptionId $id): ?Transcription
    {
        return $this->em->find(Transcription::class, $id);
    }

    public function findByUser(UserId $userId, int $limit = 10): array
    {
        return $this->em->createQueryBuilder()
            ->select('t')
            ->from(Transcription::class, 't')
            ->where('t.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
```

#### External Service Adapter

```php
namespace Infrastructure\External\OpenAI;

use Domain\Transcription\Service\TranscriberInterface;
use OpenAI\Client;

final class OpenAITranscriber implements TranscriberInterface
{
    private Client $client;
    private CacheInterface $cache;
    private MetricsCollector $metrics;

    public function __construct(
        string $apiKey,
        string $organizationId,
        CacheInterface $cache,
        MetricsCollector $metrics
    ) {
        $this->client = OpenAI::client($apiKey, $organizationId);
        $this->cache = $cache;
        $this->metrics = $metrics;
    }

    public function transcribe(AudioFile $audioFile): TranscriptionResult
    {
        $startTime = microtime(true);

        try {
            $response = $this->client->audio()->transcriptions->create([
                'model' => 'whisper-1',
                'file' => fopen($audioFile->path(), 'r'),
                'language' => $audioFile->detectedLanguage()?->code(),
                'response_format' => 'verbose_json'
            ]);

            $this->metrics->increment('openai.transcription.success');
            $this->metrics->histogram(
                'openai.transcription.duration',
                microtime(true) - $startTime
            );

            return new TranscriptionResult(
                $response->text,
                $response->segments ?? [],
                Language::fromCode($response->language)
            );

        } catch (\Exception $e) {
            $this->metrics->increment('openai.transcription.error');
            throw new TranscriptionFailedException(
                'OpenAI transcription failed: ' . $e->getMessage(),
                previous: $e
            );
        }
    }
}
```

#### API Controllers

```php
namespace Infrastructure\API\REST;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/transcriptions')]
final class TranscriptionController
{
    public function __construct(
        private CommandBus $commandBus,
        private QueryBus $queryBus
    ) {}

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $command = new TranscribeAudioCommand(
            audioFilePath: $request->get('audio_path'),
            userId: $request->attributes->get('user_id'),
            language: $request->get('language'),
            priority: $request->get('priority', false)
        );

        try {
            $transcriptionId = $this->commandBus->dispatch($command);

            return new JsonResponse([
                'id' => $transcriptionId->toString(),
                'status' => 'processing'
            ], 202);

        } catch (InvalidAudioFileException $e) {
            return new JsonResponse([
                'error' => 'Invalid audio file',
                'details' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(string $id, Request $request): JsonResponse
    {
        $query = new GetTranscriptionQuery(
            transcriptionId: $id,
            userId: $request->attributes->get('user_id')
        );

        $transcription = $this->queryBus->ask($query);

        if (!$transcription) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }

        return new JsonResponse($transcription);
    }
}
```

## 🔧 Patterns et Pratiques

### Dependency Injection avec PHP-DI

```php
// config/container.php
use DI\ContainerBuilder;

$containerBuilder = new ContainerBuilder();

$containerBuilder->addDefinitions([
    // Domain Services
    TranscriptionPricingService::class => DI\create(StandardPricingService::class),

    // Repositories
    TranscriptionRepository::class => DI\autowire(DoctrineTranscriptionRepository::class),

    // External Services
    TranscriberInterface::class => DI\factory(function ($c) {
        return new OpenAITranscriber(
            $_ENV['OPENAI_API_KEY'],
            $_ENV['OPENAI_ORG_ID'],
            $c->get(CacheInterface::class),
            $c->get(MetricsCollector::class)
        );
    }),

    // Command Bus
    CommandBus::class => DI\factory(function ($c) {
        $bus = new SimpleCommandBus();
        $bus->register(TranscribeAudioCommand::class, $c->get(TranscribeAudioHandler::class));
        return $bus;
    })
]);

return $containerBuilder->build();
```

### Tests Unitaires (Domain)

```php
namespace Tests\Unit\Domain\Transcription;

use PHPUnit\Framework\TestCase;
use Domain\Transcription\Entity\Transcription;
use Domain\Transcription\ValueObject\TranscribedText;

class TranscriptionTest extends TestCase
{
    public function test_can_complete_pending_transcription(): void
    {
        // Arrange
        $transcription = Transcription::create(
            AudioFile::fromPath('/tmp/test.mp3'),
            Language::FRENCH(),
            UserId::fromString('user-123')
        );

        $text = new TranscribedText('Bonjour le monde', [
            ['start' => 0, 'end' => 2, 'text' => 'Bonjour le monde']
        ]);

        // Act
        $transcription->complete($text);

        // Assert
        $this->assertTrue($transcription->isCompleted());
        $this->assertEquals('Bonjour le monde', $transcription->text()->content());
        $this->assertEquals(3, $transcription->text()->wordCount());

        $events = $transcription->pullDomainEvents();
        $this->assertCount(2, $events); // Created + Completed
        $this->assertInstanceOf(TranscriptionCompleted::class, $events[1]);
    }

    public function test_cannot_complete_already_completed_transcription(): void
    {
        // Arrange
        $transcription = $this->createCompletedTranscription();

        // Assert
        $this->expectException(DomainException::class);

        // Act
        $transcription->complete(new TranscribedText('New text'));
    }
}
```

### Tests d'Intégration

```php
namespace Tests\Integration\Application;

use Tests\IntegrationTestCase;
use Application\Transcription\Command\TranscribeAudioCommand;

class TranscribeAudioTest extends IntegrationTestCase
{
    public function test_can_transcribe_audio_file(): void
    {
        // Arrange
        $audioPath = $this->uploadTestFile('sample.mp3');
        $command = new TranscribeAudioCommand(
            audioFilePath: $audioPath,
            userId: 'test-user',
            language: 'fr'
        );

        // Act
        $transcriptionId = $this->commandBus->dispatch($command);

        // Assert
        $this->assertNotNull($transcriptionId);

        // Verify in database
        $transcription = $this->repository->findById($transcriptionId);
        $this->assertNotNull($transcription);
        $this->assertTrue($transcription->isPending());

        // Verify event was dispatched
        $this->assertEventDispatched(ProcessTranscriptionCommand::class);
    }
}
```

## 📊 Métriques et Monitoring

### Complexité Cyclomatique

```php
// Utiliser PHPStan ou Psalm
// phpstan.neon
parameters:
    level: 8
    paths:
        - src
    excludePaths:
        - src/Infrastructure/Migrations

    # Règles de complexité
    complexityLimit: 10
    methodLengthLimit: 20
    classLengthLimit: 200
```

### Code Coverage

```xml
<!-- phpunit.xml -->
<phpunit>
    <coverage>
        <include>
            <directory suffix=".php">src</directory>
        </include>
        <exclude>
            <directory>src/Infrastructure/Migrations</directory>
        </exclude>
        <report>
            <html outputDirectory="build/coverage"/>
            <text outputFile="build/coverage.txt" showOnlySummary="true"/>
        </report>
    </coverage>
</phpunit>
```

## 🚀 Migration depuis l'Architecture Actuelle

### Phase 1 : Extraction du Domain (2 semaines)

1. Créer les Value Objects pour remplacer les arrays
2. Extraire les entités depuis les Services actuels
3. Définir les interfaces Repository

### Phase 2 : Application Layer (1 semaine)

1. Transformer les Controllers en Commands/Queries
2. Implémenter les Handlers
3. Ajouter le Command/Query Bus

### Phase 3 : Infrastructure (2 semaines)

1. Adapter les Services existants comme Infrastructure
2. Implémenter les Repository avec Doctrine
3. Configurer PHP-DI

### Phase 4 : API & Frontend (3 semaines)

1. GraphQL avec GraphQLite
2. Migration progressive vers Vue 3
3. TypeScript et tests E2E

## 📚 Ressources et Références

- [Clean Architecture - Robert C. Martin](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html)
- [Domain-Driven Design - Eric Evans](https://domainlanguage.com/ddd/)
- [SOLID Principles](https://en.wikipedia.org/wiki/SOLID)
- [Hexagonal Architecture - Alistair Cockburn](https://alistair.cockburn.us/hexagonal-architecture/)

---

**Note** : Cette architecture est conçue pour évoluer. Commencez simple, mesurez, et refactorisez quand nécessaire. L'over-engineering est aussi dangereux que l'under-engineering.
