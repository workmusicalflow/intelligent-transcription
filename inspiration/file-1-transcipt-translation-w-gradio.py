import os
import uuid
import time
from google.oauth2.credentials import Credentials
from google_auth_oauthlib.flow import InstalledAppFlow
from google.auth.transport.requests import Request
from googleapiclient.discovery import build
import pickle
from datetime import datetime, timedelta
import whisper
import openai
from dotenv import load_dotenv
import gradio as gr
from google.cloud import texttospeech_v1beta1 as texttospeech
from google.cloud import storage
import subprocess
import tempfile
import yt_dlp
import re
from youtube_transcript_api import YouTubeTranscriptApi
import moviepy.editor as mp
from typing_extensions import override
from openai import AssistantEventHandler
from openai import OpenAI
import json

## Scopes pour l'API Google Docs
SCOPES = ['https://www.googleapis.com/auth/documents']

# Charger les variables d'environnement
load_dotenv()

# Configuration de l'API OpenAI
openai.api_key = os.getenv("OPENAI_API_KEY")

# Charger les assistant_id déjà créé dans le Playground
assistant_id = os.getenv("ASSISTANT_ID")
paraphraser_assistant_id = os.getenv("PARAPHRASER_ASSISTANT_ID")  # Définie dans le fichier .env

# Variables globales pour le compteur d'utilisations et les threads
usage_counter = 0
paraphraser_usage_counter = 0
MAX_USAGE_BEFORE_CLEANUP = 5
thread = None
paraphraser_thread = None

# Variable globale pour stocker les credentials Google
google_creds = None

# Configuration pour Google Cloud Text-to-Speech
GOOGLE_APPLICATION_CREDENTIALS = 'core-yew-434916-m6-435b4b50c244.json'
PROJECT_ID = 'core-yew-434916-m6'
BUCKET_NAME = 'vok01'
AUDIO_FOLDER = 'audio'
LOCATION = 'europe-west1'
PUBLIC_URL_BASE = f"https://storage.googleapis.com/{BUCKET_NAME}/{AUDIO_FOLDER}/"

os.environ['GOOGLE_APPLICATION_CREDENTIALS'] = GOOGLE_APPLICATION_CREDENTIALS

# Variable globale pour stocker les fichiers audio générés
generated_audio_files = []

# Liste pour suivre les fichiers temporaires à nettoyer
temporary_files = []

# Variable globale pour stocker le contexte actuel
current_context = {
    "transcription": "",
    "translation": ""
}

def update_context(transcription="", translation=""):
    """Mettre à jour le contexte global avec les derniers contenus"""
    global current_context
    if transcription:
        current_context["transcription"] = transcription
    if translation:
        current_context["translation"] = translation

def chat_with_gpt(message, history):
    """Fonction pour interagir avec le modèle GPT en utilisant le contexte actuel"""
    try:
        client = OpenAI()
        
        # Construire le contexte pour le message système
        system_message = f"""Vous êtes un assistant utile qui a accès au contenu récemment transcrit et traduit.
        
Contenu transcrit:
{current_context['transcription']}

Traduction:
{current_context['translation']}

Utilisez ce contexte pour répondre aux questions de l'utilisateur de manière pertinente et informative.
"""
        
        # Construire l'historique des messages
        messages = [{"role": "system", "content": system_message}]
        
        # Ajouter l'historique des conversations
        for msg in history:
            messages.append({"role": "user", "content": msg[0]})
            if msg[1]:
                messages.append({"role": "assistant", "content": msg[1]})
        
        # Ajouter le message actuel
        messages.append({"role": "user", "content": message})
        
        # Appeler l'API OpenAI
        response = client.chat.completions.create(
            model="gpt-4.1-nano",
            messages=messages,
            temperature=0.7,
            max_tokens=1000
        )
        
        return response.choices[0].message.content
    except Exception as e:
        return f"Erreur lors de la communication avec ChatGPT : {str(e)}"

#fonction pour gérer l'authentification
def authenticate_google_docs():
    creds = None
    # Le token est stocké dans 'token.pickle' après la première authentification
    if os.path.exists('token.pickle'):
        with open('token.pickle', 'rb') as token:
            creds = pickle.load(token)
    # Si les credentials ne sont pas valides, authentifiez-vous
    if not creds or not creds.valid:
        if creds and creds.expired and creds.refresh_token:
            try:
                creds.refresh(Request())
            except Exception as e:
                print(f"Erreur lors du rafraîchissement des credentials : {str(e)}")
                creds = None  # Réinitialiser les creds pour forcer une nouvelle authentification
        if not creds or not creds.valid:
            flow = InstalledAppFlow.from_client_secrets_file('client_secret_811283068883-rvk1f4uua4dkubh1vc72febfo07fba9u.apps.googleusercontent.com.json', SCOPES)
            creds = flow.run_local_server(port=0)
            # Sauvegarder les credentials pour les prochaines exécutions
            with open('token.pickle', 'wb') as token:
                pickle.dump(creds, token)
    return creds

