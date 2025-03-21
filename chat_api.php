<?php

/**
 * API pour interagir avec ChatGPT en utilisant le contexte de transcription
 */

require_once 'context_manager.php';

class ChatAPI
{
    private $apiKey;
    private $model = 'gpt-3.5-turbo';

    public function __construct($apiKey = null)
    {
        // Utiliser la clé API fournie ou celle de la configuration
        $this->apiKey = $apiKey ?: OPENAI_API_KEY;
    }

    public function sendMessage($message, $history = [])
    {
        // Récupérer le contexte actuel
        $contextManager = ContextManager::getInstance();
        $context = $contextManager->getContext();

        // Construire le message système avec le contexte
        $systemMessage = "Vous êtes un assistant utile qui a accès au contenu récemment transcrit et traduit.\n\n";

        if (!empty($context['transcription'])) {
            $systemMessage .= "Contenu transcrit:\n" . $context['transcription'] . "\n\n";
        }

        if (!empty($context['translation'])) {
            $systemMessage .= "Traduction:\n" . $context['translation'] . "\n\n";
        }

        $systemMessage .= "Utilisez ce contexte pour répondre aux questions de l'utilisateur de manière pertinente et informative.";

        // Construire les messages pour l'API
        $messages = [
            ['role' => 'system', 'content' => $systemMessage]
        ];

        // Ajouter l'historique des conversations
        foreach ($history as $exchange) {
            $messages[] = ['role' => 'user', 'content' => $exchange[0]];
            if (isset($exchange[1])) {
                $messages[] = ['role' => 'assistant', 'content' => $exchange[1]];
            }
        }

        // Ajouter le message actuel
        $messages[] = ['role' => 'user', 'content' => $message];

        // Appeler l'API OpenAI
        $response = $this->callOpenAI($messages);

        return $response;
    }

    private function callOpenAI($messages)
    {
        $url = 'https://api.openai.com/v1/chat/completions';

        $data = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 1000
        ];

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return "Erreur lors de la communication avec ChatGPT : " . $error;
        }

        $responseData = json_decode($response, true);

        if (isset($responseData['choices'][0]['message']['content'])) {
            return $responseData['choices'][0]['message']['content'];
        } else {
            return "Erreur : Réponse inattendue de l'API";
        }
    }
}
