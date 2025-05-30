<?php

/**
 * Test runner complet avec adaptateur PHPUnit
 */

// Charger l'adaptateur en premier
require_once __DIR__ . '/tests/TestAdapter.php';
require_once __DIR__ . '/src/autoload.php';

use Domain\Transcription\ValueObject\Language;
use Domain\Transcription\ValueObject\TranscribedText;
use Domain\Transcription\ValueObject\AudioFile;
use Domain\Transcription\ValueObject\TranscriptionStatus;
use Domain\Transcription\ValueObject\YouTubeMetadata;
use Domain\Transcription\Entity\Transcription;
use Domain\Transcription\Service\StandardPricingService;
use Domain\Transcription\Specification\TranscriptionByStatusSpecification;
use Domain\Transcription\Specification\TranscriptionByLanguageSpecification;
use Domain\Transcription\Specification\YouTubeTranscriptionSpecification;
use Domain\Common\ValueObject\Money;
use Domain\Common\ValueObject\UserId;
use Domain\Common\Exception\InvalidArgumentException;

class TestRunner {
    private $stats = [
        'total' => 0,
        'passed' => 0,
        'failed' => 0,
        'errors' => 0,
        'assertions' => 0,
        'time' => 0
    ];
    
    private $currentTest = '';
    
    public function run() {
        $startTime = microtime(true);
        
        echo "🧪 TESTS COMPLETS DU DOMAIN LAYER\n";
        echo str_repeat('=', 50) . "\n\n";
        
        // Exécuter tous les tests
        $this->runLanguageTests();
        $this->runTranscribedTextTests();
        $this->runAudioFileTests();
        $this->runMoneyTests();
        $this->runTranscriptionEntityTests();
        $this->runPricingServiceTests();
        $this->runSpecificationTests();
        $this->runCollectionTests();
        
        $this->stats['time'] = round(microtime(true) - $startTime, 3);
        $this->printSummary();
    }
    
    private function runTest($name, $callback) {
        $this->stats['total']++;
        $this->currentTest = $name;
        echo "  ▶ $name... ";
        
        try {
            $assertions = $this->stats['assertions'];
            $callback();
            $assertionCount = $this->stats['assertions'] - $assertions;
            echo "✅ ($assertionCount assertions)\n";
            $this->stats['passed']++;
        } catch (Exception $e) {
            echo "❌\n";
            echo "     └─ " . $e->getMessage() . "\n";
            if ($e instanceof AssertionError) {
                $this->stats['failed']++;
            } else {
                $this->stats['errors']++;
            }
        }
    }
    
    private function assert($condition, $message = '') {
        $this->stats['assertions']++;
        if (!$condition) {
            throw new AssertionError($message ?: "Assertion failed in {$this->currentTest}");
        }
    }
    
    private function assertEquals($expected, $actual, $message = '') {
        $this->stats['assertions']++;
        if ($expected != $actual) {
            throw new AssertionError(
                sprintf("Expected [%s], got [%s]. %s", 
                    var_export($expected, true), 
                    var_export($actual, true), 
                    $message
                )
            );
        }
    }
    
    private function runLanguageTests() {
        echo "📋 Language Value Object Tests\n";
        echo str_repeat('-', 30) . "\n";
        
        $this->runTest('Can create language from valid code', function() {
            $language = Language::fromCode('fr');
            $this->assertEquals('fr', $language->code());
            $this->assertEquals('Français', $language->name());
        });
        
        $this->runTest('Static factories work correctly', function() {
            $french = Language::FRENCH();
            $english = Language::ENGLISH();
            $spanish = Language::SPANISH();
            
            $this->assertEquals('fr', $french->code());
            $this->assertEquals('en', $english->code());
            $this->assertEquals('es', $spanish->code());
        });
        
        $this->runTest('Throws exception for invalid code', function() {
            try {
                Language::fromCode('invalid');
                $this->assert(false, 'Should throw exception');
            } catch (InvalidArgumentException $e) {
                $this->assert(true);
            }
        });
        
        $this->runTest('Language codes are case insensitive', function() {
            $lower = Language::fromCode('fr');
            $upper = Language::fromCode('FR');
            $this->assertEquals($lower->code(), $upper->code());
        });
        
        $this->runTest('Can identify complex languages', function() {
            $chinese = Language::fromCode('zh');
            $japanese = Language::fromCode('ja');
            $french = Language::fromCode('fr');
            
            $this->assert($chinese->isComplexLanguage());
            $this->assert($japanese->isComplexLanguage());
            $this->assert(!$french->isComplexLanguage());
        });
        
        $this->runTest('Language equality works', function() {
            $lang1 = Language::fromCode('fr');
            $lang2 = Language::fromCode('fr');
            $lang3 = Language::fromCode('en');
            
            $this->assert($lang1->equals($lang2));
            $this->assert(!$lang1->equals($lang3));
        });
        
        echo "\n";
    }
    