def ensure_google_auth():
    """S'assure que l'authentification Google est effectuée"""
    global google_creds
    if not google_creds:
        google_creds = authenticate_google_docs()
    return google_creds

def create_google_doc(title):
    """Crée un nouveau document Google Docs et retourne son ID"""
    creds = ensure_google_auth()
    service = build('docs', 'v1', credentials=creds)
    
    # Créer un nouveau document
    document = {
        'title': title
    }
    doc = service.documents().create(body=document).execute()
    return doc.get('documentId')

#fonction qui ajoutera du texte au document Google Docs pendant le streaming
def append_text_to_google_doc_streaming(service, document_id, text):
    # Obtenir le nombre de caractères actuels pour insérer à la fin
    doc = service.documents().get(documentId=document_id).execute()
    end_index = doc.get('body').get('content')[-1].get('endIndex', 1)
    
    requests = [
        {
            'insertText': {
                'location': {
                    'index': end_index - 1
                },
                'text': text
            }
        }
    ]
    
    result = service.documents().batchUpdate(documentId=document_id, body={'requests': requests}).execute()
    return result

# Fonction pour transcrire avec Whisper
def transcribe_whisper(audio_file, source_lang):
    model = whisper.load_model("base")
    result = model.transcribe(audio_file, language=source_lang)
    return result["text"]

def generate_unique_filename():
    return f"file_{datetime.now().strftime('%Y%m%d_%H%M%S')}_{str(uuid.uuid4())[:8]}"

def synthesize_long_audio(text, language_code, voice_name, speed, pitch):
    client = texttospeech.TextToSpeechLongAudioSynthesizeClient()

    input_text = texttospeech.SynthesisInput(text=text)
    voice = texttospeech.VoiceSelectionParams(language_code=language_code, name=voice_name)

    audio_config = texttospeech.AudioConfig(
        audio_encoding=texttospeech.AudioEncoding.LINEAR16,
        speaking_rate=speed,
        pitch=pitch
    )

    unique_filename = generate_unique_filename()
    output_gcs_uri = f"gs://{BUCKET_NAME}/{AUDIO_FOLDER}/{unique_filename}.wav"
    public_url = f"{PUBLIC_URL_BASE}{unique_filename}.wav"

    parent = f"projects/{PROJECT_ID}/locations/{LOCATION}"

    request = texttospeech.SynthesizeLongAudioRequest(
        parent=parent,
        input=input_text,
        audio_config=audio_config,
        voice=voice,
        output_gcs_uri=output_gcs_uri,
    )

    try:
        operation = client.synthesize_long_audio(request=request)
        print("Waiting for operation to complete...")
        operation.result(timeout=300)  # Attendre que l'opération se termine
        print(f"Audio should be available at public URL: {public_url}")
        generated_audio_files.append((unique_filename, datetime.now()))
        return public_url
    except Exception as e:
        print(f"Error in synthesize_long_audio: {str(e)}")
        return public_url

def clean_old_audio_files():
    """Supprimer les fichiers audio de plus d'une heure"""
    storage_client = storage.Client()
    bucket = storage_client.bucket(BUCKET_NAME)
    current_time = datetime.now()

    for filename, creation_time in generated_audio_files[:]:
        if current_time - creation_time > timedelta(hours=1):
            blob = bucket.blob(f"{AUDIO_FOLDER}/{filename}.wav")
            blob.delete()
            generated_audio_files.remove((filename, creation_time))
            print(f"Deleted old audio file: {filename}.wav")

def append_transcription_and_translation_to_google_doc(document_id, transcription_text, translation_text=None):
    """Ajoute la transcription et la traduction à un document Google Docs"""
    creds = ensure_google_auth()
    service = build('docs', 'v1', credentials=creds)
    
    requests = []
    
    # Ajouter un titre pour la transcription
    requests.append({
        'insertText': {
            'location': {'index': 1},
            'text': f"\nTranscription ({datetime.now().strftime('%Y-%m-%d %H:%M:%S')})\n\n"
        }
    })
    
    # Ajouter la transcription
    requests.append({
        'insertText': {
            'endOfSegmentLocation': {},
            'text': f"{transcription_text}\n\n"
        }
    })
    
    # Ajouter la traduction si elle existe
    if translation_text:
        requests.append({
            'insertText': {
                'endOfSegmentLocation': {},
                'text': f"Traduction:\n{translation_text}\n\n"
            }
        })
    
    # Ajouter une ligne de séparation
    requests.append({
        'insertText': {
            'endOfSegmentLocation': {},
            'text': "----------------------------------------\n\n"
        }
    })
    
    # Exécuter les modifications
    service.documents().batchUpdate(
        documentId=document_id,
        body={'requests': requests}
    ).execute()

