<?php

namespace Services;

/**
 * Service pour le chat contextuel
 */
class ChatService
{
    /**
     * Chemin du fichier d'export
     * 
     * @var string
     */
    private $exportDir;

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->exportDir = EXPORT_DIR;

        // Créer le répertoire d'export si nécessaire
        if (!is_dir($this->exportDir)) {
            mkdir($this->exportDir, 0777, true);
        }
    }

    /**
     * Envoie un message au modèle de langage et récupère la réponse
     * 
     * @param string $message Message à envoyer
     * @param array $context Contexte de la conversation
     * @param string $transcriptionId ID de la transcription associée (optionnel)
     * @return array Résultat de la conversation
     */
    public function sendMessage($message, $context = [], $transcriptionId = null)
    {
        // Vérifier si le message est vide
        if (empty($message)) {
            return [
                'success' => false,
                'error' => 'Le message est vide'
            ];
        }

        // Récupérer le contexte de transcription si un ID est fourni
        $transcriptionContext = '';
        if ($transcriptionId) {
            $transcriptionService = new TranscriptionService();
            $transcriptionResult = $transcriptionService->getTranscriptionResult($transcriptionId);

            if ($transcriptionResult['success']) {
                $transcriptionContext = $transcriptionResult['text'];
            }
        }

        // Exécuter le script Python de chat
        $pythonPath = PYTHON_PATH;
        $scriptPath = BASE_DIR . '/chat_api.py';

        // Créer un fichier temporaire pour le message
        $messageFile = tempnam(sys_get_temp_dir(), 'chat_message_');
        file_put_contents($messageFile, $message);

        // Créer un fichier temporaire pour le contexte
        $contextFile = tempnam(sys_get_temp_dir(), 'chat_context_');
        file_put_contents($contextFile, json_encode([
            'messages' => $context,
            'transcription' => $transcriptionContext
        ]));

        // Créer un fichier temporaire pour le résultat
        $outputFile = tempnam(sys_get_temp_dir(), 'chat_output_');

        $command = escapeshellcmd($pythonPath) . ' ' .
            escapeshellarg($scriptPath) . ' ' .
            '--message=' . escapeshellarg($messageFile) . ' ' .
            '--context=' . escapeshellarg($contextFile) . ' ' .
            '--output=' . escapeshellarg($outputFile);

        // Exécuter la commande et capturer la sortie standard et d'erreur
        $descriptorspec = array(
            0 => array("pipe", "r"),  // stdin
            1 => array("pipe", "w"),  // stdout
            2 => array("pipe", "w")   // stderr
        );

        $process = proc_open($command, $descriptorspec, $pipes);

        if (!is_resource($process)) {
            // Nettoyer les fichiers temporaires
            @unlink($messageFile);
            @unlink($contextFile);
            @unlink($outputFile);

            return [
                'success' => false,
                'error' => "Impossible de démarrer le processus de chat"
            ];
        }

        // Lire la sortie standard
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        // Lire la sortie d'erreur
        $error_output = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        // Fermer le processus
        $return_value = proc_close($process);

        // Lire le résultat
        $result = [];
        if (file_exists($outputFile)) {
            $resultContent = file_get_contents($outputFile);
            $result = json_decode($resultContent, true);
        }

        // Nettoyer les fichiers temporaires
        @unlink($messageFile);
        @unlink($contextFile);
        @unlink($outputFile);

        if (!$result || !isset($result['success']) || !$result['success']) {
            return [
                'success' => false,
                'error' => $result['error'] ?? 'Erreur inconnue lors du chat'
            ];
        }

        return $result;
    }

    /**
     * Exporte une conversation
     * 
     * @param array $messages Messages de la conversation
     * @return array Résultat de l'export
     */
    public function exportConversation($messages)
    {
        if (empty($messages)) {
            return [
                'success' => false,
                'error' => 'Aucun message à exporter'
            ];
        }

        // Générer un nom de fichier unique
        $filename = 'chat_export_' . date('Ymd_His') . '.txt';
        $filePath = $this->exportDir . '/' . $filename;

        // Formater la conversation
        $content = "# Conversation exportée le " . date('d/m/Y à H:i:s') . "\n\n";

        foreach ($messages as $message) {
            $role = $message['role'] === 'user' ? 'Vous' : 'Assistant';
            $content .= "## $role\n\n";
            $content .= $message['content'] . "\n\n";
        }

        // Enregistrer le fichier
        if (file_put_contents($filePath, $content) === false) {
            return [
                'success' => false,
                'error' => 'Impossible d\'enregistrer le fichier d\'export'
            ];
        }

        return [
            'success' => true,
            'file_path' => $filePath,
            'file_name' => $filename
        ];
    }
}
