<?php

namespace Controllers;

use Services\ParaphraseService;
use Services\TranscriptionService;
use Utils\ResponseUtils;

/**
 * Contrôleur pour la paraphrase de texte
 */
class ParaphraseController
{
    /**
     * @var ParaphraseService
     */
    private $paraphraseService;

    /**
     * @var TranscriptionService
     */
    private $transcriptionService;

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->paraphraseService = new ParaphraseService();
        $this->transcriptionService = new TranscriptionService();
    }

    /**
     * Traite une requête de paraphrase de texte
     */
    public function handleParaphrase()
    {
        // Vérifier si un texte ou un ID de résultat a été fourni
        if ((!isset($_POST['text']) || empty($_POST['text'])) && (!isset($_POST['result_id']) || empty($_POST['result_id']))) {
            ResponseUtils::redirectWithError('paraphrase', 'Aucun texte ou résultat de transcription fourni');
        }

        // Récupérer les paramètres
        $text = $_POST['text'] ?? '';
        $resultId = $_POST['result_id'] ?? '';
        $style = $_POST['style'] ?? 'standard';
        $language = $_POST['language'] ?? 'fr';

        // Paraphraser le texte
        $result = [];
        if (!empty($resultId)) {
            // Paraphraser à partir d'un résultat de transcription
            $result = $this->paraphraseService->paraphraseTranscription($resultId, $style, $language);
        } else {
            // Paraphraser le texte fourni
            $result = $this->paraphraseService->paraphraseText($text, $style, $language);
        }

        // Vérifier si la paraphrase a réussi
        if (!$result['success']) {
            ResponseUtils::redirectWithError('paraphrase', $result['error']);
        }

        // Retourner le résultat pour l'affichage
        return $result;
    }

    /**
     * Récupère les styles de paraphrase disponibles
     * 
     * @return array Liste des styles disponibles
     */
    public function getAvailableStyles()
    {
        return $this->paraphraseService->getAvailableStyles();
    }

    /**
     * Récupère les langues disponibles pour la paraphrase
     * 
     * @return array Liste des langues disponibles
     */
    public function getAvailableLanguages()
    {
        return [
            'fr' => 'Français',
            'en' => 'Anglais',
            'es' => 'Espagnol',
            'de' => 'Allemand',
            'it' => 'Italien',
            'pt' => 'Portugais',
            'ru' => 'Russe'
        ];
    }

    /**
     * Affiche le formulaire de paraphrase pour un résultat de transcription
     * 
     * @return array Données pour l'affichage du formulaire
     */
    public function showParaphraseForm()
    {
        // Vérifier si un ID de résultat a été fourni
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            ResponseUtils::redirectWithError('missing_id');
        }

        $resultId = $_GET['id'];

        // Récupérer le résultat
        $result = $this->transcriptionService->getTranscriptionResult($resultId);

        // Vérifier si le résultat a été trouvé
        if (!$result['success']) {
            ResponseUtils::redirectWithError('result_not_found');
        }

        // Préparer les données pour l'affichage
        return [
            'result_id' => $resultId,
            'text' => $result['text'],
            'styles' => $this->getAvailableStyles(),
            'languages' => $this->getAvailableLanguages()
        ];
    }
}