# Classe pour gérer les événements de streaming
class EventHandler(AssistantEventHandler):
    def __init__(self):
        super().__init__()
        self.translated_text = ""
    
    @override
    def on_text_created(self, text) -> None:
        print("\nassistant > ", end="", flush=True)
    
    @override
    def on_text_delta(self, delta, snapshot):
        print(delta.value, end="", flush=True)
        self.translated_text += delta.value

# Fonction pour supprimer le thread
def delete_thread(thread_id):
    try:
        openai.beta.threads.delete(thread_id)
        print(f"Thread {thread_id} deleted successfully.")
    except Exception as e:
        print(f"Error deleting thread {thread_id}: {str(e)}")

# Fonction de traduction avec l'assistant GPT en version bêta avec gestion du thread
def translate_with_assistant(text, source_lang, target_lang):
    global usage_counter, MAX_USAGE_BEFORE_CLEANUP, thread, assistant_id
    try:
        # Si le thread n'existe pas, le créer
        if thread is None:
            thread = openai.beta.threads.create()
            print(f"New thread created: {thread.id}")
        
        # Ajouter le message de l'utilisateur au Thread
        message = openai.beta.threads.messages.create(
            thread_id=thread.id,
            role="user",
            content=text
        )
        
        # Créer un EventHandler pour gérer le streaming
        event_handler = EventHandler()
        
        # Créer et streamer le Run avec l'assistant existant
        with openai.beta.threads.runs.stream(
            thread_id=thread.id,
            assistant_id=assistant_id,
            event_handler=event_handler
        ) as stream:
            stream.until_done()
        
        # Récupérer le texte traduit depuis l'EventHandler
        translated_text = event_handler.translated_text.strip()
        
        # Incrémenter le compteur d'utilisations
        usage_counter += 1
        print(f"Usage counter: {usage_counter}")
        
        # Vérifier si nous devons nettoyer le thread après MAX_USAGE_BEFORE_CLEANUP utilisations
        if usage_counter >= MAX_USAGE_BEFORE_CLEANUP:
            delete_thread(thread.id)
            usage_counter = 0  # Réinitialiser le compteur
            thread = None      # Réinitialiser le thread
        
        return translated_text
    
    except Exception as e:
        print(f"Error in translate_with_assistant: {str(e)}")
        return f"Error in translate_with_assistant: {str(e)}"

def paraphrase_with_assistant(text):
    global paraphraser_thread, paraphraser_usage_counter, MAX_USAGE_BEFORE_CLEANUP, paraphraser_assistant_id
    try:
        # Si le thread n'existe pas, le créer
        if paraphraser_thread is None:
            paraphraser_thread = openai.beta.threads.create()
            print(f"New paraphraser thread created: {paraphraser_thread.id}")
        
        # Ajouter le message de l'utilisateur au Thread
        message = openai.beta.threads.messages.create(
            thread_id=paraphraser_thread.id,
            role="user",
            content=text
        )
        
        # Créer un EventHandler pour gérer le streaming
        event_handler = EventHandler()
        
        # Créer et streamer le Run avec le second assistant
        with openai.beta.threads.runs.stream(
            thread_id=paraphraser_thread.id,
            assistant_id=paraphraser_assistant_id,
            event_handler=event_handler
        ) as stream:
            stream.until_done()
        
        # Récupérer le texte paraphrasé depuis l'EventHandler
        paraphrased_text = event_handler.translated_text.strip()
        
        # Incrémenter le compteur d'utilisations
        paraphraser_usage_counter += 1
        print(f"Paraphraser usage counter: {paraphraser_usage_counter}")
        
        # Vérifier si nous devons nettoyer le thread après MAX_USAGE_BEFORE_CLEANUP utilisations
        if paraphraser_usage_counter >= MAX_USAGE_BEFORE_CLEANUP:
            delete_thread(paraphraser_thread.id)
            paraphraser_usage_counter = 0  # Réinitialiser le compteur
            paraphraser_thread = None      # Réinitialiser le thread
        
        return paraphrased_text
        
    except Exception as e:
        print(f"Error in paraphrase_with_assistant: {str(e)}")
        return f"Error in paraphrase_with_assistant: {str(e)}"

