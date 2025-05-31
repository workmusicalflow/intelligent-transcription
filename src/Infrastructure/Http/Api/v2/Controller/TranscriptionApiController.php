<?php

namespace Infrastructure\Http\Api\v2\Controller;

use Infrastructure\Http\Api\v2\ApiRequest;
use Infrastructure\Http\Api\v2\ApiResponse;
use Application\Transcription\Command\CreateTranscriptionCommand;
use Application\Transcription\Command\ProcessTranscriptionCommand;
use Application\Transcription\Query\GetTranscriptionQuery;
use Application\Transcription\Query\GetUserTranscriptionsQuery;
use Domain\Transcription\ValueObject\TranscriptionId;
use Domain\Transcription\ValueObject\AudioFile;
use Domain\Transcription\ValueObject\Language;
use Domain\Common\ValueObject\UserId;
use Domain\Transcription\Entity\Transcription;

/**
 * Controller API pour les transcriptions
 */
class TranscriptionApiController extends BaseApiController
{
    /**
     * Liste les transcriptions de l'utilisateur
     */
    public function index(ApiRequest $request): ApiResponse
    {
        try {
            $userId = new UserId($request->getUserId());
            $page = (int)$request->getQuery('page', 1);
            $perPage = (int)$request->getQuery('per_page', 20);
            
            // Limiter le nombre par page
            $perPage = min($perPage, 100);
            
            $query = new GetUserTranscriptionsQuery($userId, $page, $perPage);
            $handler = $this->get(\Application\Transcription\Handler\GetUserTranscriptionsHandler::class);
            
            $result = $handler->handle($query);
            
            return ApiResponse::paginated(
                $this->transformCollection($result['items']),
                $result['total'],
                $page,
                $perPage
            );
            
        } catch (\Exception $e) {
            return ApiResponse::serverError($e->getMessage());
        }
    }
    
    /**
     * Affiche une transcription
     */
    public function show(ApiRequest $request): ApiResponse
    {
        try {
            $transcriptionId = new TranscriptionId($request->getParam('id'));
            $userId = new UserId($request->getUserId());
            
            $query = new GetTranscriptionQuery($transcriptionId, $userId);
            $handler = $this->get(\Application\Transcription\Handler\GetTranscriptionHandler::class);
            
            $transcription = $handler->handle($query);
            
            if (!$transcription) {
                return ApiResponse::notFound('Transcription not found');
            }
            
            return ApiResponse::success($this->transformEntity($transcription));
            
        } catch (\Exception $e) {
            return ApiResponse::serverError($e->getMessage());
        }
    }
    
