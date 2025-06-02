<?php

namespace Tests\Unit\Translation;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test-Driven Development pour le service de traduction GPT-4o-mini
 * Focus: Anglais → Français avec préservation timestamps
 */
class GPTTranslationServiceTest extends TestCase
{
    private GPTTranslationService $translationService;
    private array $mockOpenAIClient;

    protected function setUp(): void
    {
        $this->markTestIncomplete('Service GPTTranslationService à implémenter');
        
        // Mock OpenAI client pour éviter les appels API réels en tests
        $this->mockOpenAIClient = $this->createMock(\OpenAI\Client::class);
        $this->translationService = new GPTTranslationService($this->mockOpenAIClient);
    }

    #[Test]
    public function it_should_translate_english_to_french_preserving_timestamps()
    {
        // Arrange - Données de test réalistes (basées sur nos transcriptions existantes)
        $englishSegments = [
            [
                'id' => 0,
                'text' => 'Hey everyone, hope you\'re doing well.',
                'startTime' => 0.0,
                'endTime' => 2.5,
                'words' => [
                    ['word' => 'Hey', 'start' => 0.0, 'end' => 0.42],
                    ['word' => 'everyone,', 'start' => 0.42, 'end' => 1.1],
                    ['word' => 'hope', 'start' => 1.1, 'end' => 1.4],
                    ['word' => 'you\'re', 'start' => 1.4, 'end' => 1.7],
                    ['word' => 'doing', 'start' => 1.7, 'end' => 2.0],
                    ['word' => 'well.', 'start' => 2.0, 'end' => 2.5]
                ]
            ],
            [
                'id' => 1,
                'text' => 'So I invite you to participate in the third edition.',
                'startTime' => 2.5,
                'endTime' => 6.2,
                'words' => [
                    ['word' => 'So', 'start' => 2.5, 'end' => 2.7],
                    ['word' => 'I', 'start' => 2.7, 'end' => 2.8],
                    ['word' => 'invite', 'start' => 2.8, 'end' => 3.3],
                    ['word' => 'you', 'start' => 3.3, 'end' => 3.5],
                    ['word' => 'to', 'start' => 3.5, 'end' => 3.6],
                    ['word' => 'participate', 'start' => 3.6, 'end' => 4.5],
                    ['word' => 'in', 'start' => 4.5, 'end' => 4.6],
                    ['word' => 'the', 'start' => 4.6, 'end' => 4.7],
                    ['word' => 'third', 'start' => 4.7, 'end' => 5.1],
                    ['word' => 'edition.', 'start' => 5.1, 'end' => 6.2]
                ]
            ]
        ];

        $expectedFrenchTranslation = [
            [
                'id' => 0,
                'text' => 'Salut tout le monde, j\'espère que vous allez bien.',
                'startTime' => 0.0,
                'endTime' => 2.5,
                'words' => $englishSegments[0]['words'], // Préservés pour doublage
                'translation_notes' => 'Natural greeting adapted to French'
            ],
            [
                'id' => 1,
                'text' => 'Alors je vous invite à participer à la troisième édition.',
                'startTime' => 2.5,
                'endTime' => 6.2,
                'words' => $englishSegments[1]['words'], // Préservés pour doublage
                'translation_notes' => 'Formal invitation maintained'
            ]
        ];

        // Mock de la réponse GPT-4o-mini
        $mockGPTResponse = (object) [
            'choices' => [
                (object) [
                    'message' => (object) [
                        'content' => json_encode($expectedFrenchTranslation)
                    ]
                ]
            ]
        ];

        $this->mockOpenAIClient->expects($this->once())
            ->method('chat->completions->create')
            ->willReturn($mockGPTResponse);

        // Act
        $result = $this->translationService->translateSegments(
            $englishSegments,
            'fr',
            new TranslationConfig(['preserve_timestamps' => true])
        );

        // Assert - Validation complète
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        
        // Vérifier préservation timestamps
        $this->assertEquals(0.0, $result[0]['startTime']);
        $this->assertEquals(2.5, $result[0]['endTime']);
        $this->assertEquals(2.5, $result[1]['startTime']);
        $this->assertEquals(6.2, $result[1]['endTime']);
        
        // Vérifier qualité traduction française
        $this->assertStringContains('Salut tout le monde', $result[0]['text']);
        $this->assertStringContains('j\'espère', $result[0]['text']);
        $this->assertStringContains('Alors je vous invite', $result[1]['text']);
        $this->assertStringContains('troisième édition', $result[1]['text']);
        
        // Vérifier préservation word-level data pour doublage
        $this->assertArrayHasKey('words', $result[0]);
        $this->assertArrayHasKey('words', $result[1]);
        $this->assertCount(6, $result[0]['words']);
        $this->assertCount(10, $result[1]['words']);
    }