    private function runTranscribedTextTests() {
        echo "📋 TranscribedText Value Object Tests\n";
        echo str_repeat('-', 30) . "\n";
        
        $this->runTest('Can create with content', function() {
            $text = new TranscribedText('Hello world');
            $this->assertEquals('Hello world', $text->content());
            $this->assertEquals(2, $text->wordCount());
            $this->assertEquals(11, $text->characterCount());
        });
        
        $this->runTest('Throws exception for empty content', function() {
            try {
                new TranscribedText('');
                $this->assert(false, 'Should throw exception');
            } catch (InvalidArgumentException $e) {
                $this->assert(true);
            }
        });
        
        $this->runTest('Can create with segments', function() {
            $segments = [
                ['text' => 'Hello', 'start' => 0.0, 'end' => 1.0],
                ['text' => 'world', 'start' => 1.0, 'end' => 2.0]
            ];
            
            $text = new TranscribedText('Hello world', $segments);
            $this->assertEquals(2, count($text->segments()));
            $this->assertEquals(2.0, $text->duration());
        });
        
        $this->runTest('Excerpt truncates long text', function() {
            $longText = str_repeat('word ', 50);
            $text = new TranscribedText($longText);
            
            $excerpt = $text->excerpt(20);
            $this->assertEquals(23, strlen($excerpt)); // 20 + '...'
            $this->assert(str_ends_with($excerpt, '...'));
        });
        
        $this->runTest('Get segment at timestamp', function() {
            $segments = [
                ['text' => 'First', 'start' => 0.0, 'end' => 1.0],
                ['text' => 'Second', 'start' => 1.0, 'end' => 2.5],
                ['text' => 'Third', 'start' => 2.5, 'end' => 4.0]
            ];
            
            $text = new TranscribedText('First Second Third', $segments);
            
            $segment = $text->getSegmentAt(1.5);
            $this->assert($segment !== null);
            $this->assertEquals('Second', $segment['text']);
            
            $noSegment = $text->getSegmentAt(5.0);
            $this->assert($noSegment === null);
        });
        
        echo "\n";
    }
    
    private function runAudioFileTests() {
        echo "📋 AudioFile Value Object Tests\n";
        echo str_repeat('-', 30) . "\n";
        
        $this->runTest('Can create audio file', function() {
            $audioFile = AudioFile::create(
                '/tmp/test.mp3',
                'test.mp3',
                'audio/mpeg',
                1024 * 1024,
                120.5
            );
            
            $this->assertEquals('/tmp/test.mp3', $audioFile->path());
            $this->assertEquals('test.mp3', $audioFile->originalName());
            $this->assertEquals(1048576, $audioFile->size());
            $this->assertEquals(1.0, $audioFile->sizeInMB());
            $this->assertEquals('mp3', $audioFile->extension());
        });
        
        $this->runTest('Throws exception for invalid format', function() {
            try {
                AudioFile::create('/tmp/test.exe', 'test.exe', 'application/exe', 1024);
                $this->assert(false, 'Should throw exception');
            } catch (InvalidArgumentException $e) {
                $this->assert(true);
            }
        });
        
        $this->runTest('Throws exception for file too large', function() {
            try {
                AudioFile::create(
                    '/tmp/test.mp3',
                    'test.mp3',
                    'audio/mpeg',
                    26 * 1024 * 1024 // 26MB
                );
                $this->assert(false, 'Should throw exception');
            } catch (InvalidArgumentException $e) {
                $this->assert(true);
            }
        });
        
        $this->runTest('Needs preprocessing for specific formats', function() {
            $mp4 = AudioFile::create('/tmp/test.mp4', 'test.mp4', 'video/mp4', 1024);
            $mp3 = AudioFile::create('/tmp/test.mp3', 'test.mp3', 'audio/mpeg', 1024);
            
            $this->assert($mp4->needsPreprocessing());
            $this->assert(!$mp3->needsPreprocessing());
        });
        
        $this->runTest('Can add preprocessed path immutably', function() {
            $original = AudioFile::create('/tmp/test.mp4', 'test.mp4', 'video/mp4', 1024);
            $with = $original->withPreprocessedPath('/tmp/preprocessed.wav');
            
            $this->assert($original->preprocessedPath() === null);
            $this->assertEquals('/tmp/preprocessed.wav', $with->preprocessedPath());
        });
        
        echo "\n";
    }
    
