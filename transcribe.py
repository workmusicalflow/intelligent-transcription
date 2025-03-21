#!/usr/bin/env python3

"""
Script de transcription audio avec OpenAI Whisper
Ce script est conçu pour être utilisé dans une architecture simplifiée
"""

import os
import sys
import json
import argparse
from dotenv import load_dotenv

# Charger les variables d'environnement depuis .env
load_dotenv()

# Vérifier si la clé API OpenAI est configurée
if not os.getenv("OPENAI_API_KEY"):
    print(json.dumps({
        "success": False,
        "error": "La clé API OpenAI n'est pas configurée dans le fichier .env"
    }))
    sys.exit(1)

try:
    import openai
except ImportError:
    print(json.dumps({
        "success": False,
        "error": "Le module openai n'est pas installé. Installez-le avec: pip install openai"
    }))
    sys.exit(1)

def transcribe_audio(file_path, language=None, force_language=False):
    """
    Transcrit un fichier audio avec OpenAI Whisper
    
    Args:
        file_path (str): Chemin vers le fichier audio
        language (str, optional): Code de langue (fr, en, etc.)
        force_language (bool, optional): Si True, force la traduction dans la langue spécifiée
        
    Returns:
        dict: Résultat de la transcription
    """
    try:
        # Vérifier si le fichier existe
        if not os.path.exists(file_path):
            return {"success": False, "error": f"Le fichier {file_path} n'existe pas"}
        
        # Traiter le paramètre de langue
        # Si la langue est une chaîne vide ou "auto", utiliser None pour la détection automatique
        if language == "" or language == "auto":
            language = None
            force_language = False
        
        # Ouvrir le fichier audio
        with open(file_path, "rb") as audio_file:
            # Appeler l'API OpenAI Whisper
            client = openai.OpenAI(api_key=os.getenv("OPENAI_API_KEY"))
            response = client.audio.transcriptions.create(
                model="whisper-1",
                file=audio_file,
                language=language
            )
            
            # Si force_language est True et language est spécifié, traduire le texte
            transcribed_text = response.text
            detected_language = "détecté automatiquement"
            
            if force_language and language:
                # Utiliser l'API OpenAI pour traduire le texte dans la langue spécifiée
                try:
                    translation_response = client.chat.completions.create(
                        model="gpt-3.5-turbo",
                        messages=[
                            {"role": "system", "content": f"Tu es un traducteur professionnel. Traduis le texte suivant en {language}, en conservant le style et le ton."},
                            {"role": "user", "content": transcribed_text}
                        ]
                    )
                    transcribed_text = translation_response.choices[0].message.content
                    detected_language = f"traduit en {language}"
                except Exception as e:
                    # En cas d'erreur de traduction, conserver le texte original
                    detected_language = f"transcrit en langue originale (échec de traduction: {str(e)})"
            
            # Retourner le résultat
            return {
                "success": True,
                "text": transcribed_text,
                "language": language or detected_language,
                "original_text": response.text if force_language and language else None
            }
    except Exception as e:
        return {"success": False, "error": str(e)}

def main():
    # Analyser les arguments de la ligne de commande
    parser = argparse.ArgumentParser(description="Transcription audio avec OpenAI Whisper")
    parser.add_argument("--file", required=True, help="Chemin vers le fichier audio")
    parser.add_argument("--language", help="Code de langue (fr, en, etc.)")
    parser.add_argument("--force-language", action="store_true", help="Force la traduction dans la langue spécifiée")
    parser.add_argument("--output", help="Chemin vers le fichier de sortie JSON")
    args = parser.parse_args()
    
    # Transcrire le fichier audio
    result = transcribe_audio(args.file, args.language, args.force_language)
    
    # Enregistrer le résultat dans un fichier JSON si demandé
    if args.output and result["success"]:
        with open(args.output, "w", encoding="utf-8") as f:
            json.dump(result, f, ensure_ascii=False, indent=2)
    
    # Afficher le résultat en JSON pour faciliter le traitement par PHP
    print(json.dumps(result))

if __name__ == "__main__":
    main()
