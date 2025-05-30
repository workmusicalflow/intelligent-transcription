<?php

namespace Infrastructure\External\OpenAI;

use Domain\Analytics\Service\SummarizerInterface;
use Domain\Transcription\ValueObject\TranscribedText;
use Domain\Analytics\ValueObject\Summary;
use Infrastructure\External\ApiClientInterface;
use Infrastructure\External\ApiResponse;
use Exception;

/**
 * Adaptateur pour GPT d'OpenAI pour la génération de résumés
 */
class GPTSummaryAdapter implements SummarizerInterface
{
    private string $apiKey;
    private ?ApiClientInterface $httpClient;
    private string $apiUrl = 'https://api.openai.com/v1/chat/completions';
    private string $model = 'gpt-3.5-turbo';
    
    public function __construct(string $apiKey, ?ApiClientInterface $httpClient = null)
    {
        $this->apiKey = $apiKey;
        $this->httpClient = $httpClient;
    }
    
    public function summarize(TranscribedText $text, array $options = []): Summary
    {
        try {
            $prompt = $this->buildPrompt($text, $options);
            $response = $this->callOpenAI($prompt);
            
            if (!isset($response['choices'][0]['message']['content'])) {
                throw new Exception('Invalid response from OpenAI API');
            }
            
            $summaryContent = $response['choices'][0]['message']['content'];
            
            return Summary::create($summaryContent, $this->extractKeyPoints($summaryContent));
            
        } catch (Exception $e) {
            throw new Exception('Failed to generate summary: ' . $e->getMessage(), 0, $e);
        }
    }
    
    private function buildPrompt(TranscribedText $text, array $options): string
    {
        $language = $options['language'] ?? 'français';
        $maxWords = $options['max_words'] ?? 200;
        
        $systemPrompt = "Tu es un assistant expert en création de résumés concis et pertinents.";
        
        $userPrompt = sprintf(
            "Résume le texte suivant en %s, en maximum %d mots. 
            Identifie les points clés principaux et structure le résumé de manière claire.
            
            Texte à résumer:
            %s",
            $language,
            $maxWords,
            $text->content()
        );
        
        return json_encode([
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt]
            ],
            'temperature' => 0.7,
            'max_tokens' => 1000
        ]);
    }
    
    private function callOpenAI(string $prompt): array
    {
        if ($this->httpClient) {
            $response = $this->httpClient->post($this->apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json'
                ],
                'body' => $prompt
            ]);
            
            if (!$response->isSuccessful()) {
                throw new Exception('OpenAI API error: ' . $response->getBody());
            }
            
            return $response->getData();
        }
        
        // Fallback avec cURL si pas de client HTTP injecté
        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $prompt,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json'
            ]
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('OpenAI API error: ' . $result);
        }
        
        return json_decode($result, true);
    }
    
    private function extractKeyPoints(string $summaryContent): array
    {
        // Extraction simple des points clés basée sur les phrases
        $sentences = preg_split('/[.!?]+/', $summaryContent, -1, PREG_SPLIT_NO_EMPTY);
        $keyPoints = [];
        
        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (strlen($sentence) > 20) { // Ignorer les phrases très courtes
                $keyPoints[] = $sentence;
                if (count($keyPoints) >= 5) { // Limiter à 5 points clés
                    break;
                }
            }
        }
        
        return $keyPoints;
    }
}