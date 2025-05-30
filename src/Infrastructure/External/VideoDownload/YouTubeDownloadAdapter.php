<?php

namespace Infrastructure\External\VideoDownload;

use Domain\Transcription\ValueObject\AudioFile;
use Domain\Transcription\ValueObject\YouTubeMetadata;
use Exception;

/**
 * Adapter pour le téléchargement et extraction audio YouTube
 */
class YouTubeDownloadAdapter
{
    private string $tempDirectory;
    private string $outputDirectory;
    private array $allowedFormats;
    private int $maxDuration;
    
    public function __construct(
        string $tempDirectory = '/tmp',
        string $outputDirectory = '/tmp',
        int $maxDuration = 3600 // 1 heure max
    ) {
        $this->tempDirectory = rtrim($tempDirectory, '/');
        $this->outputDirectory = rtrim($outputDirectory, '/');
        $this->maxDuration = $maxDuration;
        $this->allowedFormats = ['mp3', 'wav', 'm4a'];
        
        $this->ensureDirectoriesExist();
    }
    
    public function downloadAudio(string $youtubeUrl, string $format = 'mp3'): AudioFile
    {
        try {
            $this->validateUrl($youtubeUrl);
            $this->validateFormat($format);
            
            // Extraire les métadonnées d'abord
            $metadata = $this->extractMetadata($youtubeUrl);
            $this->validateDuration($metadata);
            
            // Télécharger l'audio
            $outputPath = $this->generateOutputPath($metadata->videoId(), $format);
            $this->executeDownload($youtubeUrl, $outputPath, $format);
            
            // Créer l'objet AudioFile
            return AudioFile::create(
                $outputPath,
                $this->generateFilename($metadata, $format),
                $this->getMimeType($format),
                filesize($outputPath),
                $metadata->duration()
            );
            
        } catch (Exception $e) {
            throw new Exception("YouTube download failed: " . $e->getMessage());
        }
    }
    
    public function extractMetadata(string $youtubeUrl): YouTubeMetadata
    {
        try {
            $videoId = $this->extractVideoId($youtubeUrl);
            
            // Simuler l'extraction de métadonnées
            // En production, utiliser yt-dlp ou l'API YouTube
            $metadata = $this->fetchVideoInfo($videoId);
            
            return YouTubeMetadata::create(
                $videoId,
                $youtubeUrl,
                $metadata['title'],
                (int) $metadata['duration']
            );
            
        } catch (Exception $e) {
            throw new Exception("Metadata extraction failed: " . $e->getMessage());
        }
    }
    
    public function isValidYouTubeUrl(string $url): bool
    {
        $patterns = [
            '/^https?:\/\/(www\.)?youtube\.com\/watch\?v=([a-zA-Z0-9_-]{11})/',
            '/^https?:\/\/youtu\.be\/([a-zA-Z0-9_-]{11})/',
            '/^https?:\/\/(www\.)?youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }
        
        return false;
    }
    
    public function getSupportedFormats(): array
    {
        return $this->allowedFormats;
    }
    
    public function getMaxDuration(): int
    {
        return $this->maxDuration;
    }
    
    private function validateUrl(string $url): void
    {
        if (!$this->isValidYouTubeUrl($url)) {
            throw new Exception("Invalid YouTube URL: {$url}");
        }
    }
    
    private function validateFormat(string $format): void
    {
        if (!in_array($format, $this->allowedFormats)) {
            throw new Exception("Unsupported format: {$format}. Allowed: " . implode(', ', $this->allowedFormats));
        }
    }
    
    private function validateDuration(YouTubeMetadata $metadata): void
    {
        if ($metadata->duration() > $this->maxDuration) {
            throw new Exception("Video too long: {$metadata->duration()}s. Max: {$this->maxDuration}s");
        }
    }
    
    private function extractVideoId(string $url): string
    {
        $patterns = [
            '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }
        
        throw new Exception("Could not extract video ID from URL: {$url}");
    }
    
    private function fetchVideoInfo(string $videoId): array
    {
        // Simulation - en production utiliser yt-dlp ou API YouTube
        // Command example: yt-dlp --print "%(title)s|%(duration)s" $url
        
        $mockData = [
            'title' => 'Sample YouTube Video ' . substr($videoId, 0, 8),
            'duration' => rand(60, 1800), // 1-30 minutes
            'description' => 'Sample description',
            'uploader' => 'Sample Channel',
            'upload_date' => date('Y-m-d'),
            'view_count' => rand(1000, 100000)
        ];
        
        return $mockData;
    }
    
    private function generateOutputPath(string $videoId, string $format): string
    {
        $filename = "youtube_{$videoId}." . $format;
        return $this->outputDirectory . '/' . $filename;
    }
    
    private function generateFilename(YouTubeMetadata $metadata, string $format): string
    {
        $safeTitle = $this->sanitizeFilename($metadata->title());
        return "{$safeTitle}.{$format}";
    }
    
    private function sanitizeFilename(string $filename): string
    {
        // Nettoyer le nom de fichier
        $filename = preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $filename);
        $filename = preg_replace('/_+/', '_', $filename);
        return substr($filename, 0, 100); // Limiter la longueur
    }
    
    private function executeDownload(string $url, string $outputPath, string $format): void
    {
        // Simulation du téléchargement
        // En production: utiliser yt-dlp
        // Command: yt-dlp -x --audio-format mp3 --output "$outputPath" "$url"
        
        $this->simulateDownload($outputPath, $format);
    }
    
    private function simulateDownload(string $outputPath, string $format): void
    {
        // Créer un fichier audio factice pour les tests
        $audioContent = $this->generateMockAudio($format);
        
        if (file_put_contents($outputPath, $audioContent) === false) {
            throw new Exception("Failed to create audio file: {$outputPath}");
        }
    }
    
    private function generateMockAudio(string $format): string
    {
        // Génère un contenu audio factice basé sur le format
        $headers = [
            'mp3' => "\xFF\xFB\x90\x00", // MP3 header
            'wav' => "RIFF\x24\x08\x00\x00WAVEfmt ", // WAV header
            'm4a' => "\x00\x00\x00\x20ftypM4A " // M4A header
        ];
        
        $header = $headers[$format] ?? $headers['mp3'];
        $content = str_repeat("\x00", 1024); // 1KB of silence
        
        return $header . $content;
    }
    
    private function getMimeType(string $format): string
    {
        $mimeTypes = [
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'm4a' => 'audio/mp4'
        ];
        
        return $mimeTypes[$format] ?? 'audio/mpeg';
    }
    
    private function ensureDirectoriesExist(): void
    {
        if (!is_dir($this->tempDirectory)) {
            mkdir($this->tempDirectory, 0755, true);
        }
        
        if (!is_dir($this->outputDirectory)) {
            mkdir($this->outputDirectory, 0755, true);
        }
    }
    
    public function cleanup(string $filePath): bool
    {
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        
        return true;
    }
    
    public function getStats(): array
    {
        return [
            'temp_directory' => $this->tempDirectory,
            'output_directory' => $this->outputDirectory,
            'max_duration' => $this->maxDuration,
            'supported_formats' => $this->allowedFormats,
            'temp_files_count' => count(glob($this->tempDirectory . '/youtube_*')),
            'output_files_count' => count(glob($this->outputDirectory . '/youtube_*'))
        ];
    }
}