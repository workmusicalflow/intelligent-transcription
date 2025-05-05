# Gestion des scripts Python dans un environnement cPanel

Ce document détaille les méthodes, bonnes pratiques et limitations pour exécuter des scripts Python dans un environnement d'hébergement cPanel, en se basant sur les informations les plus récentes.

## 1. Méthodes d'exécution de scripts Python sur cPanel

### 1.1 Méthode CGI (Traditionnelle)

Cette méthode est disponible sur pratiquement tous les hébergements cPanel, même les plus basiques.

#### Configuration

1. **Placement des scripts**:

   - Placez vos scripts Python dans le répertoire `/cgi-bin` (recommandé)
   - Ou configurez `.htaccess` pour d'autres répertoires

2. **Structure du script**:

   ```python
   #!/usr/bin/python3
   print("Content-type: text/html\n\n")

   # Votre code Python ici
   print("<html><body>")
   print("Python fonctionne!")
   print("</body></html>")
   ```

3. **Permissions**:

   - Définissez les permissions à `755` via le Gestionnaire de fichiers cPanel ou:

   ```bash
   chmod +x script.py
   ```

4. **Configuration .htaccess** (si en dehors de `/cgi-bin`):

   ```apache
   Options +ExecCGI
   AddHandler cgi-script .py
   ```

5. **Accès**:
   - Accédez au script via: `https://topdigitalevel.site/transcription/cgi-bin/script.py`

### 1.2 Méthode "Setup Python App" (Moderne)

Cette méthode est préférable pour les applications plus complexes et la gestion des dépendances. Elle est disponible sur les versions récentes de cPanel.

#### Configuration

1. **Accès à l'outil**:

   - Connectez-vous à cPanel
   - Naviguez vers **Software > Setup Python App**

2. **Création de l'application**:

   - **Version Python**: Sélectionnez la version souhaitée (ex: 3.9)
   - **Application Root**: Chemin du répertoire (ex: `/home/user/public_html/transcription`)
   - **Application URL**: URL d'accès (ex: `https://topdigitalevel.site/transcription`)
   - **Startup File**: Généralement `passenger_wsgi.py`

3. **Environnement virtuel**:

   - cPanel crée automatiquement un environnement virtuel
   - Accédez-y via SSH:

   ```bash
   source /home/user/virtualenv/transcription/3.9/bin/activate
   cd /home/user/public_html/transcription
   ```

4. **Installation des dépendances**:

   ```bash
   pip install -r requirements.txt
   ```

5. **Configuration WSGI** (pour frameworks comme Flask):

   - Créez ou modifiez `passenger_wsgi.py`:

   ```python
   from transcribe import app as application
   # ou pour un script simple:
   import sys, os
   INTERP = "/home/user/virtualenv/transcription/3.9/bin/python"
   if sys.executable != INTERP:
       os.execl(INTERP, INTERP, *sys.argv)

   def application(environ, start_response):
       start_response('200 OK', [('Content-Type', 'text/html')])
       return [b"Hello from Python!"]
   ```

6. **Redémarrage de l'application**:
   - Utilisez le bouton "Restart" dans cPanel
   - Ou via SSH: `touch tmp/restart.txt`

## 2. Adaptation de notre application de transcription

### 2.1 Structure recommandée pour notre application

```
public_html/
└── transcription/
    ├── passenger_wsgi.py     # Point d'entrée WSGI
    ├── transcribe.py         # Script de transcription
    ├── paraphrase.py         # Script de paraphrase
    ├── preprocess_audio.py   # Script de prétraitement
    ├── requirements.txt      # Dépendances Python
    ├── venv/                 # Environnement virtuel (si créé manuellement)
    ├── uploads/              # Dossier pour les uploads
    ├── temp_audio/           # Dossier pour les fichiers audio temporaires
    ├── results/              # Dossier pour les résultats
    └── ...                   # Autres fichiers de l'application
```

### 2.2 Adaptation des scripts PHP pour appeler Python

#### Méthode CGI

```php
// transcribe.php
$audioFile = escapeshellarg($audioFilePath);
$command = "/usr/bin/python3 " . escapeshellarg(__DIR__ . "/cgi-bin/transcribe.py") . " " . $audioFile;
$output = shell_exec($command);
```

#### Méthode Setup Python App

```php
// transcribe.php
$pythonPath = "/home/user/virtualenv/transcription/3.9/bin/python";
$scriptPath = __DIR__ . "/transcribe.py";
$audioFile = escapeshellarg($audioFilePath);
$command = escapeshellarg($pythonPath) . " " . escapeshellarg($scriptPath) . " " . $audioFile;
$output = shell_exec($command);
```

### 2.3 Adaptation des scripts Python

Modifiez vos scripts Python pour fonctionner dans l'environnement cPanel:

```python
#!/usr/bin/env python3
# transcribe.py

import sys
import os
import json
import whisper

# Assurez-vous que les chemins sont corrects
sys.path.append(os.path.dirname(os.path.abspath(__file__)))

def transcribe(audio_file):
    model = whisper.load_model("base")
    result = model.transcribe(audio_file)
    return result["text"]

if __name__ == "__main__":
    if len(sys.argv) > 1:
        audio_file = sys.argv[1]
        try:
            text = transcribe(audio_file)
            result = {"success": True, "text": text}
        except Exception as e:
            result = {"success": False, "error": str(e)}

        print(json.dumps(result))
    else:
        print(json.dumps({"success": False, "error": "No audio file provided"}))
```

