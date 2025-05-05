<?php

namespace Services;

/**
 * Service pour la paraphrase de texte
 */
class ParaphraseService
{
    /**
     * Paraphrase un texte en utilisant l'API OpenAI
     * 
     * @param string $text Texte à paraphraser
     * @param string $style Style de paraphrase (simple, formel, créatif, etc.)
     * @param string $language Langue de sortie
     * @return array Résultat de la paraphrase
     */
    public function paraphraseText($text, $style = 'standard', $language = 'fr')
    {
        // Valider le texte à paraphraser
        $textValidation = \Utils\ValidationUtils::validateTextMessage($text, [
            'min_length' => 10,  // Minimum 10 caractères pour une paraphrase sensée
            'max_length' => 20000,  // Maximum 20000 caractères pour éviter les abus
            'strip_tags' => true
        ]);
        
        if (!$textValidation['valid']) {
            return [
                'success' => false,
                'error' => $textValidation['error'],
                'category' => 'validation',
                'advice' => 'Le texte doit contenir entre 10 et 20000 caractères pour être paraphrasé correctement.'
            ];
        }
        
        // Récupérer le texte nettoyé
        $text = $textValidation['sanitized'];
        
        // Valider les paramètres de paraphrase
        $paramsValidation = \Utils\ValidationUtils::validateParaphraseParams([
            'style' => $style,
            'language' => $language
        ]);
        
        if (!$paramsValidation['valid']) {
            return [
                'success' => false,
                'error' => $paramsValidation['error'],
                'category' => 'validation',
                'advice' => 'Veuillez vérifier les paramètres de paraphrase fournis.'
            ];
        }
        
        // Récupérer les paramètres validés
        $style = $paramsValidation['sanitized']['style'];
        $language = $paramsValidation['sanitized']['language'];

        // Exécuter le script Python de paraphrase
        $pythonPath = PYTHON_PATH;
        $scriptPath = BASE_DIR . '/paraphrase.py';

        // Créer un fichier temporaire pour le texte d'entrée
        $inputFile = tempnam(sys_get_temp_dir(), 'paraphrase_input_');
        file_put_contents($inputFile, $text);

        // Créer un fichier temporaire pour le résultat
        $outputFile = tempnam(sys_get_temp_dir(), 'paraphrase_output_');

        $command = escapeshellcmd($pythonPath) . ' ' .
            escapeshellarg($scriptPath) . ' ' .
            '--input=' . escapeshellarg($inputFile) . ' ' .
            '--output=' . escapeshellarg($outputFile) . ' ' .
            '--style=' . escapeshellarg($style) . ' ' .
            '--language=' . escapeshellarg($language);

        // Utiliser notre utilitaire d'exécution Python
        $result = \Utils\PythonErrorUtils::executePythonProcess($command, 'paraphrase', $outputFile);
        
        // Nettoyer les fichiers temporaires
        @unlink($inputFile);
        @unlink($outputFile);
        
        // Si l'opération a échoué, retourner l'erreur avec des informations utiles
        if (!isset($result['success']) || !$result['success']) {
            // Pour les erreurs de paraphrase, ajouter des conseils spécifiques
            if (!isset($result['advice'])) {
                if (mb_strlen($text) > 10000) {
                    $result['advice'] = "Le texte est peut-être trop long. Essayez de le diviser en sections plus petites.";
                } else {
                    $result['advice'] = "Essayez un style de paraphrase différent ou reformulez certaines parties du texte.";
                }
            }
            return $result;
        }

        return $result;
    }

    /**
     * Paraphrase un texte à partir d'un ID de résultat de transcription
     * 
     * @param string $resultId ID du résultat de transcription
     * @param string $style Style de paraphrase (simple, formel, créatif, etc.)
     * @param string $language Langue de sortie
     * @return array Résultat de la paraphrase
     */
    public function paraphraseTranscription($resultId, $style = 'standard', $language = 'fr')
    {
        // Récupérer le résultat de transcription
        $transcriptionService = new TranscriptionService();
        $transcriptionResult = $transcriptionService->getTranscriptionResult($resultId);

        if (!$transcriptionResult['success']) {
            return [
                'success' => false,
                'error' => $transcriptionResult['error']
            ];
        }

        // Paraphraser le texte
        $text = $transcriptionResult['text'];
        return $this->paraphraseText($text, $style, $language);
    }

    /**
     * Récupère les styles de paraphrase disponibles
     * 
     * @return array Liste des styles disponibles
     */
    public function getAvailableStyles()
    {
        return [
            'standard' => 'Standard',
            'simple' => 'Simplifié',
            'formel' => 'Formel',
            'academique' => 'Académique',
            'creatif' => 'Créatif',
            'professionnel' => 'Professionnel',
            'concis' => 'Concis'
        ];
    }
}