    private function runMoneyTests() {
        echo "📋 Money Value Object Tests\n";
        echo str_repeat('-', 30) . "\n";
        
        $this->runTest('Can create money', function() {
            $money = new Money(10.50, 'USD');
            $this->assertEquals(10.50, $money->amount());
            $this->assertEquals('USD', $money->currency());
        });
        
        $this->runTest('Rounds to two decimal places', function() {
            $money = Money::USD(10.999);
            $this->assertEquals(11.00, $money->amount());
        });
        
        $this->runTest('Throws exception for negative amount', function() {
            try {
                new Money(-10, 'USD');
                $this->assert(false, 'Should throw exception');
            } catch (InvalidArgumentException $e) {
                $this->assert(true);
            }
        });
        
        $this->runTest('Can perform arithmetic operations', function() {
            $money1 = Money::USD(10.50);
            $money2 = Money::USD(5.25);
            
            $sum = $money1->add($money2);
            $this->assertEquals(15.75, $sum->amount());
            
            $diff = $money1->subtract($money2);
            $this->assertEquals(5.25, $diff->amount());
            
            $doubled = $money1->multiply(2);
            $this->assertEquals(21.00, $doubled->amount());
            
            $halved = $money1->divide(2);
            $this->assertEquals(5.25, $halved->amount());
        });
        
        $this->runTest('Cannot add different currencies', function() {
            $usd = Money::USD(10);
            $eur = Money::EUR(10);
            
            try {
                $usd->add($eur);
                $this->assert(false, 'Should throw exception');
            } catch (InvalidArgumentException $e) {
                $this->assert(true);
            }
        });
        
        $this->runTest('Formatting works correctly', function() {
            $usd = Money::USD(1234.56);
            $eur = Money::EUR(1234.56);
            
            $this->assertEquals('$1,234.56', $usd->format());
            $this->assertEquals('€1,234.56', $eur->format());
        });
        
        echo "\n";
    }
    
    private function runTranscriptionEntityTests() {
        echo "📋 Transcription Entity Tests\n";
        echo str_repeat('-', 30) . "\n";
        
        $this->runTest('Can create from file', function() {
            $audioFile = AudioFile::create('/tmp/test.mp3', 'test.mp3', 'audio/mpeg', 1024, 120);
            $language = Language::FRENCH();
            $userId = UserId::fromInt(123);
            
            $transcription = Transcription::createFromFile($audioFile, $language, $userId);
            
            $this->assert(!empty($transcription->id()));
            $this->assert($transcription->isPending());
            $this->assert(!$transcription->isYouTubeSource());
        });
        
        $this->runTest('Can create from YouTube', function() {
            $audioFile = AudioFile::create('/tmp/test.mp3', 'test.mp3', 'audio/mpeg', 1024, 120);
            $youtube = YouTubeMetadata::create('dQw4w9WgXcQ', 'https://youtube.com/watch?v=dQw4w9WgXcQ');
            $language = Language::FRENCH();
            $userId = UserId::fromInt(123);
            
            $transcription = Transcription::createFromYouTube($audioFile, $youtube, $language, $userId);
            
            $this->assert($transcription->isYouTubeSource());
            $this->assertEquals('dQw4w9WgXcQ', $transcription->youtubeMetadata()->videoId());
        });
        
        $this->runTest('Workflow: pending -> processing -> completed', function() {
            $audioFile = AudioFile::create('/tmp/test.mp3', 'test.mp3', 'audio/mpeg', 1024, 120);
            $transcription = Transcription::createFromFile(
                $audioFile,
                Language::FRENCH(),
                UserId::fromInt(123)
            );
            
            // Start processing
            $transcription->startProcessing();
            $this->assert($transcription->isProcessing());
            
            // Complete
            $text = new TranscribedText('Test content');
            $transcription->complete($text);
            $this->assert($transcription->isCompleted());
            $this->assertEquals('Test content', $transcription->text()->content());
        });
        
        $this->runTest('Cannot complete non-processing transcription', function() {
            $audioFile = AudioFile::create('/tmp/test.mp3', 'test.mp3', 'audio/mpeg', 1024, 120);
            $transcription = Transcription::createFromFile(
                $audioFile,
                Language::FRENCH(),
                UserId::fromInt(123)
            );
            
            try {
                $transcription->complete(new TranscribedText('Test'));
                $this->assert(false, 'Should throw exception');
            } catch (Exception $e) {
                $this->assert(true);
            }
        });
        
        $this->runTest('Can retry failed transcription', function() {
            $audioFile = AudioFile::create('/tmp/test.mp3', 'test.mp3', 'audio/mpeg', 1024, 120);
            $transcription = Transcription::createFromFile(
                $audioFile,
                Language::FRENCH(),
                UserId::fromInt(123)
            );
            
            $transcription->startProcessing();
            $transcription->fail('Test error');
            $this->assert($transcription->isFailed());
            
            $transcription->retry();
            $this->assert($transcription->isPending());
            $this->assert($transcription->failureReason() === null);
        });
        
        echo "\n";
    }
    
