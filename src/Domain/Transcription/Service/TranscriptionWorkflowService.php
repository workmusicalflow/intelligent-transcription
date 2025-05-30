<?php

namespace Domain\Transcription\Service;

use Domain\Transcription\Entity\Transcription;
use Domain\Transcription\ValueObject\AudioFile;
use Domain\Transcription\ValueObject\Language;
use Domain\Transcription\Exception\TranscriptionFailedException;

/**
 * Service d'orchestration du workflow de transcription
 * Ce service coordonne les différentes étapes du processus de transcription
 */
class TranscriptionWorkflowService
{
    private TranscriberInterface $transcriber;
    private AudioPreprocessorInterface $preprocessor;
    private TranscriptionPricingService $pricingService;
    
    public function __construct(
        TranscriberInterface $transcriber,
        AudioPreprocessorInterface $preprocessor,
        TranscriptionPricingService $pricingService
    ) {
        $this->transcriber = $transcriber;
        $this->preprocessor = $preprocessor;
        $this->pricingService = $pricingService;
    }
    
    /**
     * Exécute le workflow complet de transcription
     */
    public function processTranscription(Transcription $transcription): void
    {
        try {
            // 1. Validation du fichier audio
            $this->validateAudioFile($transcription->audioFile());
            
            // 2. Démarrer le traitement
            $transcription->startProcessing();
            
            // 3. Preprocessing si nécessaire
            $audioFile = $transcription->audioFile();
            if ($this->shouldPreprocess($audioFile)) {
                $preprocessedFile = $this->preprocessor->preprocess($audioFile);
                $transcription->startProcessing($preprocessedFile->path());
                $audioFile = $preprocessedFile;
            }
            
            // 4. Transcription
            $result = $this->transcriber->transcribe(
                $audioFile,
                $transcription->language()
            );
            
            // 5. Compléter la transcription
            $transcription->complete(
                $result->text(),
                [
                    'confidence' => $result->confidence(),
                    'detected_language' => $result->detectedLanguage()->code(),
                    'transcriber' => $this->transcriber->getName(),
                    'metadata' => $result->metadata()
                ]
            );
            
        } catch (TranscriptionFailedException $e) {
            $transcription->fail(
                $e->getMessage(),
                $e->getErrorCode() ?? 'TRANSCRIPTION_FAILED',
                $e->getContext()
            );
            throw $e;
        } catch (\Exception $e) {
            $transcription->fail(
                'Unexpected error during transcription: ' . $e->getMessage(),
                'UNEXPECTED_ERROR',
                ['exception' => get_class($e)]
            );
            throw new TranscriptionFailedException(
                'Transcription workflow failed',
                'WORKFLOW_FAILED',
                ['original_exception' => $e->getMessage()],
                0,
                $e
            );
        }
    }
    
    /**
     * Valide qu'un fichier audio peut être transcrit
     */
    private function validateAudioFile(AudioFile $audioFile): void
    {
        if (!$audioFile->isValid()) {
            throw TranscriptionFailedException::unsupportedFormat(
                $audioFile->extension(),
                AudioFile::getSupportedFormats()
            );
        }
        
        if (!$this->transcriber->supportsFormat($audioFile->extension())) {
            throw TranscriptionFailedException::unsupportedFormat(
                $audioFile->extension(),
                ['whisper supported formats']
            );
        }
        
        if ($audioFile->size() > $this->transcriber->getMaxFileSizeSupported()) {
            throw TranscriptionFailedException::audioTooLarge(
                $audioFile->size(),
                $this->transcriber->getMaxFileSizeSupported()
            );
        }
    }
    
    /**
     * Détermine si un fichier audio nécessite un preprocessing
     */
    private function shouldPreprocess(AudioFile $audioFile): bool
    {
        // Preprocessing pour certains formats ou si le fichier est trop gros
        return $audioFile->needsPreprocessing() || 
               $audioFile->size() > 10 * 1024 * 1024; // 10MB
    }
    
    /**
     * Calcule le prix d'une transcription
     */
    public function calculatePrice(
        AudioFile $audioFile,
        Language $language,
        bool $isPriority = false
    ): Money {
        return $this->pricingService->calculatePrice(
            $audioFile,
            $language,
            $isPriority
        );
    }
}