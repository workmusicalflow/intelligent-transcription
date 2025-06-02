<?php

namespace Domain\Dubbing\Service;

use Domain\Transcription\ValueObject\AudioFile;
use Domain\Transcription\ValueObject\Language;
use Domain\Dubbing\ValueObject\DubbingTranscription;
use Domain\Dubbing\ValueObject\AudioMetadata;
use Domain\Common\Exception\DomainException;
use Infrastructure\External\OpenAI\OpenAIClient;

/**
 * Service Whisper enrichi pour le doublage révolutionnaire
 * 
 * Exploite les capacités complètes de Whisper-1 avec word-level timestamps,
 * prompting contextuel et optimisations spécifiques au doublage
 */
final class EnhancedWhisperService
{
    private OpenAIClient $openAIClient;
    private array $config;

    public function __construct(OpenAIClient $openAIClient, array $config = [])
    {
        $this->openAIClient = $openAIClient;
        $this->config = array_merge([
            'model' => 'whisper-1',
            'response_format' => 'verbose_json',
            'temperature' => 0.0,
            'timestamp_granularities' => ['segment', 'word'], // 🔑 RÉVOLUTIONNAIRE
            'max_file_size' => 25 * 1024 * 1024, // 25MB
            'timeout' => 300 // 5 minutes
        ], $config);
    }