def process_files(uploaded_files, source_lang, target_lang, use_whisper=True, use_translation=False, export_to_gdocs=False, gdocs_document_id='', batch_processing=False):
    global usage_counter, MAX_USAGE_BEFORE_CLEANUP, thread, assistant_id
    global paraphraser_usage_counter, paraphraser_thread, paraphraser_assistant_id
    try:
        # Effacer les fichiers temporaires précédents
        temporary_files.clear()

        if not uploaded_files:
            return None, "No files uploaded.", "", "", "N/A"

        # Si un seul fichier est téléchargé, le convertir en liste
        if not isinstance(uploaded_files, list):
            uploaded_files = [uploaded_files]

        # Limiter le nombre de fichiers à 5
        if len(uploaded_files) > 5:
            return None, "Vous pouvez télécharger un maximum de 5 fichiers.", "", "", "N/A"

        video_outputs = []
        file_info_texts = []
        transcripts = []
        translated_texts = []

        for uploaded_file in uploaded_files:
            # Vérifier si le fichier est un fichier vidéo ou audio
            file_path = uploaded_file
            filename = os.path.basename(file_path)
            file_ext = os.path.splitext(filename)[1].lower()
            temp_audio_file = None
            video_output = None

            if file_ext in ['.mp4', '.avi', '.mov', '.mkv']:
                # C'est un fichier vidéo
                video_path = file_path
                video_output = video_path  # Retourner le chemin vers le fichier vidéo

                # Extraire l'audio du fichier vidéo
                temp_audio_file = tempfile.NamedTemporaryFile(delete=False, suffix=".wav")
                temporary_files.append(temp_audio_file.name)  # Suivre les fichiers temporaires
                command = ['ffmpeg', '-y', '-i', video_path, '-vn', '-acodec', 'pcm_s16le', '-ar', '44100', '-ac', '2', temp_audio_file.name]
                subprocess.run(command, check=True)
                audio_file = temp_audio_file.name
            elif file_ext in ['.mp3', '.wav', '.aac', '.flac', '.m4a']:
                # C'est un fichier audio
                audio_file = file_path
                video_output = None  # Pas de vidéo à afficher
            else:
                file_info_text = f"**Filename:** {filename}\n**File Type:** {file_ext}"
                file_info_texts.append(file_info_text)
                transcripts.append("Unsupported file type.")
                translated_texts.append("")
                continue  # Passer au fichier suivant

            # Transcrire l'audio
            transcript = transcribe_whisper(audio_file, source_lang)

            # Paraphraser ou traduire le texte
            if use_translation:
                # Traduction avec l'Assistant GPT
                translated_text = translate_with_assistant(transcript, source_lang, target_lang)
            else:
                # Paraphraser le texte avec le second Assistant GPT
                translated_text = paraphrase_with_assistant(transcript)

            # Nettoyer le fichier audio temporaire si nous l'avons créé
            if temp_audio_file and os.path.exists(temp_audio_file.name):
                os.remove(temp_audio_file.name)
                temporary_files.remove(temp_audio_file.name)

            # Traduction avec l'Assistant GPT
            if use_translation:
                translated_text = translate_with_assistant(transcript, source_lang, target_lang)
            else:
                translated_text = ""

            # Ajouter les résultats aux listes
            video_outputs.append(video_output)
            file_info_texts.append(f"**Filename:** {filename}\n**File Type:** {file_ext}")
            transcripts.append(transcript)
            translated_texts.append(translated_text)

            # Ajouter la transcription et la traduction/paraphrase au document Google Docs si nécessaire
            if export_to_gdocs and gdocs_document_id:
                try:
                    append_transcription_and_translation_to_google_doc(gdocs_document_id, transcript, translated_text)
                    print(f"Transcription et traduction/paraphrase de {filename} ajoutées au document Google Docs avec succès.")
                except Exception as e:
                    print(f"Erreur lors de l'exportation vers Google Docs pour {filename}: {str(e)}")

            # Incrémenter le compteur d'utilisations
            usage_counter += 1
            print(f"Usage counter: {usage_counter}")

            if usage_counter >= MAX_USAGE_BEFORE_CLEANUP:
                delete_thread(thread.id)
                usage_counter = 0  # Réinitialiser le compteur
                thread = None      # Réinitialiser le thread

        # Combiner les résultats pour l'affichage
        # Pour l'affichage vidéo, on ne peut afficher qu'une vidéo à la fois
        # Nous afficherons la première vidéo si disponible
        combined_video_output = video_outputs[0] if any(video_outputs) else None

        # Combiner les informations des fichiers
        combined_file_info_text = "\n\n".join(file_info_texts)

        # Combiner les transcriptions
        combined_transcript = "\n\n".join([f"**{file_info_texts[i]}**\n{transcripts[i]}" for i in range(len(transcripts))])

        # Combiner les traductions
        combined_translated_text = "\n\n".join([f"**{file_info_texts[i]}**\n{translated_texts[i]}" for i in range(len(translated_texts))])

        return combined_video_output, combined_file_info_text, combined_transcript, combined_translated_text, "N/A"
    except Exception as e:
        error_message = f"An unexpected error occurred: {str(e)}"
        return None, "", error_message, "", "N/A"

