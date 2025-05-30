<?php

namespace Domain\Transcription\Service;

use Domain\Transcription\ValueObject\AudioFile;
use Domain\Transcription\ValueObject\Language;
use Domain\Transcription\ValueObject\TranscribedText;

/**
 * Interface pour les services de transcription
 * Cette interface définit le contrat pour tout service capable de transcrire de l'audio
 */
interface TranscriberInterface
{
    /**
     * Transcrit un fichier audio en texte
     * 
     * @param AudioFile $audioFile Le fichier audio à transcrire
     * @param Language|null $language La langue cible (null pour détection automatique)
     * @return TranscriptionResult Le résultat de la transcription
     * @throws TranscriptionFailedException Si la transcription échoue
     */
    public function transcribe(AudioFile $audioFile, ?Language $language = null): TranscriptionResult;
    
    /**
     * Détecte la langue d'un fichier audio
     * 
     * @param AudioFile $audioFile Le fichier audio à analyser
     * @return Language La langue détectée
     * @throws LanguageDetectionFailedException Si la détection échoue
     */
    public function detectLanguage(AudioFile $audioFile): Language;
    
    /**
     * Vérifie si le service supporte un format audio spécifique
     * 
     * @param string $format Le format audio (mp3, wav, etc.)
     * @return bool True si le format est supporté
     */
    public function supportsFormat(string $format): bool;
    
    /**
     * Obtient la durée maximale supportée en secondes
     * 
     * @return int La durée maximale en secondes
     */
    public function getMaxDurationSupported(): int;
    
    /**
     * Obtient la taille maximale de fichier supportée en octets
     * 
     * @return int La taille maximale en octets
     */
    public function getMaxFileSizeSupported(): int;
    
    /**
     * Obtient le nom du service de transcription
     * 
     * @return string Le nom du service
     */
    public function getName(): string;
}