    private function runPricingServiceTests() {
        echo "📋 Pricing Service Tests\n";
        echo str_repeat('-', 30) . "\n";
        
        $this->runTest('Base rate is correct', function() {
            $service = new StandardPricingService();
            $baseRate = $service->getBaseRatePerMinute();
            
            $this->assertEquals(0.01, $baseRate->amount());
            $this->assertEquals('USD', $baseRate->currency());
        });
        
        $this->runTest('Calculates price with minimum', function() {
            $service = new StandardPricingService();
            $audioFile = AudioFile::create('/tmp/test.mp3', 'test.mp3', 'audio/mpeg', 1024, 30);
            
            $price = $service->calculatePrice($audioFile, Language::ENGLISH());
            $this->assertEquals(0.10, $price->amount()); // Minimum charge
        });
        
        $this->runTest('Priority multiplier works', function() {
            $service = new StandardPricingService();
            $audioFile = AudioFile::create('/tmp/test.mp3', 'test.mp3', 'audio/mpeg', 1024, 1200);
            
            $standard = $service->calculatePrice($audioFile, Language::ENGLISH(), false);
            $priority = $service->calculatePrice($audioFile, Language::ENGLISH(), true);
            
            $this->assertEquals($standard->amount() * 2.5, $priority->amount());
        });
        
        $this->runTest('Language complexity affects price', function() {
            $service = new StandardPricingService();
            $audioFile = AudioFile::create('/tmp/test.mp3', 'test.mp3', 'audio/mpeg', 1024, 1200);
            
            $english = $service->calculatePrice($audioFile, Language::ENGLISH());
            $chinese = $service->calculatePrice($audioFile, Language::fromCode('zh'));
            
            $this->assertEquals($english->amount() * 1.5, $chinese->amount());
        });
        
        $this->runTest('Can apply discount', function() {
            $service = new StandardPricingService();
            $price = Money::USD(1.00);
            
            $discounted = $service->applyDiscount($price, 20);
            $this->assertEquals(0.80, $discounted->amount());
        });
        
        echo "\n";
    }
    