def process_input(input_type, youtube_url, uploaded_files, source_lang, target_lang, use_whisper, use_translation, export_to_gdocs, gdocs_document_id, batch_processing):
    try:
        # Si l'export Google Docs est activé, vérifier l'authentification
        if export_to_gdocs:
            try:
                ensure_google_auth()
            except Exception as e:
                return "", "", f"Erreur d'authentification Google : {str(e)}"

            # Si aucun ID de document n'est fourni, créer un nouveau document
            if not gdocs_document_id:
                title = "Transcription " + datetime.now().strftime("%Y-%m-%d %H:%M:%S")
                if input_type == "Lien YouTube" and youtube_url:
                    try:
                        info = get_video_info(youtube_url)
                        title = f"Transcription - {info['title']}"
                    except:
                        pass
                gdocs_document_id = create_google_doc(title)

        # Traitement du contenu
        if input_type == "Lien YouTube":
            if not youtube_url:
                return "", "", "Veuillez entrer une URL YouTube valide"
            transcription = process_youtube_url(youtube_url, use_whisper, source_lang)
            if isinstance(transcription, str) and transcription.startswith("Erreur"):
                return "", "", transcription
            
            translated_text = ""
            if use_translation:
                translated_text = translate_with_assistant(transcription, source_lang, target_lang)
            
            # Mettre à jour le contexte
            update_context(transcription, translated_text)
            
            # Export vers Google Docs si activé
            if export_to_gdocs and gdocs_document_id:
                try:
                    append_transcription_and_translation_to_google_doc(
                        gdocs_document_id,
                        transcription,
                        translated_text if use_translation else None
                    )
                    return transcription, translated_text, f"Traitement terminé. Document Google Docs ID: {gdocs_document_id}"
                except Exception as e:
                    return transcription, translated_text, f"Erreur lors de l'export vers Google Docs : {str(e)}"
            
            return transcription, translated_text, "Traitement terminé"
        else:
            if not uploaded_files:
                return "", "", "Veuillez sélectionner des fichiers à traiter"
            return process_files(
                uploaded_files, source_lang, target_lang,
                use_whisper, use_translation,
                export_to_gdocs, gdocs_document_id,
                batch_processing
            )
    except Exception as e:
        return "", "", f"Erreur lors du traitement : {str(e)}"

# Fonction pour extraire l'ID de la vidéo YouTube
def extract_video_id(url):
    video_id = re.search(r'(?:v=|\/)([0-9A-Za-z_-]{11}).*', url)
    return video_id.group(1) if video_id else None

# Fonction pour obtenir les informations de la vidéo
def get_video_info(video_url):
    ydl_opts = {'quiet': True}
    with yt_dlp.YoutubeDL(ydl_opts) as ydl:
        info = ydl.extract_info(video_url, download=False)
        return {
            "title": info['title'],
            "duration": info['duration'],
            "upload_date": info['upload_date'],
            "view_count": info['view_count'],
            "thumbnail": info['thumbnail'],
        }