    #[Test]
    public function it_should_adapt_translation_length_for_dubbing_synchronization()
    {
        // Arrange - Segment court avec contrainte temporelle stricte
        $shortSegment = [
            'id' => 0,
            'text' => 'Yes.',
            'startTime' => 0.0,
            'endTime' => 0.8,
            'words' => [
                ['word' => 'Yes.', 'start' => 0.0, 'end' => 0.8]
            ]
        ];

        $adaptedTranslation = [
            [
                'id' => 0,
                'text' => 'Oui.',  // Adaptation courte pour timing
                'startTime' => 0.0,
                'endTime' => 0.8,
                'words' => $shortSegment['words'],
                'translation_notes' => 'Shortened for timing constraint'
            ]
        ];

        $mockResponse = (object) [
            'choices' => [(object) ['message' => (object) ['content' => json_encode($adaptedTranslation)]]]
        ];

        $this->mockOpenAIClient->method('chat->completions->create')->willReturn($mockResponse);

        // Act
        $result = $this->translationService->translateSegments(
            [$shortSegment],
            'fr',
            new TranslationConfig(['strict_timing' => true])
        );

        // Assert
        $this->assertEquals('Oui.', $result[0]['text']);
        $this->assertLessThanOrEqual(0.8, $result[0]['endTime'] - $result[0]['startTime']);
    }

    #[Test]
    public function it_should_preserve_character_names_and_technical_terms()
    {
        // Arrange - Dialogue avec noms propres et termes techniques
        $technicalSegment = [
            'id' => 0,
            'text' => 'Marie, the echography shows a clear diagnostic.',
            'startTime' => 0.0,
            'endTime' => 3.5,
            'words' => [
                ['word' => 'Marie,', 'start' => 0.0, 'end' => 0.6],
                ['word' => 'the', 'start' => 0.6, 'end' => 0.8],
                ['word' => 'echography', 'start' => 0.8, 'end' => 1.8],
                ['word' => 'shows', 'start' => 1.8, 'end' => 2.2],
                ['word' => 'a', 'start' => 2.2, 'end' => 2.3],
                ['word' => 'clear', 'start' => 2.3, 'end' => 2.7],
                ['word' => 'diagnostic.', 'start' => 2.7, 'end' => 3.5]
            ]
        ];

        $preservedTranslation = [
            [
                'id' => 0,
                'text' => 'Marie, l\'échographie montre un diagnostic clair.',
                'startTime' => 0.0,
                'endTime' => 3.5,
                'words' => $technicalSegment['words'],
                'translation_notes' => 'Character name and medical terms preserved'
            ]
        ];

        $mockResponse = (object) [
            'choices' => [(object) ['message' => (object) ['content' => json_encode($preservedTranslation)]]]
        ];

        $this->mockOpenAIClient->method('chat->completions->create')->willReturn($mockResponse);

        // Act
        $result = $this->translationService->translateSegments(
            [$technicalSegment],
            'fr',
            new TranslationConfig([
                'character_names' => ['Marie'],
                'technical_terms' => ['echography', 'diagnostic']
            ])
        );

        // Assert
        $this->assertStringContains('Marie', $result[0]['text']);
        $this->assertStringContains('échographie', $result[0]['text']);
        $this->assertStringContains('diagnostic', $result[0]['text']);
    }