    /**
     * Crée une nouvelle transcription
     */
    public function create(ApiRequest $request): ApiResponse
    {
        try {
            // Validation
            $validationResponse = $this->validate($request, [
                'language' => 'required|max:5'
            ]);
            
            if ($validationResponse) {
                return $validationResponse;
            }
            
            // Vérifier qu'on a soit un fichier, soit une URL YouTube
            $hasFile = isset($_FILES['audio']) && $_FILES['audio']['error'] === UPLOAD_ERR_OK;
            $hasYoutubeUrl = !empty($request->getBody('youtube_url'));
            
            if (!$hasFile && !$hasYoutubeUrl) {
                return ApiResponse::badRequest('Please provide either an audio file or a YouTube URL');
            }
            
            // Créer l'AudioFile
            if ($hasFile) {
                $audioFile = $this->handleFileUpload($_FILES['audio']);
            } else {
                $audioFile = $this->handleYouTubeUrl($request->getBody('youtube_url'));
            }
            
            // Créer la commande
            $command = new CreateTranscriptionCommand(
                new UserId($request->getUserId()),
                $audioFile,
                new Language($request->getBody('language'))
            );
            
            $handler = $this->get(\Application\Transcription\Handler\CreateTranscriptionHandler::class);
            $transcriptionId = $handler->handle($command);
            
            return ApiResponse::created(
                ['id' => $transcriptionId->value()],
                '/api/v2/transcriptions/' . $transcriptionId->value()
            );
            
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::badRequest($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::serverError($e->getMessage());
        }
    }
    
    /**
     * Met à jour une transcription
     */
    public function update(ApiRequest $request): ApiResponse
    {
        // Pour l'instant, on ne permet pas la modification des transcriptions
        return ApiResponse::forbidden('Transcriptions cannot be updated');
    }
    
    /**
     * Supprime une transcription
     */
    public function delete(ApiRequest $request): ApiResponse
    {
        try {
            $transcriptionId = new TranscriptionId($request->getParam('id'));
            $userId = new UserId($request->getUserId());
            
            // Vérifier que l'utilisateur a accès
            $query = new GetTranscriptionQuery($transcriptionId, $userId);
            $handler = $this->get(\Application\Transcription\Handler\GetTranscriptionHandler::class);
            $transcription = $handler->handle($query);
            
            if (!$transcription) {
                return ApiResponse::notFound('Transcription not found');
            }
            
            // Supprimer
            $repository = $this->get(\Domain\Transcription\Repository\TranscriptionRepository::class);
            $repository->delete($transcriptionId);
            
            return ApiResponse::noContent();
            
        } catch (\Exception $e) {
            return ApiResponse::serverError($e->getMessage());
        }
    }
    
    /**
     * Lance le traitement d'une transcription
     */
    public function process(ApiRequest $request): ApiResponse
    {
        try {
            $transcriptionId = new TranscriptionId($request->getParam('id'));
            $userId = new UserId($request->getUserId());
            
            // Vérifier l'accès
            $query = new GetTranscriptionQuery($transcriptionId, $userId);
            $handler = $this->get(\Application\Transcription\Handler\GetTranscriptionHandler::class);
            $transcription = $handler->handle($query);
            
            if (!$transcription) {
                return ApiResponse::notFound('Transcription not found');
            }
            
            if (!$transcription->status()->isPending()) {
                return ApiResponse::conflict('Transcription is already processed or processing');
            }
            
            // Lancer le traitement
            $command = new ProcessTranscriptionCommand($transcriptionId);
            $processHandler = $this->get(\Application\Transcription\Handler\ProcessTranscriptionHandler::class);
            $processHandler->handle($command);
            
            return ApiResponse::success(['message' => 'Processing started']);
            
        } catch (\Exception $e) {
            return ApiResponse::serverError($e->getMessage());
        }
    }
    
    /**
     * Télécharge une transcription
     */
    public function download(ApiRequest $request): ApiResponse
    {
        try {
            $transcriptionId = new TranscriptionId($request->getParam('id'));
            $userId = new UserId($request->getUserId());
            $format = $request->getQuery('format', 'txt');
            
            // Vérifier l'accès
            $query = new GetTranscriptionQuery($transcriptionId, $userId);
            $handler = $this->get(\Application\Transcription\Handler\GetTranscriptionHandler::class);
            $transcription = $handler->handle($query);
            
            if (!$transcription) {
                return ApiResponse::notFound('Transcription not found');
            }
            
            if (!$transcription->status()->isCompleted()) {
                return ApiResponse::conflict('Transcription is not yet completed');
            }
            
            // Générer le contenu selon le format
            $content = $this->generateDownloadContent($transcription, $format);
            $filename = 'transcription_' . $transcriptionId->value() . '.' . $format;
            
            // Forcer le téléchargement
            header('Content-Type: ' . $this->getContentTypeForFormat($format));
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($content));
            
            echo $content;
            exit;
            
        } catch (\Exception $e) {
            return ApiResponse::serverError($e->getMessage());
        }
    }
    
