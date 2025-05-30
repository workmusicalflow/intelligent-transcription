<?php

namespace Domain\Transcription\Service;

use Domain\Transcription\ValueObject\AudioFile;

/**
 * Interface pour le preprocessing des fichiers audio
 * Permet de préparer les fichiers audio avant transcription (conversion, compression, etc.)
 */
interface AudioPreprocessorInterface
{
    /**
     * Prétraite un fichier audio pour la transcription
     * 
     * @param AudioFile $audioFile Le fichier audio original
     * @return AudioFile Le fichier audio prétraité
     * @throws AudioPreprocessingFailedException Si le preprocessing échoue
     */
    public function preprocess(AudioFile $audioFile): AudioFile;
    
    /**
     * Vérifie si un fichier audio nécessite un preprocessing
     * 
     * @param AudioFile $audioFile Le fichier audio à vérifier
     * @return bool True si le preprocessing est nécessaire
     */
    public function needsPreprocessing(AudioFile $audioFile): bool;
    
    /**
     * Nettoie les fichiers temporaires créés pendant le preprocessing
     * 
     * @param string $preprocessedPath Le chemin du fichier prétraité
     * @return void
     */
    public function cleanup(string $preprocessedPath): void;
    
    /**
     * Obtient les formats de sortie supportés
     * 
     * @return array Les formats supportés (wav, mp3, etc.)
     */
    public function getSupportedOutputFormats(): array;
}