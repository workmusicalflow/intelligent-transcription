<?php

namespace Infrastructure\External\OpenAI;

use Domain\Transcription\Service\TranscriberInterface;
use Domain\Transcription\Service\TranscriptionResult;
use Domain\Transcription\ValueObject\AudioFile;
use Domain\Transcription\ValueObject\Language;
use Domain\Transcription\ValueObject\TranscribedText;
use Domain\Common\ValueObject\Money;
use Infrastructure\External\OpenAI\OpenAIClient;
use Exception;

/**
 * Adapter Whisper OpenAI pour la transcription
 */
class WhisperAdapter implements TranscriberInterface
{
    private OpenAIClient $client;
    private string $model;
    private array $defaultOptions;
    
    public function __construct(
        string $apiKey,
        string $model = 'whisper-1'
    ) {
        $this->client = new OpenAIClient($apiKey);
        $this->model = $model;
        $this->defaultOptions = [
            'response_format' => 'verbose_json',
            'timestamp_granularities' => ['segment', 'word'], // Support segments ET mots
            'temperature' => 0  // Plus de cohérence
        ];
    }
    
    public function transcribe(AudioFile $audioFile, ?Language $language = null): TranscriptionResult
    {
        try {
            $this->validateAudioFile($audioFile);
            
            $params = [
                'model' => $this->model,
                'file' => $this->prepareFileForUpload($audioFile),
                ...$this->defaultOptions
            ];
            
            // Ajouter la langue seulement si elle est spécifiée
            if ($language !== null) {
                $params['language'] = $language->code();
                
                // Ajouter un prompt contextuel pour améliorer la qualité
                $params['prompt'] = $this->getLanguagePrompt($language);
            }
            
            $response = $this->client->post('audio/transcriptions', $params);
            
            if (!$response->isSuccessful()) {
                throw new Exception("Whisper API Error: " . $response->getError());
            }
            
            return $this->parseResponse($response->getData(), $audioFile);
            
        } catch (Exception $e) {
            throw new Exception("Transcription failed: " . $e->getMessage());
        }
    }
    
    public function estimateCost(AudioFile $audioFile): Money
    {
        // Prix Whisper : $0.006 par minute
        $pricePerMinute = 0.006;
        $durationMinutes = ($audioFile->duration() ?? 0) / 60;
        $estimatedCost = $durationMinutes * $pricePerMinute;
        
        return Money::fromAmount($estimatedCost, 'USD');
    }
    
    public function getSupportedLanguages(): array
    {
        return [
            'fr', 'en', 'es', 'de', 'it', 'pt', 'ru', 'ja', 'ko', 'zh',
            'ar', 'hi', 'tr', 'pl', 'nl', 'sv', 'da', 'no', 'fi'
        ];
    }
    
    public function isLanguageSupported(Language $language): bool
    {
        return in_array($language->code(), $this->getSupportedLanguages());
    }
    
    public function getMaxFileSize(): int
    {
        return 25 * 1024 * 1024; // 25MB
    }
    
    public function getSupportedFormats(): array
    {
        return [
            'audio/mpeg',     // mp3
            'audio/mp4',      // mp4
            'audio/wav',      // wav
            'audio/webm',     // webm
            'audio/ogg',      // ogg
            'audio/flac',     // flac
            'audio/x-m4a'     // m4a
        ];
    }
    
    private function validateAudioFile(AudioFile $audioFile): void
    {
        if (!$audioFile->isValid()) {
            throw new Exception("Invalid audio file");
        }
        
        if ($audioFile->size() > $this->getMaxFileSize()) {
            throw new Exception("File too large. Max size: " . $this->getMaxFileSize() . " bytes");
        }
        
        if (!in_array($audioFile->mimeType(), $this->getSupportedFormats())) {
            throw new Exception("Unsupported format: " . $audioFile->mimeType());
        }
        
        if (!file_exists($audioFile->path())) {
            throw new Exception("Audio file not found: " . $audioFile->path());
        }
    }
    
    private function prepareFileForUpload(AudioFile $audioFile): string
    {
        // Pour une vraie implémentation HTTP multipart
        // Ici on retourne le chemin du fichier
        return $audioFile->path();
    }
    