    /**
     * Webhook appelé quand une transcription est terminée
     */
    public function webhookCompleted(ApiRequest $request): ApiResponse
    {
        // Vérifier la signature du webhook
        $signature = $request->getHeader('X-Webhook-Signature');
        if (!$this->verifyWebhookSignature($request->getBody(), $signature)) {
            return ApiResponse::unauthorized('Invalid webhook signature');
        }
        
        try {
            $transcriptionId = $request->getBody('transcription_id');
            
            // Notifier les clients connectés via WebSocket/SSE
            // À implémenter avec la tâche Real-time Features
            
            return ApiResponse::success(['message' => 'Webhook processed']);
            
        } catch (\Exception $e) {
            return ApiResponse::serverError($e->getMessage());
        }
    }
    
    /**
     * Transforme une entité Transcription pour l'API
     */
    protected function transformEntity($entity): array
    {
        if (!$entity instanceof Transcription) {
            return [];
        }
        
        $data = [
            'id' => $entity->id()->value(),
            'status' => $entity->status()->value(),
            'language' => [
                'code' => $entity->language()->code(),
                'name' => $entity->language()->name()
            ],
            'created_at' => $entity->createdAt()->format('c'),
            'updated_at' => $entity->updatedAt()->format('c')
        ];
        
        // Ajouter le texte si complété
        if ($entity->status()->isCompleted()) {
            $data['text'] = $entity->transcribedText()->value();
            $data['cost'] = $entity->cost() ? [
                'amount' => $entity->cost()->amount(),
                'currency' => $entity->cost()->currency()
            ] : null;
        }
        
        // Ajouter les métadonnées YouTube si présentes
        if ($entity->youtubeMetadata()) {
            $data['youtube'] = [
                'title' => $entity->youtubeMetadata()->title(),
                'video_id' => $entity->youtubeMetadata()->videoId(),
                'duration' => $entity->youtubeMetadata()->duration()
            ];
        }
        
        return $data;
    }
    
    /**
     * Gère l'upload d'un fichier
     */
    private function handleFileUpload(array $file): AudioFile
    {
        $uploadDir = __DIR__ . '/../../../../../../../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $filename = uniqid() . '_' . basename($file['name']);
        $uploadPath = $uploadDir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new \RuntimeException('Failed to upload file');
        }
        
        return AudioFile::fromPath($uploadPath);
    }
    
    /**
     * Gère une URL YouTube
     */
    private function handleYouTubeUrl(string $url): AudioFile
    {
        $youtubeService = $this->get(\Infrastructure\External\YouTube\YouTubeDownloader::class);
        $audioPath = $youtubeService->downloadAudio($url);
        
        return AudioFile::fromPath($audioPath);
    }
    
    /**
     * Génère le contenu de téléchargement
     */
    private function generateDownloadContent(Transcription $transcription, string $format): string
    {
        switch ($format) {
            case 'json':
                return json_encode($this->transformEntity($transcription), JSON_PRETTY_PRINT);
                
            case 'srt':
                // Format SRT simple (sans timing réel)
                $lines = explode("\n", $transcription->transcribedText()->value());
                $srt = '';
                foreach ($lines as $i => $line) {
                    $srt .= ($i + 1) . "\n";
                    $srt .= "00:00:" . str_pad($i * 5, 2, '0', STR_PAD_LEFT) . ",000 --> 00:00:" . str_pad(($i + 1) * 5, 2, '0', STR_PAD_LEFT) . ",000\n";
                    $srt .= $line . "\n\n";
                }
                return $srt;
                
            case 'txt':
            default:
                return $transcription->transcribedText()->value();
        }
    }
    
    /**
     * Retourne le content type pour un format
     */
    private function getContentTypeForFormat(string $format): string
    {
        return match($format) {
            'json' => 'application/json',
            'srt' => 'text/plain',
            default => 'text/plain'
        };
    }
    
    /**
     * Vérifie la signature d'un webhook
     */
    private function verifyWebhookSignature(array $payload, ?string $signature): bool
    {
        if (!$signature) {
            return false;
        }
        
        $secret = $_ENV['WEBHOOK_SECRET'] ?? '';
        $expectedSignature = hash_hmac('sha256', json_encode($payload), $secret);
        
        return hash_equals($expectedSignature, $signature);
    }
}