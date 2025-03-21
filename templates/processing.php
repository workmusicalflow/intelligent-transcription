<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traitement en cours</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Meta refresh comme solution de secours (5 secondes) -->
    <meta http-equiv="refresh" content="5;url=index.php?action=check_status&job_id=<?= $jobId ?>&refresh=true">
    <style>
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .processing-container {
            max-width: 600px;
            margin: 100px auto;
            padding: 40px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            text-align: center;
            position: relative;
        }

        .spinner {
            display: inline-block;
            width: 80px;
            height: 80px;
            margin: 30px auto;
        }

        .spinner:after {
            content: " ";
            display: block;
            width: 64px;
            height: 64px;
            margin: 8px;
            border-radius: 50%;
            border: 6px solid #3498db;
            border-color: #3498db transparent #3498db transparent;
            animation: spinner 1.2s linear infinite;
        }

        @keyframes spinner {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        p {
            color: #7f8c8d;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .status-message {
            font-size: 16px;
            color: #3498db;
            margin: 20px 0;
            font-weight: 500;
        }

        .btn-primary {
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.2s ease;
            display: inline-block;
            margin-top: 20px;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.12);
            background-color: #3498db;
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 6px 10px rgba(0, 0, 0, 0.12);
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background-color: #2ecc71;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px auto;
            animation: scale-in 0.5s ease-out;
        }

        .success-icon svg {
            width: 40px;
            height: 40px;
            fill: none;
            stroke: white;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        @keyframes scale-in {
            0% {
                transform: scale(0);
                opacity: 0;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>

<body>
    <nav class="main-nav">
        <ul>
            <li><a href="index.php">Accueil</a></li>
            <li><a href="index.php?action=chat">Chat</a></li>
        </ul>
    </nav>

    <div class="processing-container">
        <?php if (isset($templateStatus) && $templateStatus === 'completed'): ?>
            <div class="success-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M5 12l5 5L20 7"></path>
                </svg>
            </div>
            <h1>Traitement terminé !</h1>
            <p>Votre transcription est maintenant prête.</p>
            <a href="index.php?action=result&id=<?= $resultId ?>" class="btn-primary">Voir les résultats</a>
            <script>
                // Redirection automatique
                setTimeout(function() {
                    window.location.href = 'index.php?action=result&id=<?= $resultId ?>';
                }, 2000);
            </script>
        <?php elseif (isset($templateStatus) && $templateStatus === 'error'): ?>
            <div class="error-message">
                <h2>Une erreur est survenue</h2>
                <p><?= $errorMessage ?></p>
                <a href="index.php" class="btn-primary">Réessayer</a>
            </div>
        <?php else: ?>
            <h1>Traitement en cours</h1>
            <p>Veuillez patienter pendant que nous préparons votre transcription. Cette opération peut prendre quelques
                instants.</p>

            <div class="spinner"></div>

            <div class="status-message">
                <?php
                switch ($currentStep) {
                    case 1:
                        echo "Initialisation du fichier en cours...";
                        break;
                    case 2:
                        echo "Prétraitement audio en cours...";
                        break;
                    case 3:
                        echo "Transcription IA en cours...";
                        break;
                    case 4:
                        echo "Finalisation de la transcription...";
                        break;
                    default:
                        echo "Traitement en cours...";
                }
                ?>
            </div>

            <script>
                // Simple rafraîchissement périodique
                setTimeout(function() {
                    window.location.href = 'index.php?action=check_status&job_id=<?= $jobId ?>&refresh=true';
                }, 3000);
            </script>
        <?php endif; ?>
    </div>
</body>

</html>