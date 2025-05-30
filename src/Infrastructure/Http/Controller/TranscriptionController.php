<?php

namespace Infrastructure\Http\Controller;

use Application\Transcription\Command\CreateTranscriptionCommand;
use Application\Transcription\Command\ProcessTranscriptionCommand;
use Application\Transcription\Query\GetTranscriptionQuery;
use Application\Transcription\Query\GetUserTranscriptionsQuery;
use Domain\Transcription\ValueObject\AudioFile;
use Domain\Transcription\ValueObject\Language;
use Domain\Transcription\ValueObject\TranscriptionId;
use Domain\Common\ValueObject\UserId;
use Template\TwigManager;

/**
 * Controller HTTP pour la gestion des transcriptions
 * 
 * Utilise les Command/Query handlers de la couche Application
 */
class TranscriptionController extends BaseController
{
    private TwigManager $twig;
    
    public function __construct()
    {
        parent::__construct();
        $this->twig = new TwigManager();
    }
    
    /**
     * Affiche le formulaire de transcription
     */
    public function index(): void
    {
        echo $this->twig->render('home/index.twig', [
            'title' => 'Transcription Audio & Vidéo',
            'languages' => $this->getSupportedLanguages()
        ]);
    }
    
    /**
     * Crée une nouvelle transcription
     */
    public function create(): void
    {
        try {
            // Validation des données
            $validationError = $this->validateTranscriptionRequest();
            if ($validationError) {
                $this->jsonError($validationError);
                return;
            }
            
            // Récupération des données
            $userId = $this->getCurrentUserId();
            $language = new Language($_POST['language'] ?? 'fr');
            
            // Gestion du fichier audio ou URL YouTube
            if (!empty($_FILES['audio']['tmp_name'])) {
                $audioFile = $this->handleFileUpload($_FILES['audio']);
            } elseif (!empty($_POST['youtube_url'])) {
                $audioFile = $this->handleYouTubeUrl($_POST['youtube_url']);
            } else {
                $this->jsonError('Aucun fichier audio ou URL YouTube fourni.');
                return;
            }
            
            // Créer la commande
            $command = new CreateTranscriptionCommand(
                $userId,
                $audioFile,
                $language
            );
            
            // Exécuter via le handler (récupéré depuis le conteneur DI)
            $handler = $this->get(\Application\Transcription\Handler\CreateTranscriptionHandler::class);
            $transcriptionId = $handler->handle($command);
            
            // Retourner la réponse
            $this->json([
                'success' => true,
                'transcription_id' => $transcriptionId->value(),
                'redirect' => '/processing.php?id=' . $transcriptionId->value()
            ]);
            
        } catch (\Exception $e) {
            error_log("TranscriptionController::create error: " . $e->getMessage());
            $this->jsonError('Une erreur est survenue lors de la création de la transcription.', 500);
        }
    }
    
    /**
     * Lance le traitement d'une transcription
     */
    public function process(string $transcriptionId): void
    {
        try {
            $command = new ProcessTranscriptionCommand(
                new TranscriptionId($transcriptionId)
            );
            
            $handler = $this->get(\Application\Transcription\Handler\ProcessTranscriptionHandler::class);
            $handler->handle($command);
            
            $this->json(['success' => true]);
            
        } catch (\Exception $e) {
            error_log("TranscriptionController::process error: " . $e->getMessage());
            $this->jsonError('Erreur lors du traitement de la transcription.', 500);
        }
    }
    
    /**
     * Affiche une transcription
     */
    public function show(string $transcriptionId): void
    {
        try {
            $query = new GetTranscriptionQuery(
                new TranscriptionId($transcriptionId),
                $this->getCurrentUserId()
            );
            
            $handler = $this->get(\Application\Transcription\Handler\GetTranscriptionHandler::class);
            $transcription = $handler->handle($query);
            
            if (!$transcription) {
                header("HTTP/1.0 404 Not Found");
                echo $this->twig->render('error.twig', [
                    'message' => 'Transcription non trouvée.'
                ]);
                return;
            }
            
            echo $this->twig->render('result/show.twig', [
                'transcription' => $this->formatTranscriptionForView($transcription)
            ]);
            
        } catch (\Exception $e) {
            error_log("TranscriptionController::show error: " . $e->getMessage());
            header("HTTP/1.0 500 Internal Server Error");
            echo $this->twig->render('error.twig', [
                'message' => 'Erreur lors de la récupération de la transcription.'
            ]);
        }
    }
    
