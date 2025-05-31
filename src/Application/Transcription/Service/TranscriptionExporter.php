<?php

namespace Application\Transcription\Service;

use Domain\Transcription\Entity\Transcription;
use Domain\Transcription\Collection\TranscriptionCollection;

final class TranscriptionExporter
{
    public function exportToJson(Transcription $transcription): string
    {
        return json_encode($transcription->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    public function exportToText(Transcription $transcription): string
    {
        $text = $transcription->transcribedText();
        if (!$text) {
            return '';
        }
        
        $output = "Transcription: {$transcription->id()}\n";
        $output .= "Language: {$transcription->language()->code()}\n";
        $output .= "Created: {$transcription->createdAt()->format('Y-m-d H:i:s')}\n";
        if ($transcription->youtubeMetadata()) {
            $output .= "YouTube: {$transcription->youtubeMetadata()->originalUrl()}\n";
            $output .= "Title: {$transcription->youtubeMetadata()->title()}\n";
        }
        $output .= "\n--- TRANSCRIPT ---\n\n";
        $output .= $text;
        
        return $output;
    }
    
    public function exportToSrt(Transcription $transcription): string
    {
        $text = $transcription->text();
        if (!$text || !method_exists($text, 'segments')) {
            // Fallback if no segments available
            return $this->createBasicSrt($transcription->transcribedText());
        }
        
        $segments = $text->segments();
        $srt = '';
        $counter = 1;
        
        foreach ($segments as $segment) {
            $start = $this->formatSrtTime($segment['start'] ?? 0);
            $end = $this->formatSrtTime($segment['end'] ?? 0);
            
            $srt .= "{$counter}\n";
            $srt .= "{$start} --> {$end}\n";
            $srt .= trim($segment['text'] ?? '') . "\n\n";
            $counter++;
        }
        
        return $srt;
    }
    
    public function exportCollectionToCsv(TranscriptionCollection $transcriptions): string
    {
        $csv = "ID,User ID,Language,Status,Original Filename,Duration,File Size,Created At,Completed At,YouTube URL,Text Preview\n";
        
        foreach ($transcriptions as $transcription) {
            $row = [
                $transcription->id(),
                $transcription->userId()->value(),
                $transcription->language()->code(),
                $transcription->status()->value(),
                $transcription->audioFile()->originalName(),
                $transcription->audioFile()->duration(),
                $transcription->audioFile()->size(),
                $transcription->createdAt()->format('Y-m-d H:i:s'),
                $transcription->completedAt()?->format('Y-m-d H:i:s') ?? '',
                $transcription->youtubeMetadata()?->originalUrl() ?? '',
                $this->truncateText($transcription->transcribedText() ?? '', 100)
            ];
            
            $csv .= implode(',', array_map([$this, 'escapeCsvField'], $row)) . "\n";
        }
        
        return $csv;
    }
    
    private function formatSrtTime(float $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;
        
        return sprintf('%02d:%02d:%06.3f', $hours, $minutes, $seconds);
    }
    
    private function createBasicSrt(string $text): string
    {
        // Create basic SRT from plain text (estimate 3 words per second)
        $words = explode(' ', $text);
        $srt = '';
        $counter = 1;
        $wordsPerSegment = 10;
        $wordsPerSecond = 3;
        
        for ($i = 0; $i < count($words); $i += $wordsPerSegment) {
            $segmentWords = array_slice($words, $i, $wordsPerSegment);
            $startTime = $i / $wordsPerSecond;
            $endTime = ($i + count($segmentWords)) / $wordsPerSecond;
            
            $srt .= "{$counter}\n";
            $srt .= $this->formatSrtTime($startTime) . " --> " . $this->formatSrtTime($endTime) . "\n";
            $srt .= implode(' ', $segmentWords) . "\n\n";
            $counter++;
        }
        
        return $srt;
    }
    
    private function truncateText(string $text, int $length): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length - 3) . '...';
    }
    
    private function escapeCsvField(string $field): string
    {
        if (strpos($field, ',') !== false || strpos($field, '"') !== false || strpos($field, "\n") !== false) {
            return '"' . str_replace('"', '""', $field) . '"';
        }
        
        return $field;
    }
}