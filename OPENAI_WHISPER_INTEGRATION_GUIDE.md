# 🎯 Guide d'Intégration OpenAI Speech-to-Text (Whisper) - Projet de Transcription Intelligente

> **Documentation de référence officielle** : [OpenAI Speech-to-Text Guide](https://platform.openai.com/docs/guides/speech-to-text)  
> **Version** : Décembre 2024  
> **Équipe** : Développement Transcription Intelligente

---

## 📋 Table des Matières

1. [Vue d'ensemble des modèles](#vue-densemble-des-modèles)
2. [Paramètres de configuration](#paramètres-de-configuration)
3. [Formats de réponse](#formats-de-réponse)
4. [Timestamps et granularité](#timestamps-et-granularité)
5. [Gestion des fichiers longs](#gestion-des-fichiers-longs)
6. [Optimisation avec prompts](#optimisation-avec-prompts)
7. [Streaming en temps réel](#streaming-en-temps-réel)
8. [Amélioration de la fiabilité](#amélioration-de-la-fiabilité)
9. [Implémentation pour notre projet](#implémentation-pour-notre-projet)
10. [Bonnes pratiques](#bonnes-pratiques)

---

## 🎛️ Vue d'ensemble des modèles

### Modèles Disponibles

| Modèle | Capacités | Formats supportés | Timestamps | Cas d'usage |
|--------|-----------|------------------|------------|-------------|
| **`whisper-1`** | Transcription + Traduction | `json`, `text`, `srt`, `verbose_json`, `vtt` | ✅ Segment + Word | Production générale |
| **`gpt-4o-transcribe`** | Transcription haute qualité | `json`, `text` | ❌ | Qualité premium |
| **`gpt-4o-mini-transcribe`** | Transcription rapide | `json`, `text` | ❌ | Performance optimisée |

### ⚠️ **IMPORTANT pour notre projet** :
- **`whisper-1`** est le seul modèle supportant les timestamps (`timestamp_granularities`)
- Notre implémentation actuelle utilise `whisper-1` ✅
- Les modèles GPT-4o n'ont pas encore de support timestamp (décembre 2024)

---

## ⚙️ Paramètres de configuration

### Configuration Optimale pour notre Projet

```php
// Configuration recommandée pour WhisperAdapter.php
$this->defaultOptions = [
    'response_format' => 'verbose_json',           // OBLIGATOIRE pour timestamps
    'timestamp_granularities' => ['segment', 'word'], // Les deux niveaux
    'temperature' => 0,                            // Plus de cohérence
    'language' => null,                           // Auto-détection
    'prompt' => ''                                // Contextuel selon la langue
];
```

### Paramètres Disponibles

| Paramètre | Valeurs | Description | Modèles supportés |
|-----------|---------|-------------|-------------------|
| `model` | `whisper-1`, `gpt-4o-transcribe`, `gpt-4o-mini-transcribe` | Modèle de transcription | Tous |
| `response_format` | `json`, `text`, `srt`, `verbose_json`, `vtt` | Format de sortie | `whisper-1` uniquement pour `srt`/`vtt` |
| `timestamp_granularities` | `['segment']`, `['word']`, `['segment', 'word']` | Granularité timestamps | `whisper-1` uniquement |
| `temperature` | `0` à `1` | Créativité (0 = déterministe) | Tous |
| `language` | Code ISO 639-1 | Langue forcée ou `null` pour auto-détection | Tous |
| `prompt` | String (224 tokens max) | Contexte pour améliorer qualité | Tous |

---

## 📄 Formats de réponse

### `verbose_json` (Recommandé pour notre projet)

```json
{
  "task": "transcribe",
  "language": "french",
  "duration": 180.0,
  "text": "Bonjour, comment allez-vous aujourd'hui ?",
  "segments": [
    {
      "id": 0,
      "start": 0.0,
      "end": 3.2,
      "text": "Bonjour, comment allez-vous aujourd'hui ?",
      "tokens": [1234, 5678, 9012],
      "temperature": 0.0,
      "avg_logprob": -0.15,
      "compression_ratio": 1.2,
      "no_speech_prob": 0.01
    }
  ],
  "words": [
    {
      "word": "Bonjour",
      "start": 0.0,
      "end": 0.8
    },
    {
      "word": "comment",
      "start": 1.0,
      "end": 1.5
    }
  ]
}
```

### Formats Export Direct

| Format | Extension | Cas d'usage |
|--------|-----------|-------------|
| `srt` | `.srt` | Sous-titres vidéo |
| `vtt` | `.vtt` | Sous-titres web (HTML5) |
| `text` | `.txt` | Texte simple |
| `json` | `.json` | Intégration API |

---

## ⏱️ Timestamps et granularité

### Configuration des Timestamps

```php
// Option 1: Segments uniquement (recommandé pour interface de navigation)
'timestamp_granularities' => ['segment']

// Option 2: Mots uniquement (granularité maximale)
'timestamp_granularities' => ['word']

// Option 3: Les deux (RECOMMANDÉ pour notre projet)
'timestamp_granularities' => ['segment', 'word']
```

### Structure des Données

#### Segments
```json
{
  "id": 0,
  "start": 0.0,      // Début en secondes
  "end": 3.2,        // Fin en secondes
  "text": "...",     // Texte du segment
  "avg_logprob": -0.15,  // Score de confiance
  "no_speech_prob": 0.01 // Probabilité de silence
}
```

#### Mots (Word-level)
```json
{
  "word": "Bonjour",
  "start": 0.0,
  "end": 0.8
}
```

---

## 📏 Gestion des fichiers longs

### Limites Actuelles
- **Taille max** : 25 MB par fichier
- **Formats supportés** : `mp3`, `mp4`, `mpeg`, `mpga`, `m4a`, `wav`, `webm`

### Stratégie de Chunking (Déjà implémentée)

Notre `preprocess_audio.py` gère automatiquement :

```python
# Compression si > 25MB
if file_size > 25_000_000:
    # Compression FFmpeg vers MP3 mono 22kHz
    # Bitrate adaptatif 32-192 kbps
```

### Technique de Chunking Manuel (Pour très longs fichiers)

```python
from pydub import AudioSegment

# Diviser en chunks de 10 minutes
audio = AudioSegment.from_mp3("long_audio.mp3")
chunk_length = 10 * 60 * 1000  # 10 minutes en milliseconds

for i, chunk in enumerate(audio[::chunk_length]):
    chunk.export(f"chunk_{i}.mp3", format="mp3")
```

---

## 🎯 Optimisation avec prompts

### Prompts Contextuels par Langue

```php
// Dans WhisperAdapter.php
private function getLanguagePrompt(Language $language): string
{
    $prompts = [
        'fr' => 'Transcription précise en français avec ponctuation correcte et accents appropriés.',
        'en' => 'Accurate English transcription with proper punctuation and formatting.',
        'es' => 'Transcripción precisa en español con puntuación y acentos correctos.',
        // ... autres langues
    ];
    
    return $prompts[$language->code()] ?? 'Accurate transcription with proper punctuation.';
}
```

### Prompts Spécialisés

#### Pour Acronymes et Noms Propres
```text
"Cette transcription contient des termes techniques : OpenAI, GPT-4, API, JSON, Vue.js, TypeScript, Whisper."
```

#### Pour Préserver la Ponctuation
```text
"Bonjour, comment allez-vous ? Très bien, merci !"
```

#### Pour Conserver les Mots de Remplissage
```text
"Euh, ben, vous voyez, c'est comme ça que, enfin, ça marche."
```

---

## 🔄 Streaming en temps réel

### 1. Streaming de Fichier Complet

```javascript
const stream = await openai.audio.transcriptions.create({
  file: fs.createReadStream("audio.mp3"),
  model: "gpt-4o-mini-transcribe",
  response_format: "text",
  stream: true,
});

for await (const event of stream) {
  console.log(event); // Événements de transcription en temps réel
}
```

### 2. Streaming Audio en Direct (Realtime API)

```text
WebSocket: wss://api.openai.com/v1/realtime?intent=transcription
```

```json
{
  "type": "transcription_session.update",
  "input_audio_format": "pcm16",
  "input_audio_transcription": {
    "model": "gpt-4o-transcribe",
    "prompt": "",
    "language": "fr"
  },
  "turn_detection": {
    "type": "server_vad",
    "threshold": 0.5,
    "silence_duration_ms": 500
  }
}
```

---

## 🔧 Amélioration de la fiabilité

### Méthode 1: Prompt Whisper (224 tokens max)

```php
$params['prompt'] = "ZyntriQix, API OpenAI, Vue.js, TypeScript, Whisper, GPT-4";
```

### Méthode 2: Post-traitement GPT-4 (Recommandé)

```php
// Après transcription Whisper
$systemPrompt = "
Tu es un assistant pour une application de transcription. 
Corrige les erreurs d'orthographe et assure-toi que ces termes 
sont correctement orthographiés : OpenAI, Whisper, Vue.js, TypeScript, 
API, JSON, PHP, JavaScript, FFmpeg.
Ajoute uniquement la ponctuation nécessaire.
";

$correctedText = $this->gptClient->correct($whisperText, $systemPrompt);
```

---

## 🏗️ Implémentation pour notre projet

### Ajustements Recommandés

#### 1. Configuration WhisperAdapter

```php
// src/Infrastructure/External/OpenAI/WhisperAdapter.php
$this->defaultOptions = [
    'response_format' => 'verbose_json',
    'timestamp_granularities' => ['segment', 'word'], // ✨ NOUVEAU
    'temperature' => 0,                                // ✨ NOUVEAU
];
```

#### 2. Stockage des Segments

```sql
-- Table segments (déjà créée via migration)
CREATE TABLE transcription_segments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    transcription_id TEXT NOT NULL,
    segment_index INTEGER NOT NULL,
    start_time REAL NOT NULL,
    end_time REAL NOT NULL,
    text TEXT NOT NULL,
    confidence REAL,
    avg_logprob REAL,
    word_count INTEGER,
    FOREIGN KEY (transcription_id) REFERENCES transcriptions(id)
);
```

#### 3. Sauvegarde des Données Whisper

```php
// Après transcription Whisper réussie
$whisperData = [
    'segments' => $response['segments'],
    'words' => $response['words'],
    'language' => $response['language'],
    'duration' => $response['duration']
];

$stmt->execute([
    'whisper_data' => json_encode($whisperData),
    'confidence_score' => $averageConfidence,
    'detected_language' => $response['language'],
    'processing_model' => 'whisper-1'
]);
```

### API Frontend Optimisée

```php
// api/transcriptions/detail.php
if (!empty($transcription['whisper_data'])) {
    $whisperData = json_decode($transcription['whisper_data'], true);
    $segments = $whisperData['segments']; // VRAIS segments Whisper
    $hasRealSegments = true;
} else {
    $segments = $this->generateEstimatedSegments($text); // Segments estimés
    $hasRealSegments = false;
}
```

---

## ✅ Bonnes pratiques

### Performance

1. **Prétraitement audio** : Compression automatique < 25MB ✅
2. **Format optimal** : MP3 mono 22kHz pour réduire les coûts ✅
3. **Prompt caching** : Économie ~50% sur les coûts GPT ✅
4. **Retry logic** : Gestion automatique des erreurs réseau ✅

### Qualité

1. **Temperature = 0** : Plus de cohérence dans les transcriptions
2. **Prompts contextuels** : Amélioration qualité par langue
3. **Post-traitement GPT-4** : Correction des noms propres et acronymes
4. **Granularité mixte** : `['segment', 'word']` pour flexibilité maximale

### Coûts

| Service | Prix | Optimisation |
|---------|------|--------------|
| Whisper | $0.006/minute | Compression audio ✅ |
| GPT-4o-mini | $0.00015/1k tokens input | Prompt caching ✅ |

### Monitoring

```php
// Métriques à tracker
$metrics = [
    'transcription_duration' => $processingTime,
    'audio_duration' => $audioDuration,
    'confidence_score' => $averageConfidence,
    'segment_count' => count($segments),
    'word_count' => $wordCount,
    'cost_usd' => $estimatedCost
];
```

---

## 🚀 Prochaines étapes

### Phase Immédiate

1. ✅ **Mise à jour configuration** : `temperature: 0` + granularité mixte
2. ✅ **Migration segments** : Table et colonnes créées
3. ✅ **API optimisée** : Détection automatique vrais/estimés segments

### Phase Futur

1. **Streaming transcriptions** : Implémentation en temps réel
2. **Post-traitement intelligent** : GPT-4 pour correction automatique
3. **Interface timeline** : Navigation précise par mots
4. **Export SRT/VTT** : Sous-titres avec timestamps réels

---

## 📞 Support et Ressources

- **Documentation officielle** : [OpenAI Speech-to-Text](https://platform.openai.com/docs/guides/speech-to-text)
- **Référence API** : [Audio API Reference](https://platform.openai.com/docs/api-reference/audio)
- **Modèles Whisper** : [GitHub Whisper](https://github.com/openai/whisper)
- **Langues supportées** : [99+ langues disponibles](https://github.com/openai/whisper#available-models-and-languages)

---

**🎯 Notre projet est déjà excellemment configuré ! Les ajustements proposés optimiseront encore la qualité et les fonctionnalités.**