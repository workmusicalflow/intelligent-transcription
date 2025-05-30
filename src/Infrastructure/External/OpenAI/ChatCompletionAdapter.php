<?php

namespace Infrastructure\External\OpenAI;

use Infrastructure\External\OpenAI\OpenAIClient;
use Domain\Common\ValueObject\Money;
use Exception;

/**
 * Adapter OpenAI Chat Completion pour les conversations
 */
class ChatCompletionAdapter
{
    private OpenAIClient $client;
    private string $model;
    private array $defaultOptions;
    private array $tokenPricing;
    
    public function __construct(
        OpenAIClient $client,
        string $model = 'gpt-4o-mini'
    ) {
        $this->client = $client;
        $this->model = $model;
        $this->defaultOptions = [
            'temperature' => 0.7,
            'max_tokens' => 4000,
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0
        ];
        
        // Prix par 1K tokens (approximatifs)
        $this->tokenPricing = [
            'gpt-4o-mini' => ['input' => 0.00015, 'output' => 0.0006],
            'gpt-4o' => ['input' => 0.005, 'output' => 0.015],
            'gpt-3.5-turbo' => ['input' => 0.0005, 'output' => 0.0015]
        ];
    }
    
    public function complete(array $messages, array $options = []): array
    {
        try {
            $requestData = [
                'model' => $this->model,
                'messages' => $messages,
                ...$this->defaultOptions,
                ...$options
            ];
            
            $response = $this->client->post('chat/completions', $requestData);
            
            if (!$response->isSuccessful()) {
                throw new Exception("Chat Completion Error: " . $response->getError());
            }
            
            return $this->parseResponse($response->getData());
            
        } catch (Exception $e) {
            throw new Exception("Chat completion failed: " . $e->getMessage());
        }
    }
    
    public function summarizeText(string $text, string $language = 'fr'): array
    {
        $prompt = $this->buildSummaryPrompt($text, $language);
        
        $messages = [
            [
                'role' => 'system',
                'content' => 'Tu es un assistant spécialisé dans la création de résumés clairs et concis.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ];
        
        return $this->complete($messages, ['max_tokens' => 1000]);
    }
    
    public function paraphraseText(string $text, string $tone = 'professional', string $language = 'fr'): array
    {
        $prompt = $this->buildParaphrasePrompt($text, $tone, $language);
        
        $messages = [
            [
                'role' => 'system',
                'content' => 'Tu es un assistant expert en réécriture et paraphrase de texte.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ];
        
        return $this->complete($messages, ['max_tokens' => 2000]);
    }
    
    public function answerQuestion(string $question, string $context, string $language = 'fr'): array
    {
        $prompt = $this->buildQuestionPrompt($question, $context, $language);
        
        $messages = [
            [
                'role' => 'system',
                'content' => 'Tu es un assistant qui répond aux questions basées sur un contexte fourni.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ];
        
        return $this->complete($messages);
    }
    
    public function estimateCost(array $messages): Money
    {
        $inputTokens = $this->estimateTokens($messages);
        $outputTokens = 500; // Estimation moyenne
        
        $pricing = $this->tokenPricing[$this->model] ?? $this->tokenPricing['gpt-4o-mini'];
        
        $inputCost = ($inputTokens / 1000) * $pricing['input'];
        $outputCost = ($outputTokens / 1000) * $pricing['output'];
        
        return Money::fromAmount($inputCost + $outputCost, 'USD');
    }
    
    private function parseResponse(array $data): array
    {
        $choice = $data['choices'][0] ?? null;
        $usage = $data['usage'] ?? [];
        
        if (!$choice) {
            throw new Exception("No response choice found");
        }
        
        return [
            'content' => $choice['message']['content'] ?? '',
            'role' => $choice['message']['role'] ?? 'assistant',
            'finish_reason' => $choice['finish_reason'] ?? null,
            'usage' => [
                'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
                'completion_tokens' => $usage['completion_tokens'] ?? 0,
                'total_tokens' => $usage['total_tokens'] ?? 0
            ],
            'model' => $data['model'] ?? $this->model,
            'cost' => $this->calculateActualCost($usage)
        ];
    }
    
    private function buildSummaryPrompt(string $text, string $language): string
    {
        $instructions = [
            'fr' => 'Résume le texte suivant en 3-5 points clés essentiels',
            'en' => 'Summarize the following text in 3-5 key essential points'
        ];
        
        $instruction = $instructions[$language] ?? $instructions['fr'];
        
        return "{$instruction}:\n\n{$text}\n\nRésumé:";
    }
    
    private function buildParaphrasePrompt(string $text, string $tone, string $language): string
    {
        $toneInstructions = [
            'professional' => 'style professionnel et formel',
            'casual' => 'style décontracté et accessible',
            'academic' => 'style académique et précis',
            'creative' => 'style créatif et original'
        ];
        
        $toneDesc = $toneInstructions[$tone] ?? $toneInstructions['professional'];
        
        return "Réécris le texte suivant avec un {$toneDesc}, en gardant le même sens:\n\n{$text}\n\nTexte réécrit:";
    }
    
    private function buildQuestionPrompt(string $question, string $context, string $language): string
    {
        return "Contexte:\n{$context}\n\nQuestion: {$question}\n\nRéponds en te basant uniquement sur le contexte fourni:";
    }
    
    private function estimateTokens(array $messages): int
    {
        $totalText = '';
        foreach ($messages as $message) {
            $totalText .= $message['content'] ?? '';
        }
        
        // Estimation approximative : 1 token ≈ 4 caractères
        return (int) ceil(strlen($totalText) / 4);
    }
    
    private function calculateActualCost(array $usage): Money
    {
        if (empty($usage)) {
            return Money::fromAmount(0, 'USD');
        }
        
        $pricing = $this->tokenPricing[$this->model] ?? $this->tokenPricing['gpt-4o-mini'];
        
        $inputCost = ($usage['prompt_tokens'] / 1000) * $pricing['input'];
        $outputCost = ($usage['completion_tokens'] / 1000) * $pricing['output'];
        
        return Money::fromAmount($inputCost + $outputCost, 'USD');
    }
    
    public function setModel(string $model): void
    {
        $this->model = $model;
    }
    
    public function setOptions(array $options): void
    {
        $this->defaultOptions = array_merge($this->defaultOptions, $options);
    }
    
    public function getStats(): array
    {
        return [
            'model' => $this->model,
            'default_options' => $this->defaultOptions,
            'supported_models' => array_keys($this->tokenPricing),
            'pricing' => $this->tokenPricing[$this->model] ?? null
        ];
    }
}