# Fonction pour télécharger et transcrire une vidéo YouTube
def process_youtube_url(youtube_url, use_whisper=True, source_lang="auto"):
    video_id = extract_video_id(youtube_url)
    if not video_id:
        return "URL YouTube invalide"
    
    try:
        # Essayer d'abord avec l'API YouTube Transcript
        try:
            transcript = YouTubeTranscriptApi.get_transcript(video_id)
            return ' '.join([entry['text'] for entry in transcript])
        except Exception as transcript_error:
            if not use_whisper:
                raise transcript_error
            
            print("API YouTube Transcript a échoué, utilisation de Whisper...")
            
            # Créer un dossier temporaire s'il n'existe pas
            temp_dir = "temp_audio"
            if not os.path.exists(temp_dir):
                os.makedirs(temp_dir)
            
            # Utiliser un nom de fichier unique dans le dossier temp
            base_filename = os.path.join(temp_dir, f"{video_id}_{int(time.time())}")
            temp_audio = f"{base_filename}.%(ext)s"
            final_audio = f"{base_filename}.wav"
            
            # Configuration de yt-dlp
            ydl_opts = {
                'format': 'bestaudio/best',
                'outtmpl': temp_audio,
                'postprocessors': [{
                    'key': 'FFmpegExtractAudio',
                    'preferredcodec': 'wav',
                    'preferredquality': '192',
                }],
                'quiet': False,  # Activer les logs pour le débogage
                'verbose': True,  # Plus de détails dans les logs
                'progress': True,  # Afficher la progression
                'keepvideo': False,
                'writethumbnail': False,
                'writesubtitles': False,
            }
            
            print(f"Tentative de téléchargement vers : {temp_audio}")
            
            try:
                with yt_dlp.YoutubeDL(ydl_opts) as ydl:
                    ydl.download([youtube_url])
                    
                # Vérifier si le fichier WAV existe
                if not os.path.exists(final_audio):
                    print(f"Fichier WAV non trouvé : {final_audio}")
                    # Chercher d'autres fichiers potentiels
                    potential_files = [f for f in os.listdir(temp_dir) if f.startswith(f"{video_id}_")]
                    if potential_files:
                        print(f"Fichiers trouvés dans le dossier : {potential_files}")
                    return "Erreur : Le fichier audio n'a pas été créé correctement"
                
                print(f"Fichier audio créé avec succès : {final_audio}")
                
                # Ajouter le fichier à la liste des fichiers temporaires
                temporary_files.append(final_audio)
                
                # Transcription avec Whisper
                model = whisper.load_model("base")
                
                if source_lang == "auto":
                    audio = whisper.load_audio(final_audio)
                    audio = whisper.pad_or_trim(audio)
                    mel = whisper.log_mel_spectrogram(audio).to(model.device)
                    _, probs = model.detect_language(mel)
                    detected_lang = max(probs, key=probs.get)
                    result = model.transcribe(final_audio, language=detected_lang)
                else:
                    result = model.transcribe(final_audio, language=source_lang)
                
                transcription_text = result["text"]
                
                # Nettoyer le fichier
                if os.path.exists(final_audio):
                    os.remove(final_audio)
                    temporary_files.remove(final_audio)
                
                return transcription_text
                
            except Exception as e:
                print(f"Erreur lors du téléchargement/traitement : {str(e)}")
                # Nettoyer les fichiers potentiels
                if os.path.exists(final_audio):
                    os.remove(final_audio)
                    if final_audio in temporary_files:
                        temporary_files.remove(final_audio)
                return f"Erreur lors du téléchargement : {str(e)}"
            
    except Exception as e:
        return f"Erreur lors du traitement de la vidéo : {str(e)}"
    finally:
        # Nettoyer les fichiers temporaires restants
        clean_old_audio_files()

# Fonction pour générer de l'audio
def generate_audio(text, tts_language, tts_voice, speed, pitch):
    try:
        if not text:
            raise ValueError("No text provided for audio generation")
        clean_old_audio_files()  # Nettoyer les anciens fichiers avant d'en générer un nouveau
        public_url = synthesize_long_audio(text, tts_language, tts_voice, speed, pitch)
        return public_url, f"[Download Audio]({public_url})\n\nNote: Un nouveau fichier audio a été généré avec une vitesse de parole de {speed} et une hauteur de voix de {pitch}. Les fichiers précédents seront automatiquement supprimés après 1 heure."
    except Exception as e:
        error_message = f"TTS error: {str(e)}"
        print(error_message)
        return "", error_message

def reset_interface():
    """Fonction pour réinitialiser l'interface et nettoyer les fichiers temporaires"""
    # Nettoyer les fichiers temporaires
    for temp_file in temporary_files:
        if os.path.exists(temp_file):
            os.remove(temp_file)
    temporary_files.clear()
    
    # Réinitialiser le contexte global
    update_context("", "")
    
    # Nettoyer les fichiers audio générés
    clean_old_audio_files()
    
    # Retourner les valeurs dans l'ordre des outputs
    return (
        gr.update(value=None),  # uploaded_files
        "",                     # output_text
        "",                     # translated_text
        "",                     # status_output
        "",                     # youtube_url
        "",                     # video_info
        gr.update(visible=False, value=""),  # video_player
        None,                   # chatbot (historique du chat)
        "",                     # message input
        ""                      # chat status
    )

def toggle_input_type(choice):
    if choice == "Lien YouTube":
        return (
            gr.update(visible=True),    # youtube_url
            gr.update(visible=False),   # uploaded_files
            gr.update(visible=True),    # video_info
            gr.update(visible=False)    # batch_processing
        )
    else:
        return (
            gr.update(visible=False),   # youtube_url
            gr.update(visible=True),    # uploaded_files
            gr.update(visible=False),   # video_info
            gr.update(visible=True)     # batch_processing
        )