    /**
     * Transcription optimisée pour le doublage avec word-level timestamps
     */
    public function transcribeForDubbing(
        AudioFile $audioFile, 
        ?Language $language = null,
        array $dubbingContext = []
    ): DubbingTranscription {
        $this->validateAudioFile($audioFile);
        
        // Générer un prompt contextuel pour le doublage
        $contextualPrompt = $this->generateContextualPrompt($audioFile, $dubbingContext);
        
        $requestParams = [
            'model' => $this->config['model'],
            'file' => $this->prepareFileForUpload($audioFile),
            'response_format' => $this->config['response_format'],
            'timestamp_granularities' => $this->config['timestamp_granularities'],
            'temperature' => $this->config['temperature'],
            'prompt' => $contextualPrompt
        ];
        
        if ($language !== null) {
            $requestParams['language'] = $language->code();
        }
        
        try {
            $response = $this->openAIClient->post('audio/transcriptions', $requestParams);
            
            if (!$response->isSuccessful()) {
                throw new DomainException(
                    "Whisper transcription failed: " . $response->getError()
                );
            }
            
            $whisperData = $response->getData();
            
            // Créer les métadonnées audio enrichies
            $audioMetadata = AudioMetadata::fromWhisperData(
                $whisperData, 
                $language ? $language->code() : ($whisperData['language'] ?? 'en')
            );
            
            // Enrichir avec le contexte de doublage
            if (!empty($dubbingContext['speakers'])) {
                $audioMetadata = $audioMetadata->withSpeakers($dubbingContext['speakers']);
            }
            
            if (!empty($dubbingContext['technical_terms'])) {
                $audioMetadata = $audioMetadata->withTechnicalTerms($dubbingContext['technical_terms']);
            }
            
            return DubbingTranscription::fromWhisperResponse($whisperData, $audioMetadata);
            
        } catch (\Exception $e) {
            throw new DomainException(
                "Enhanced Whisper transcription failed: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Traduction avec préservation des timestamps (RÉVOLUTIONNAIRE)
     */
    public function translateWithTimestamps(
        DubbingTranscription $sourceTranscription,
        Language $targetLanguage,
        AudioFile $originalAudioFile
    ): DubbingTranscription {
        // Construire un prompt spécialisé pour la traduction de doublage
        $translationPrompt = $this->buildTranslationPrompt(
            $sourceTranscription,
            $targetLanguage
        );
        
        $requestParams = [
            'model' => $this->config['model'],
            'file' => $this->prepareFileForUpload($originalAudioFile),
            'response_format' => $this->config['response_format'],
            'timestamp_granularities' => ['segment'], // Segments pour traduction
            'temperature' => 0.1, // Légèrement plus créatif pour la traduction
            'prompt' => $translationPrompt,
            'task' => 'translate' // 🔑 Mode traduction Whisper
        ];
        
        try {
            $response = $this->openAIClient->post('audio/translations', $requestParams);
            
            if (!$response->isSuccessful()) {
                throw new DomainException(
                    "Whisper translation failed: " . $response->getError()
                );
            }
            
            $translatedData = $response->getData();
            
            // Créer les métadonnées pour la version traduite
            $translatedMetadata = new AudioMetadata(
                $sourceTranscription->detectedLanguage()->code(),
                $targetLanguage->code(),
                $sourceTranscription->metadata()->duration(),
                $sourceTranscription->metadata()->averageSpeechRate(),
                $sourceTranscription->metadata()->contentType(),
                $sourceTranscription->metadata()->speakers(),
                $sourceTranscription->metadata()->technicalTerms(),
                $sourceTranscription->metadata()->noiseLevel(),
                $sourceTranscription->metadata()->emotionalTones(),
                $sourceTranscription->metadata()->pausePatterns(),
                $sourceTranscription->metadata()->hasBackgroundMusic(),
                $sourceTranscription->metadata()->silenceRegions(),
                $sourceTranscription->metadata()->compressionRatio()
            );
            
            // Préserver les word timestamps originaux si possible
            $preservedWords = $this->preserveWordTimestamps(
                $sourceTranscription->words(),
                $translatedData['segments'] ?? []
            );
            
            return new DubbingTranscription(
                $translatedData['text'],
                $translatedData['segments'] ?? [],
                $preservedWords,
                $translatedMetadata,
                $sourceTranscription->confidence(),
                $targetLanguage,
                $sourceTranscription->speakerSegments()
            );
            
        } catch (\Exception $e) {
            throw new DomainException(
                "Enhanced Whisper translation failed: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Optimisation batch pour plusieurs fichiers
     */
    public function batchTranscribeForDubbing(
        array $audioFiles,
        ?Language $language = null,
        array $sharedContext = []
    ): array {
        $results = [];
        $errors = [];
        
        foreach ($audioFiles as $index => $audioFile) {
            try {
                $results[$index] = $this->transcribeForDubbing(
                    $audioFile,
                    $language,
                    $sharedContext
                );
            } catch (\Exception $e) {
                $errors[$index] = $e->getMessage();
            }
        }
        
        return [
            'results' => $results,
            'errors' => $errors,
            'success_rate' => count($results) / count($audioFiles)
        ];
    }

    /**
     * Analyse qualité pour recommandations d'amélioration
     */
    public function analyzeTranscriptionQuality(
        DubbingTranscription $transcription
    ): array {
        $quality = $transcription->getQualityMetrics();
        $recommendations = [];
        
        // Recommandations basées sur la qualité
        if ($quality['overallConfidence'] < 0.8) {
            $recommendations[] = [
                'type' => 'audio_quality',
                'priority' => 'high',
                'message' => 'Low confidence detected. Consider improving audio quality or using noise reduction.',
                'action' => 'preprocess_audio'
            ];
        }
        
        if (!$transcription->hasWordTimestamps()) {
            $recommendations[] = [
                'type' => 'feature_upgrade',
                'priority' => 'critical',
                'message' => 'Word-level timestamps missing. Reprocess with enhanced Whisper for optimal dubbing.',
                'action' => 'reprocess_with_word_timestamps'
            ];
        }
        
        if ($quality['speechRate'] > 200) {
            $recommendations[] = [
                'type' => 'speech_rate',
                'priority' => 'medium',
                'message' => 'Very fast speech detected. Consider segment adjustment for dubbing.',
                'action' => 'adjust_segmentation'
            ];
        }
        
        if ($transcription->metadata()->hasBackgroundMusic()) {
            $recommendations[] = [
                'type' => 'audio_separation',
                'priority' => 'medium',
                'message' => 'Background music detected. Consider audio separation for cleaner dubbing.',
                'action' => 'separate_audio_tracks'
            ];
        }
        
        return [
            'qualityScore' => $quality['qualityScore'],
            'dubbingReadiness' => $quality['dubbingReadiness'],
            'recommendations' => $recommendations,
            'metrics' => $quality
        ];
    }

    private function generateContextualPrompt(
        AudioFile $audioFile,
        array $dubbingContext = []
    ): string {
        $promptParts = [];
        
        // Base prompt pour doublage
        $promptParts[] = "Accurate transcription with natural flow, proper punctuation and conversational style.";
        $promptParts[] = "Preserve emotional tone, pauses, and natural speech rhythm for dubbing synchronization.";
        
        // Contexte spécifique au contenu
        if (isset($dubbingContext['content_type'])) {
            switch ($dubbingContext['content_type']) {
                case 'dialogue':
                    $promptParts[] = "This is film/TV dialogue with natural speech patterns and character interactions.";
                    break;
                case 'narration':
                    $promptParts[] = "This is narration content requiring clear, authoritative delivery.";
                    break;
                case 'news':
                    $promptParts[] = "This is news content with professional broadcasting style.";
                    break;
            }
        }
        
        // Locuteurs détectés
        if (!empty($dubbingContext['speakers'])) {
            $speakers = implode(', ', $dubbingContext['speakers']);
            $promptParts[] = "Character names: {$speakers}.";
        }
        
        // Termes techniques
        if (!empty($dubbingContext['technical_terms'])) {
            $terms = implode(', ', $dubbingContext['technical_terms']);
            $promptParts[] = "Technical terms: {$terms}.";
        }
        
        // Instructions de qualité
        $promptParts[] = "Maintain precise timing and word boundaries for optimal dubbing synchronization.";
        
        return implode(' ', $promptParts);
    }

    private function buildTranslationPrompt(
        DubbingTranscription $source,
        Language $targetLanguage
    ): string {
        $promptParts = [];
        
        // Base de traduction
        $sourceLang = $source->detectedLanguage()->code();
        $targetLang = $targetLanguage->code();
        
        $promptParts[] = "Translate from {$sourceLang} to {$targetLang} while preserving:";
        $promptParts[] = "- Natural speech flow and timing";
        $promptParts[] = "- Emotional tone and intensity";
        $promptParts[] = "- Cultural context and meaning";
        $promptParts[] = "- Character personality through dialogue";
        
        // Contexte spécifique
        $contentType = $source->metadata()->contentType();
        switch ($contentType) {
            case 'dialogue':
                $promptParts[] = "This is dialogue requiring natural conversational translation.";
                break;
            case 'narration':
                $promptParts[] = "This is narration requiring clear, authoritative translation.";
                break;
        }
        
        // Termes techniques à préserver
        $technicalTerms = $source->metadata()->technicalTerms();
        if (!empty($technicalTerms)) {
            $terms = implode(', ', $technicalTerms);
            $promptParts[] = "Preserve technical accuracy for: {$terms}.";
        }
        
        return implode(' ', $promptParts);
    }

    private function preserveWordTimestamps(
        array $originalWords,
        array $translatedSegments
    ): array {
        // Algorithme simple de préservation des timestamps
        // Dans une implémentation complète, cela utiliserait un alignement sophistiqué
        $preservedWords = [];
        $currentWordIndex = 0;
        
        foreach ($translatedSegments as $segment) {
            $segmentText = $segment['text'] ?? '';
            $segmentStart = $segment['start'] ?? 0;
            $segmentEnd = $segment['end'] ?? 0;
            $segmentDuration = $segmentEnd - $segmentStart;
            
            $words = explode(' ', trim($segmentText));
            $wordCount = count($words);
            
            if ($wordCount > 0) {
                $avgWordDuration = $segmentDuration / $wordCount;
                
                foreach ($words as $wordIndex => $word) {
                    $wordStart = $segmentStart + ($wordIndex * $avgWordDuration);
                    $wordEnd = $wordStart + $avgWordDuration;
                    
                    $preservedWords[] = [
                        'word' => $word,
                        'start' => $wordStart,
                        'end' => $wordEnd,
                        'confidence' => $segment['avg_logprob'] ?? null,
                        'estimated' => true // Marquer comme estimé
                    ];
                }
            }
        }
        
        return $preservedWords;
    }

    private function validateAudioFile(AudioFile $audioFile): void
    {
        $fileSize = $audioFile->size();
        
        if ($fileSize > $this->config['max_file_size']) {
            throw new DomainException(
                sprintf(
                    "Audio file too large: %d bytes. Maximum allowed: %d bytes",
                    $fileSize,
                    $this->config['max_file_size']
                )
            );
        }
        
        $supportedFormats = ['mp3', 'mp4', 'wav', 'webm', 'ogg', 'flac', 'm4a'];
        $extension = pathinfo($audioFile->path(), PATHINFO_EXTENSION);
        
        if (!in_array(strtolower($extension), $supportedFormats, true)) {
            throw new DomainException(
                sprintf(
                    "Unsupported audio format: %s. Supported formats: %s",
                    $extension,
                    implode(', ', $supportedFormats)
                )
            );
        }
    }

    private function prepareFileForUpload(AudioFile $audioFile): \CURLFile
    {
        $filePath = $audioFile->path();
        
        if (!file_exists($filePath)) {
            throw new DomainException("Audio file not found: {$filePath}");
        }
        
        $mimeType = $this->getMimeType($filePath);
        $fileName = basename($filePath);
        
        return new \CURLFile($filePath, $mimeType, $fileName);
    }

    private function getMimeType(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'mp3' => 'audio/mpeg',
            'mp4' => 'audio/mp4',
            'wav' => 'audio/wav',
            'webm' => 'audio/webm',
            'ogg' => 'audio/ogg',
            'flac' => 'audio/flac',
            'm4a' => 'audio/mp4'
        ];
        
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    public function getCapabilities(): array
    {
        return [
            'models' => [$this->config['model']],
            'wordLevelTimestamps' => true,
            'segmentLevelTimestamps' => true,
            'translation' => true,
            'multipleLanguages' => true,
            'contextualPrompting' => true,
            'dubbingOptimized' => true,
            'maxFileSize' => $this->config['max_file_size'],
            'supportedFormats' => ['mp3', 'mp4', 'wav', 'webm', 'ogg', 'flac', 'm4a'],
            'features' => [
                'emotional_tone_preservation',
                'technical_term_recognition',
                'speaker_context_awareness',
                'dubbing_specific_optimization'
            ]
        ];
    }

    public function __toString(): string
    {
        return sprintf(
            'EnhancedWhisperService[model=%s, word_timestamps=%s]',
            $this->config['model'],
            in_array('word', $this->config['timestamp_granularities']) ? 'enabled' : 'disabled'
        );
    }
}