    #[Test]
    public function it_should_handle_emotional_context_in_translation()
    {
        // Arrange - Segment avec contexte émotionnel
        $emotionalSegment = [
            'id' => 0,
            'text' => 'I\'m so worried about the results!',
            'startTime' => 0.0,
            'endTime' => 2.8,
            'words' => [
                ['word' => 'I\'m', 'start' => 0.0, 'end' => 0.3],
                ['word' => 'so', 'start' => 0.3, 'end' => 0.6],
                ['word' => 'worried', 'start' => 0.6, 'end' => 1.2],
                ['word' => 'about', 'start' => 1.2, 'end' => 1.5],
                ['word' => 'the', 'start' => 1.5, 'end' => 1.6],
                ['word' => 'results!', 'start' => 1.6, 'end' => 2.8]
            ]
        ];

        $emotionalTranslation = [
            [
                'id' => 0,
                'text' => 'Je suis si inquiète pour les résultats !',
                'startTime' => 0.0,
                'endTime' => 2.8,
                'words' => $emotionalSegment['words'],
                'translation_notes' => 'Emotional intensity preserved with appropriate French expression'
            ]
        ];

        $mockResponse = (object) [
            'choices' => [(object) ['message' => (object) ['content' => json_encode($emotionalTranslation)]]]
        ];

        $this->mockOpenAIClient->method('chat->completions->create')->willReturn($mockResponse);

        // Act
        $result = $this->translationService->translateSegments(
            [$emotionalSegment],
            'fr',
            new TranslationConfig(['emotional_context' => ['worried', 'anxious']])
        );

        // Assert
        $this->assertStringContains('inquiète', $result[0]['text']);
        $this->assertStringContains('!', $result[0]['text']); // Ponctuation émotionnelle préservée
    }

    public static function providesRealWorldTranslationScenarios(): array
    {
        return [
            'medical_dialogue' => [
                [
                    'text' => 'Doctor, what does this ultrasound show?',
                    'context' => ['medical', 'formal'],
                    'expected_french' => 'Docteur, que montre cette échographie ?'
                ]
            ],
            'casual_conversation' => [
                [
                    'text' => 'Hey guys, what\'s up?',
                    'context' => ['casual', 'friendly'],
                    'expected_french' => 'Salut les gars, ça va ?'
                ]
            ],
            'business_presentation' => [
                [
                    'text' => 'Let me present the quarterly results.',
                    'context' => ['formal', 'business'],
                    'expected_french' => 'Permettez-moi de présenter les résultats trimestriels.'
                ]
            ]
        ];
    }

    #[Test]
    #[DataProvider('providesRealWorldTranslationScenarios')]
    public function it_should_adapt_translation_style_to_context(array $scenario)
    {
        // Test paramétré pour différents contextes de traduction
        $this->markTestIncomplete('À implémenter avec les scénarios réels');
        
        // Ces tests valideront que notre service s'adapte intelligemment
        // au contexte (médical, casual, business, etc.)
    }

    #[Test]
    public function it_should_estimate_cost_accurately()
    {
        // Test de validation des coûts estimés
        $segments = [
            ['text' => 'Hello world', 'duration' => 1.5],
            ['text' => 'How are you doing today?', 'duration' => 2.8]
        ];

        $estimatedCost = $this->translationService->estimateCost($segments, 'fr');

        // Basé sur GPT-4o-mini pricing: ~$0.075/1M tokens
        $expectedTokens = 50; // Estimation pour ce texte court
        $expectedCost = ($expectedTokens / 1000000) * 0.075;

        $this->assertEqualsWithDelta($expectedCost, $estimatedCost, 0.001);
    }

    #[Test]
    public function it_should_fail_gracefully_on_api_errors()
    {
        // Test de robustesse
        $this->mockOpenAIClient->method('chat->completions->create')
            ->willThrowException(new \Exception('API Rate Limit'));

        $this->expectException(TranslationServiceException::class);
        $this->expectExceptionMessage('Translation API temporarily unavailable');

        $this->translationService->translateSegments(
            [['text' => 'Test', 'startTime' => 0, 'endTime' => 1]],
            'fr'
        );
    }

    #[Test]
    public function it_should_cache_similar_translations()
    {
        // Test du cache intelligent
        $identicalSegments = [
            ['text' => 'Hello', 'startTime' => 0, 'endTime' => 1],
            ['text' => 'Hello', 'startTime' => 5, 'endTime' => 6] // Même texte, timestamps différents
        ];

        // Le service ne devrait appeler l'API qu'une seule fois grâce au cache
        $this->mockOpenAIClient->expects($this->once())
            ->method('chat->completions->create');

        $this->translationService->translateSegments($identicalSegments, 'fr');
    }
}