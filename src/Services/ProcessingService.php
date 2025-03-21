<?php

namespace Services;

/**
 * Service pour gérer le suivi des tâches de traitement
 */
class ProcessingService
{
    /**
     * Répertoire de stockage des fichiers d'état
     */
    private $statusDir;

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->statusDir = BASE_DIR . '/logs/processing';

        // Créer le répertoire de stockage s'il n'existe pas
        if (!is_dir($this->statusDir)) {
            mkdir($this->statusDir, 0755, true);
        }
    }

    /**
     * Démarre un nouveau job de traitement
     * 
     * @param string $type Type de traitement ('file' ou 'youtube')
     * @param array $metadata Métadonnées du job
     * @return string ID du job
     */
    public function startJob($type, $metadata = [])
    {
        $jobId = uniqid('job_', true);

        $status = [
            'id' => $jobId,
            'type' => $type,
            'start_time' => time(),
            'status' => 'processing',
            'progress' => 0,
            'current_step' => 1,
            'metadata' => $metadata,
            'updates' => [
                [
                    'time' => time(),
                    'message' => 'Traitement démarré',
                    'step' => 1,
                    'progress' => 0
                ]
            ]
        ];

        $this->saveStatus($jobId, $status);

        return $jobId;
    }

    /**
     * Met à jour l'état d'un job
     * 
     * @param string $jobId ID du job
     * @param int $progress Progression (0-100)
     * @param int $step Étape actuelle
     * @param string $message Message d'état
     * @return array État mis à jour
     */
    public function updateJob($jobId, $progress, $step, $message = '')
    {
        $status = $this->getStatus($jobId);

        if (!$status) {
            return false;
        }

        $status['progress'] = $progress;
        $status['current_step'] = $step;
        $status['last_update'] = time();

        $status['updates'][] = [
            'time' => time(),
            'message' => $message,
            'step' => $step,
            'progress' => $progress
        ];

        $this->saveStatus($jobId, $status);

        return $status;
    }

    /**
     * Marque un job comme terminé
     * 
     * @param string $jobId ID du job
     * @param string $resultId ID du résultat
     * @return array État final
     */
    public function completeJob($jobId, $resultId)
    {
        $status = $this->getStatus($jobId);

        if (!$status) {
            return false;
        }

        $status['status'] = 'completed';
        $status['progress'] = 100;
        $status['current_step'] = 5;
        $status['end_time'] = time();
        $status['result_id'] = $resultId;

        $status['updates'][] = [
            'time' => time(),
            'message' => 'Traitement terminé avec succès',
            'step' => 5,
            'progress' => 100
        ];

        $this->saveStatus($jobId, $status);

        return $status;
    }

    /**
     * Marque un job comme échoué
     * 
     * @param string $jobId ID du job
     * @param string $error Message d'erreur
     * @return array État final
     */
    public function failJob($jobId, $error)
    {
        $status = $this->getStatus($jobId);

        if (!$status) {
            return false;
        }

        $status['status'] = 'error';
        $status['error'] = $error;
        $status['end_time'] = time();

        $status['updates'][] = [
            'time' => time(),
            'message' => 'Erreur: ' . $error,
            'step' => $status['current_step'],
            'progress' => $status['progress']
        ];

        $this->saveStatus($jobId, $status);

        return $status;
    }

    /**
     * Récupère l'état d'un job
     * 
     * @param string $jobId ID du job
     * @return array État du job
     */
    public function getStatus($jobId)
    {
        $statusFile = $this->getStatusFilePath($jobId);

        if (!file_exists($statusFile)) {
            return false;
        }

        $status = json_decode(file_get_contents($statusFile), true);

        return $status;
    }

    /**
     * Obtient le chemin du fichier d'état pour un job
     * 
     * @param string $jobId ID du job
     * @return string Chemin du fichier
     */
    private function getStatusFilePath($jobId)
    {
        return $this->statusDir . '/' . $jobId . '.json';
    }

    /**
     * Enregistre l'état d'un job
     * 
     * @param string $jobId ID du job
     * @param array $status État à sauvegarder
     * @return bool Succès de l'opération
     */
    private function saveStatus($jobId, $status)
    {
        $statusFile = $this->getStatusFilePath($jobId);

        file_put_contents($statusFile, json_encode($status, JSON_PRETTY_PRINT));

        return true;
    }

    /**
     * Nettoie les anciens fichiers d'état (plus de 24 heures)
     */
    public function cleanupOldJobs()
    {
        $files = glob($this->statusDir . '/*.json');
        $now = time();

        foreach ($files as $file) {
            $mtime = filemtime($file);

            // Supprimer les fichiers plus vieux que 24 heures
            if ($now - $mtime > 86400) {
                unlink($file);
            }
        }
    }
}