    /**
     * Liste les transcriptions de l'utilisateur
     */
    public function userTranscriptions(): void
    {
        try {
            $userId = $this->getCurrentUserId();
            $page = (int)($_GET['page'] ?? 1);
            $limit = 20;
            
            $query = new GetUserTranscriptionsQuery($userId, $page, $limit);
            $handler = $this->get(\Application\Transcription\Handler\GetUserTranscriptionsHandler::class);
            $result = $handler->handle($query);
            
            if ($this->isAjaxRequest()) {
                $this->json($result);
            } else {
                echo $this->twig->render('transcriptions/list.twig', $result);
            }
            
        } catch (\Exception $e) {
            error_log("TranscriptionController::userTranscriptions error: " . $e->getMessage());
            $this->jsonError('Erreur lors de la récupération des transcriptions.', 500);
        }
    }
    
    /**
     * Télécharge une transcription
     */
    public function download(string $transcriptionId, string $format = 'txt'): void
    {
        try {
            $query = new GetTranscriptionQuery(
                new TranscriptionId($transcriptionId),
                $this->getCurrentUserId()
            );
            
            $handler = $this->get(\Application\Transcription\Handler\GetTranscriptionHandler::class);
            $transcription = $handler->handle($query);
            
            if (!$transcription) {
                header("HTTP/1.0 404 Not Found");
                exit('Transcription non trouvée.');
            }
            
            $exporter = $this->get(\Application\Transcription\Service\TranscriptionExporter::class);
            $content = $exporter->export($transcription, $format);
            
            // Headers pour le téléchargement
            header('Content-Type: ' . $this->getContentTypeForFormat($format));
            header('Content-Disposition: attachment; filename="transcription_' . $transcriptionId . '.' . $format . '"');
            header('Content-Length: ' . strlen($content));
            
            echo $content;
            
        } catch (\Exception $e) {
            error_log("TranscriptionController::download error: " . $e->getMessage());
            header("HTTP/1.0 500 Internal Server Error");
            exit('Erreur lors du téléchargement.');
        }
    }
    
    // Méthodes privées utilitaires
    
    private function validateTranscriptionRequest(): ?string
    {
        if (empty($_FILES['audio']['tmp_name']) && empty($_POST['youtube_url'])) {
            return 'Veuillez fournir un fichier audio ou une URL YouTube.';
        }
        
        if (!empty($_FILES['audio']['tmp_name'])) {
            $maxSize = 100 * 1024 * 1024; // 100MB
            if ($_FILES['audio']['size'] > $maxSize) {
                return 'Le fichier est trop volumineux (max 100MB).';
            }
            
            $allowedTypes = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/mp4', 'video/mp4'];
            if (!in_array($_FILES['audio']['type'], $allowedTypes)) {
                return 'Format de fichier non supporté.';
            }
        }
        
        return null;
    }
    
    private function handleFileUpload(array $file): AudioFile
    {
        $uploadDir = __DIR__ . '/../../../../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $filename = uniqid() . '_' . basename($file['name']);
        $uploadPath = $uploadDir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new \Exception('Échec du téléchargement du fichier.');
        }
        
        return AudioFile::fromPath($uploadPath);
    }
    
    private function handleYouTubeUrl(string $url): AudioFile
    {
        $youtubeService = $this->get(\Infrastructure\External\YouTube\YouTubeDownloader::class);
        $audioPath = $youtubeService->downloadAudio($url);
        
        return AudioFile::fromPath($audioPath);
    }
    
    private function getCurrentUserId(): UserId
    {
        // Pour l'instant, utiliser un ID par défaut ou de session
        $userId = $_SESSION['user_id'] ?? 'default_user';
        return new UserId($userId);
    }
    
    private function getSupportedLanguages(): array
    {
        return [
            'fr' => 'Français',
            'en' => 'English',
            'es' => 'Español',
            'de' => 'Deutsch',
            'it' => 'Italiano',
            'pt' => 'Português',
            'nl' => 'Nederlands',
            'pl' => 'Polski',
            'ru' => 'Русский',
            'ja' => '日本語',
            'zh' => '中文',
            'ar' => 'العربية'
        ];
    }
    
    private function formatTranscriptionForView($transcription): array
    {
        return [
            'id' => $transcription->id()->value(),
            'text' => $transcription->transcribedText()->value(),
            'language' => $transcription->language()->name(),
            'status' => $transcription->status()->value(),
            'created_at' => $transcription->createdAt()->format('d/m/Y H:i'),
            'youtube_metadata' => $transcription->youtubeMetadata() ? [
                'title' => $transcription->youtubeMetadata()->title(),
                'video_id' => $transcription->youtubeMetadata()->videoId(),
                'duration' => $transcription->youtubeMetadata()->duration()
            ] : null
        ];
    }
    
    private function getContentTypeForFormat(string $format): string
    {
        return match($format) {
            'json' => 'application/json',
            'xml' => 'application/xml',
            'pdf' => 'application/pdf',
            default => 'text/plain'
        };
    }
}