## 3. Bonnes pratiques pour cPanel

### 3.1 Gestion des dépendances

- Utilisez un fichier `requirements.txt` pour lister toutes les dépendances
- Installez les dépendances dans l'environnement virtuel créé par cPanel
- Évitez d'installer des packages globalement

```
# requirements.txt
whisper==1.0.0
numpy==1.23.5
torch==2.0.1
```

### 3.2 Gestion des erreurs et logs

- Redirigez les erreurs vers un fichier de log:

```php
$command = "$pythonPath $scriptPath 2>> /home/user/public_html/transcription/logs/python_errors.log";
```

- Vérifiez les logs d'erreur dans cPanel (section "Error Log")
- Implémentez un système de journalisation dans vos scripts Python:

```python
import logging
logging.basicConfig(filename='/home/user/public_html/transcription/logs/python.log', level=logging.INFO)
logging.info("Transcription started for file: " + audio_file)
```

### 3.3 Sécurité

- Ne stockez jamais de secrets (API keys, etc.) directement dans les scripts
- Utilisez les variables d'environnement de cPanel
- Validez toujours les entrées utilisateur avant de les passer aux scripts Python
- Limitez les permissions des fichiers au minimum nécessaire

## 4. Limitations et solutions

| Limitation                        | Solution                                                                                         |
| --------------------------------- | ------------------------------------------------------------------------------------------------ |
| **Versions Python limitées**      | Vérifiez les versions disponibles dans "Setup Python App" et adaptez votre code en conséquence   |
| **Ressources limitées**           | Optimisez vos scripts, utilisez des modèles plus légers, implémentez un système de mise en cache |
| **Absence de "Setup Python App"** | Utilisez la méthode CGI ou envisagez un hébergement alternatif avec meilleur support Python      |
| **Temps d'exécution limité**      | Divisez les tâches longues en sous-tâches, utilisez des files d'attente                          |
| **Accès limité aux ports**        | Utilisez les ports standard (80/443) et configurez via l'URL de l'application                    |

### 4.1 Alternatives pour les hébergements avec support Python limité

1. **API externe**:

   - Déployez les scripts Python sur un service cloud (AWS Lambda, Google Cloud Functions)
   - Créez une API REST pour ces fonctions
   - Appelez cette API depuis votre application PHP

2. **Services tiers**:
   - Transcription: Google Speech-to-Text, AWS Transcribe
   - Paraphrase: OpenAI API
   - Prétraitement audio: Services d'édition audio en ligne

## 5. Tests et vérification

### 5.1 Script de test pour vérifier l'environnement Python

Créez un fichier `test_python.php`:

```php
<?php
$pythonPath = "/home/user/virtualenv/transcription/3.9/bin/python";
$output = shell_exec("$pythonPath -V 2>&1");
echo "Version Python: " . $output . "<br>";

$output = shell_exec("$pythonPath -c 'import sys; print(\"Python Path: \" + \"\\n\".join(sys.path))' 2>&1");
echo "<pre>$output</pre><br>";

$output = shell_exec("$pythonPath -c 'import whisper; print(\"Whisper version: \" + whisper.__version__)' 2>&1");
echo "Whisper: $output<br>";
?>
```

### 5.2 Vérification des permissions

```bash
# Vérifier les permissions des scripts Python
ls -la *.py

# Vérifier les permissions des dossiers
ls -la | grep "^d"
```

## 6. Déploiement étape par étape

1. **Préparation locale**:

   - Testez tous les scripts localement
   - Créez un fichier `requirements.txt`
   - Adaptez les chemins dans les scripts PHP et Python

2. **Configuration cPanel**:

   - Créez l'application Python via "Setup Python App" (si disponible)
   - Ou configurez le répertoire `/cgi-bin` et `.htaccess`

3. **Transfert des fichiers**:

   - Transférez tous les fichiers via FileZilla
   - Assurez-vous que les permissions sont correctes

4. **Installation des dépendances**:

   - Connectez-vous en SSH
   - Activez l'environnement virtuel
   - Installez les dépendances

5. **Tests**:
   - Exécutez le script de test
   - Vérifiez les logs d'erreur
   - Testez chaque fonctionnalité

## 7. Maintenance

### 7.1 Mise à jour des dépendances

```bash
source /home/user/virtualenv/transcription/3.9/bin/activate
pip install --upgrade -r requirements.txt
touch tmp/restart.txt  # Redémarrer l'application
```

### 7.2 Surveillance des performances

- Surveillez l'utilisation CPU/mémoire dans cPanel
- Mettez en place des alertes en cas de problèmes
- Optimisez régulièrement les scripts Python

### 7.3 Sauvegardes

- Sauvegardez régulièrement l'environnement virtuel
- Sauvegardez la base de données et les fichiers
- Documentez toutes les modifications