    private function runSpecificationTests() {
        echo "📋 Specification Pattern Tests\n";
        echo str_repeat('-', 30) . "\n";
        
        $this->runTest('Language specification works', function() {
            $audioFile = AudioFile::create('/tmp/test.mp3', 'test.mp3', 'audio/mpeg', 1024, 60);
            $transcription = Transcription::createFromFile(
                $audioFile,
                Language::FRENCH(),
                UserId::fromInt(123)
            );
            
            $frenchSpec = new TranscriptionByLanguageSpecification(Language::FRENCH());
            $englishSpec = new TranscriptionByLanguageSpecification(Language::ENGLISH());
            
            $this->assert($frenchSpec->isSatisfiedBy($transcription));
            $this->assert(!$englishSpec->isSatisfiedBy($transcription));
        });
        
        $this->runTest('Status specification works', function() {
            $audioFile = AudioFile::create('/tmp/test.mp3', 'test.mp3', 'audio/mpeg', 1024, 60);
            $transcription = Transcription::createFromFile(
                $audioFile,
                Language::FRENCH(),
                UserId::fromInt(123)
            );
            
            $pendingSpec = new TranscriptionByStatusSpecification(TranscriptionStatus::PENDING());
            $completedSpec = new TranscriptionByStatusSpecification(TranscriptionStatus::COMPLETED());
            
            $this->assert($pendingSpec->isSatisfiedBy($transcription));
            $this->assert(!$completedSpec->isSatisfiedBy($transcription));
        });
        
        $this->runTest('Composite specifications work', function() {
            $audioFile = AudioFile::create('/tmp/test.mp3', 'test.mp3', 'audio/mpeg', 1024, 60);
            $transcription = Transcription::createFromFile(
                $audioFile,
                Language::FRENCH(),
                UserId::fromInt(123)
            );
            
            $frenchSpec = new TranscriptionByLanguageSpecification(Language::FRENCH());
            $pendingSpec = new TranscriptionByStatusSpecification(TranscriptionStatus::PENDING());
            
            $frenchAndPending = $frenchSpec->and($pendingSpec);
            $this->assert($frenchAndPending->isSatisfiedBy($transcription));
            
            $notFrench = $frenchSpec->not();
            $this->assert(!$notFrench->isSatisfiedBy($transcription));
        });
        
        echo "\n";
    }
    
    private function runCollectionTests() {
        echo "📋 Collection Tests\n";
        echo str_repeat('-', 30) . "\n";
        
        $this->runTest('TranscriptionCollection filtering', function() {
            $audioFile = AudioFile::create('/tmp/test.mp3', 'test.mp3', 'audio/mpeg', 1024, 60);
            
            $trans1 = Transcription::createFromFile($audioFile, Language::FRENCH(), UserId::fromInt(123));
            $trans2 = Transcription::createFromFile($audioFile, Language::ENGLISH(), UserId::fromInt(123));
            $trans3 = Transcription::createFromFile($audioFile, Language::FRENCH(), UserId::fromInt(123));
            
            $collection = new Domain\Transcription\Collection\TranscriptionCollection([$trans1, $trans2, $trans3]);
            
            $this->assertEquals(3, $collection->count());
            
            $frenchOnly = $collection->filterByLanguage(Language::FRENCH());
            $this->assertEquals(2, $frenchOnly->count());
        });
        
        $this->runTest('Collection statistics', function() {
            $audioFile = AudioFile::create('/tmp/test.mp3', 'test.mp3', 'audio/mpeg', 1024, 60);
            
            $trans1 = Transcription::createFromFile($audioFile, Language::FRENCH(), UserId::fromInt(123));
            $trans2 = Transcription::createFromFile($audioFile, Language::ENGLISH(), UserId::fromInt(123));
            
            $trans1->startProcessing();
            $trans1->complete(new TranscribedText('Bonjour le monde'));
            
            $collection = new Domain\Transcription\Collection\TranscriptionCollection([$trans1, $trans2]);
            
            $stats = $collection->getStatistics();
            $this->assertEquals(2, $stats['total']);
            $this->assertEquals(1, $stats['completed']);
            $this->assertEquals(1, $stats['pending']);
        });
        
        echo "\n";
    }
    
    private function printSummary() {
        echo str_repeat('=', 50) . "\n";
        echo "📊 RÉSUMÉ DES TESTS\n";
        echo str_repeat('=', 50) . "\n";
        
        $successRate = $this->stats['total'] > 0 
            ? round(($this->stats['passed'] / $this->stats['total']) * 100, 1) 
            : 0;
        
        echo "Tests exécutés:     {$this->stats['total']}\n";
        echo "Tests réussis:      {$this->stats['passed']} ({$successRate}%)\n";
        echo "Tests échoués:      {$this->stats['failed']}\n";
        echo "Erreurs:            {$this->stats['errors']}\n";
        echo "Assertions:         {$this->stats['assertions']}\n";
        echo "Temps d'exécution:  {$this->stats['time']}s\n";
        
        echo "\n";
        
        if ($this->stats['failed'] === 0 && $this->stats['errors'] === 0) {
            echo "✅ TOUS LES TESTS SONT PASSÉS! 🎉\n";
            echo "\nLe Domain Layer est entièrement validé et prêt pour la production.\n";
        } else {
            echo "❌ CERTAINS TESTS ONT ÉCHOUÉ\n";
            echo "\nVeuillez corriger les erreurs avant de continuer.\n";
        }
    }
}

// Exécuter les tests
$runner = new TestRunner();
$runner->run();