def update_video_player_and_info(url):
    video_info = ""
    video_player_html = gr.update(visible=False, value="")
    
    if url:
        video_id = extract_video_id(url)
        if video_id:
            try:
                # Mise à jour des informations de la vidéo
                info = get_video_info(url)
                duration = f"{info['duration'] // 60}:{info['duration'] % 60:02d}"
                upload_date = datetime.strptime(str(info['upload_date']), '%Y%m%d').strftime('%Y-%m-%d')
                video_info = f"""
                Titre: {info['title']}
                Durée: {duration}
                Date de publication: {upload_date}
                Nombre de vues: {info['view_count']:,}
                """
                
                # Mise à jour du lecteur vidéo
                embed_html = f'''
                <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%; border-radius: 10px;">
                    <iframe 
                        style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none;"
                        src="https://www.youtube.com/embed/{video_id}"
                        title="YouTube video player"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen>
                    </iframe>
                </div>
                '''
                video_player_html = gr.update(visible=True, value=embed_html)
            except Exception as e:
                video_info = f"Erreur lors de la récupération des informations : {str(e)}"
    
    return video_info, video_player_html

def append_chat_history_to_google_doc(doc_id, chat_history):
    """Ajoute l'historique du chat au document Google Docs"""
    try:
        creds = authenticate_google_docs()
        service = build('docs', 'v1', credentials=creds)
        
        # Obtenir la taille actuelle du document
        document = service.documents().get(documentId=doc_id).execute()
        doc_end = document.get('body').get('content')[-1].get('endIndex')
        
        # Formatage de l'historique du chat
        chat_content = "\n\n=== Historique de la conversation ===\n\n"
        for user_msg, assistant_msg in chat_history:
            chat_content += f"\n👤 Utilisateur : {user_msg}\n"
            chat_content += f"🤖 Assistant : {assistant_msg}\n"
            chat_content += "----------------------------------------\n"
        
        requests = [
            {
                'insertText': {
                    'location': {
                        'index': doc_end - 1 if doc_end > 1 else 1,
                    },
                    'text': chat_content
                }
            }
        ]
        
        service.documents().batchUpdate(
            documentId=doc_id,
            body={'requests': requests}
        ).execute()
        
        return True, "Historique du chat exporté avec succès"
    except Exception as e:
        return False, f"Erreur lors de l'export du chat : {str(e)}"