    private function parseResponse(array $data, AudioFile $audioFile): TranscriptionResult
    {
        $text = $data['text'] ?? '';
        $segments = $data['segments'] ?? [];
        $words = $data['words'] ?? [];
        
        // Créer TranscribedText avec les segments
        $transcribedText = TranscribedText::fromContentWithSegments($text, $segments);
        
        // Déterminer la langue détectée
        $detectedLanguageCode = $data['language'] ?? 'en';
        $detectedLanguage = Language::fromCode($detectedLanguageCode);
        
        // Calculer les statistiques
        $confidence = $this->calculateAverageConfidence($segments);
        
        return new TranscriptionResult(
            $transcribedText,
            $detectedLanguage,
            $confidence,
            [
                'model' => $this->model,
                'language_detected' => $detectedLanguageCode,
                'segments_count' => count($segments),
                'words_count' => count($words),
                'word_count' => str_word_count($text),
                'duration' => $audioFile->duration() ?? 0,
                'cost' => $this->calculateActualCost($audioFile)->toArray(),
                'processing_time' => microtime(true) // Temps de traitement simulé
            ]
        );
    }
    
    private function calculateAverageConfidence(array $segments): float
    {
        if (empty($segments)) {
            return 0.0;
        }
        
        $totalConfidence = 0;
        $count = 0;
        
        foreach ($segments as $segment) {
            if (isset($segment['avg_logprob'])) {
                // Convertir log prob en confidence (approximation)
                $confidence = exp($segment['avg_logprob']);
                $totalConfidence += $confidence;
                $count++;
            }
        }
        
        return $count > 0 ? $totalConfidence / $count : 0.0;
    }
    
    private function calculateActualCost(AudioFile $audioFile): Money
    {
        // Coût réel basé sur la durée effective
        return $this->estimateCost($audioFile);
    }
    
    public function setOptions(array $options): void
    {
        $this->defaultOptions = array_merge($this->defaultOptions, $options);
    }
    
    public function getOptions(): array
    {
        return $this->defaultOptions;
    }
    
    public function getStats(): array
    {
        return [
            'model' => $this->model,
            'supported_languages' => count($this->getSupportedLanguages()),
            'supported_formats' => count($this->getSupportedFormats()),
            'max_file_size_mb' => $this->getMaxFileSize() / 1024 / 1024,
            'options' => $this->defaultOptions
        ];
    }
    
    // Méthodes de l'interface TranscriberInterface
    
    public function detectLanguage(AudioFile $audioFile): Language
    {
        // Whisper peut détecter automatiquement la langue
        // Pour cela, on fait une transcription sans spécifier la langue
        $result = $this->transcribe($audioFile, null);
        
        // La langue détectée est déjà retournée dans TranscriptionResult
        return $result->detectedLanguage();
    }
    
    public function supportsFormat(string $format): bool
    {
        return in_array($format, $this->getSupportedFormats());
    }
    
    public function getMaxDurationSupported(): int
    {
        // Whisper supporte jusqu'à 25MB ce qui correspond environ à 170 minutes en MP3 128kbps
        return 170 * 60; // 170 minutes en secondes
    }
    
    public function getMaxFileSizeSupported(): int
    {
        return $this->getMaxFileSize();
    }
    
    public function getName(): string
    {
        return 'OpenAI Whisper';
    }
    
    /**
     * Génère un prompt contextuel pour améliorer la qualité selon la langue
     */
    private function getLanguagePrompt(Language $language): string
    {
        $prompts = [
            'fr' => 'Transcription précise en français avec ponctuation correcte et accents appropriés.',
            'en' => 'Accurate English transcription with proper punctuation and formatting.',
            'es' => 'Transcripción precisa en español con puntuación y acentos correctos.',
            'de' => 'Präzise deutsche Transkription mit korrekter Interpunktion und Umlauten.',
            'it' => 'Trascrizione precisa in italiano con punteggiatura e accenti corretti.',
            'pt' => 'Transcrição precisa em português com pontuação e acentos corretos.'
        ];
        
        return $prompts[$language->code()] ?? 'Accurate transcription with proper punctuation.';
    }
}