# Interface Gradio
with gr.Blocks() as iface:
    gr.Markdown("# Transcription et Traduction de Fichiers Audio/Vidéo")

    with gr.Tab("Transcription et Traduction"):
        with gr.Row():
            with gr.Column(scale=1):
                input_type = gr.Radio(
                    ["Fichier", "Lien YouTube"],
                    label="Type d'entrée",
                    value="Fichier"
                )
                youtube_url = gr.Textbox(
                    label="URL YouTube",
                    placeholder="Entrez l'URL de la vidéo YouTube",
                    visible=False
                )
                uploaded_files = gr.File(
                    label="Fichiers Audio/Vidéo",
                    file_count="multiple"
                )
                batch_processing = gr.Checkbox(
                    label="Traitement par lots",
                    value=False
                )
                source_lang = gr.Dropdown(
                    choices=["auto"] + [lang[0] for lang in whisper.tokenizer.LANGUAGES.items()],
                    value="auto",
                    label="Langue source"
                )
                use_whisper = gr.Checkbox(
                    label="Utiliser Whisper pour la transcription",
                    value=True
                )
                use_translation = gr.Checkbox(
                    label="Activer la traduction",
                    value=False
                )
                target_lang = gr.Dropdown(
                    choices=[lang[0] for lang in whisper.tokenizer.LANGUAGES.items()],
                    value="fr",
                    label="Langue cible",
                    visible=False
                )
                export_to_gdocs = gr.Checkbox(
                    label="Exporter vers Google Docs",
                    value=False
                )
                gdocs_document_id = gr.Textbox(
                    label="ID du document Google Docs",
                    visible=False
                )
                with gr.Row():
                    process_btn = gr.Button("Traiter")
                    reset_btn = gr.Button("Nouvelle tâche", variant="secondary")
                    clear_gdocs_btn = gr.Button("Effacer l'ID Google Docs")

            with gr.Column(scale=2):
                # Ajout du lecteur vidéo YouTube
                video_player = gr.HTML(
                    label="Lecteur Vidéo",
                    visible=False
                )
                video_info = gr.Textbox(
                    label="Informations sur la vidéo",
                    visible=False
                )
                with gr.Tabs():
                    with gr.TabItem("Transcription"):
                        output_text = gr.Textbox(
                            label="Texte transcrit",
                            lines=10
                        )
                    with gr.TabItem("Traduction"):
                        translated_text = gr.Textbox(
                            label="Texte traduit",
                            lines=10,
                            visible=True
                        )
                status_output = gr.Textbox(
                    label="Statut",
                    lines=1
                )

    with gr.Tab("Chat"):
        gr.Markdown("""### Discutez avec l'Assistant
        Posez des questions sur le contenu transcrit et traduit. L'assistant utilisera ce contexte pour vous répondre de manière pertinente.""")
        
        chatbot = gr.Chatbot(
            label="Discussion",
            height=400
        )
        with gr.Row():
            msg = gr.Textbox(
                label="Votre message",
                placeholder="Posez votre question ici...",
                lines=2,
                scale=4
            )
            submit_btn = gr.Button("Envoyer", scale=1, variant="primary")
        
        with gr.Row():
            clear = gr.Button("Effacer la conversation")
            export_chat_btn = gr.Button("Exporter vers Google Docs", scale=1, variant="secondary")
            chat_status = gr.Textbox(label="Statut", lines=1, interactive=False, scale=2)

        def respond(message, chat_history):
            if not message.strip():  # Ignorer les messages vides
                return "", chat_history
            bot_message = chat_with_gpt(message, chat_history)
            chat_history.append((message, bot_message))
            return "", chat_history

        def export_chat(chat_history, gdocs_document_id):
            if not gdocs_document_id:
                return "Veuillez d'abord configurer un document Google Docs dans l'onglet principal"
            if not chat_history:
                return "Aucune conversation à exporter"
            
            success, message = append_chat_history_to_google_doc(gdocs_document_id, chat_history)
            return message

        # Lier le bouton d'envoi et la touche Entrée à la fonction respond
        submit_btn.click(respond, [msg, chatbot], [msg, chatbot])
        msg.submit(respond, [msg, chatbot], [msg, chatbot])  # Garder aussi la soumission par Entrée
        clear.click(lambda: None, None, chatbot, queue=False)
        export_chat_btn.click(
            export_chat,
            inputs=[chatbot, gdocs_document_id],
            outputs=[chat_status]
        )

    with gr.Tab("Text-to-Speech"):
        with gr.Row():
            tts_language = gr.Dropdown(
                label="Langue TTS",
                choices=['en-US', 'fr-FR', 'de-DE', 'es-ES'],
                value='en-US'
            )
            tts_voice = gr.Dropdown(
                label="Voix TTS",
                choices=['en-US-Wavenet-D', 'fr-FR-Wavenet-F', 'fr-FR-Wavenet-G'],
                value='en-US-Wavenet-D'
            )
            speed = gr.Slider(
                minimum=0.25,
                maximum=4.0,
                value=1.0,
                step=0.25,
                label="Vitesse"
            )
            pitch = gr.Slider(
                minimum=-20.0,
                maximum=20.0,
                value=0.0,
                step=1.0,
                label="Hauteur"
            )

        tts_btn = gr.Button("Générer l'Audio")
        audio_output = gr.Audio(label="Audio Généré")
        download_link = gr.Markdown("", label="Lien de Téléchargement")

        # Event handlers pour TTS
        tts_btn.click(
            generate_audio,
            inputs=[translated_text, tts_language, tts_voice, speed, pitch],
            outputs=[audio_output, download_link]
        )

        # Event handlers pour l'interface principale
        input_type.change(
            toggle_input_type,
            inputs=[input_type],
            outputs=[youtube_url, uploaded_files, video_info, batch_processing]
        )

        youtube_url.change(
            update_video_player_and_info,
            inputs=[youtube_url],
            outputs=[video_info, video_player]
        )

        use_translation.change(
            lambda x: gr.update(visible=x),
            inputs=[use_translation],
            outputs=[target_lang]
        )

        export_to_gdocs.change(
            lambda x: gr.update(visible=x),
            inputs=[export_to_gdocs],
            outputs=[gdocs_document_id]
        )

        process_btn.click(
            process_input,
            inputs=[
                input_type, youtube_url, uploaded_files,
                source_lang, target_lang,
                use_whisper, use_translation,
                export_to_gdocs, gdocs_document_id,
                batch_processing
            ],
            outputs=[output_text, translated_text, status_output]
        )

        reset_btn.click(
            reset_interface,
            outputs=[
                uploaded_files,
                output_text,
                translated_text,
                status_output,
                youtube_url,
                video_info,
                video_player,
                chatbot,
                msg,
                chat_status
            ]
        )

        clear_gdocs_btn.click(
            lambda: "",  # Simplement retourner une chaîne vide
            outputs=[gdocs_document_id]
        )

    iface.launch()
