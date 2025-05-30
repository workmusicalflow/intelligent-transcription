#!/usr/bin/env python3

"""
Script de paraphrase de texte avec OpenAI Assistant
Ce script utilise l'API OpenAI Assistant pour paraphraser un texte
"""

import os
import sys
import json
import argparse
from dotenv import load_dotenv
import openai
from openai import OpenAI
from typing_extensions import override
from openai import AssistantEventHandler

# Charger les variables d'environnement depuis différents emplacements possibles
env_paths = [
    os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(os.path.dirname(__file__)))), 'inteligent-transcription-env', '.env'),
    os.path.join(os.path.dirname(os.path.abspath(__file__)), '.env')
]

for env_path in env_paths:
    if os.path.exists(env_path):
        load_dotenv(env_path)
        break

# Vérifier si la clé API OpenAI est configurée
if not os.getenv("OPENAI_API_KEY"):
    print(json.dumps({
        "success": False,
        "error": "La clé API OpenAI n'est pas configurée dans le fichier .env"
    }))
    sys.exit(1)

# Vérifier si l'ID de l'assistant est configuré
paraphraser_assistant_id = os.getenv("PARAPHRASER_ASSISTANT_ID")
if not paraphraser_assistant_id:
    # Si l'ID de l'assistant n'est pas configuré, nous allons créer un nouvel assistant
    try:
        client = OpenAI(
            api_key=os.getenv("OPENAI_API_KEY"),
            organization=os.getenv("OPENAI_ORG_ID", "org-HzNhomFpeY5ewhrUNlmpTehv")
        )
        assistant = client.beta.assistants.create(
            name="Paraphraser",
            instructions="""Tu es un assistant spécialisé dans la paraphrase de texte. 
Ta tâche est de reformuler le texte fourni pour le rendre plus clair, plus fluide et plus professionnel, tout en conservant le sens original. 
Améliore la structure des phrases, le vocabulaire et la cohérence globale. 
Ne réponds qu'avec le texte paraphrasé, sans commentaires ni explications.
IMPORTANT: Tu dois toujours paraphraser dans la même langue que le texte original. 
Si le texte est en français, ta réponse doit être en français.
Si le texte est en anglais, ta réponse doit être en anglais.
Ne traduis jamais le texte dans une autre langue.""",
            model="gpt-4o-mini",
        )
        paraphraser_assistant_id = assistant.id
        print(f"Nouvel assistant créé avec l'ID: {paraphraser_assistant_id}")
        print(f"Ajoutez cet ID à votre fichier .env: PARAPHRASER_ASSISTANT_ID={paraphraser_assistant_id}")
    except Exception as e:
        print(json.dumps({
            "success": False,
            "error": f"Erreur lors de la création de l'assistant: {str(e)}"
        }))
        sys.exit(1)

# Variables globales pour le compteur d'utilisations et le thread
paraphraser_usage_counter = 0
MAX_USAGE_BEFORE_CLEANUP = 5
paraphraser_thread = None

# Classe pour gérer les événements de streaming
class EventHandler(AssistantEventHandler):
    def __init__(self):
        super().__init__()
        self.paraphrased_text = ""
    
    @override
    def on_text_created(self, text) -> None:
        pass
    
    @override
    def on_text_delta(self, delta, snapshot):
        self.paraphrased_text += delta.value

# Fonction pour supprimer le thread
def delete_thread(thread_id):
    try:
        client = OpenAI(
            api_key=os.getenv("OPENAI_API_KEY"),
            organization=os.getenv("OPENAI_ORG_ID", "org-HzNhomFpeY5ewhrUNlmpTehv")
        )
        client.beta.threads.delete(thread_id)
        print(f"Thread {thread_id} supprimé avec succès.")
    except Exception as e:
        print(f"Erreur lors de la suppression du thread {thread_id}: {str(e)}")

def paraphrase_text(text, language="fr"):
    """
    Paraphrase un texte avec l'API OpenAI Assistant
    
    Args:
        text (str): Texte à paraphraser
        language (str, optional): Code de langue (fr, en, etc.)
        
    Returns:
        dict: Résultat de la paraphrase
    """
    global paraphraser_thread, paraphraser_usage_counter, MAX_USAGE_BEFORE_CLEANUP, paraphraser_assistant_id
    
    try:
        # Vérifier si le texte est vide
        if not text or text.strip() == "":
            return {"success": False, "error": "Le texte à paraphraser est vide"}
        
        # Initialiser le client OpenAI
        client = OpenAI(
            api_key=os.getenv("OPENAI_API_KEY"),
            organization=os.getenv("OPENAI_ORG_ID", "org-HzNhomFpeY5ewhrUNlmpTehv")
        )
        
        # Si le thread n'existe pas, le créer
        if paraphraser_thread is None:
            paraphraser_thread = client.beta.threads.create()
            print(f"Nouveau thread créé: {paraphraser_thread.id}")
        
        # Ajouter le message de l'utilisateur au Thread
        message = client.beta.threads.messages.create(
            thread_id=paraphraser_thread.id,
            role="user",
            content=text
        )
        
        # Créer un EventHandler pour gérer le streaming
        event_handler = EventHandler()
        
        # Créer et streamer le Run avec l'assistant
        with client.beta.threads.runs.stream(
            thread_id=paraphraser_thread.id,
            assistant_id=paraphraser_assistant_id,
            event_handler=event_handler
        ) as stream:
            stream.until_done()
        
        # Récupérer le texte paraphrasé depuis l'EventHandler
        paraphrased_text = event_handler.paraphrased_text.strip()
        
        # Incrémenter le compteur d'utilisations
        paraphraser_usage_counter += 1
        print(f"Compteur d'utilisations: {paraphraser_usage_counter}")
        
        # Vérifier si nous devons nettoyer le thread après MAX_USAGE_BEFORE_CLEANUP utilisations
        if paraphraser_usage_counter >= MAX_USAGE_BEFORE_CLEANUP:
            delete_thread(paraphraser_thread.id)
            paraphraser_usage_counter = 0  # Réinitialiser le compteur
            paraphraser_thread = None      # Réinitialiser le thread
        
        # Retourner le résultat
        return {
            "success": True,
            "original_text": text,
            "paraphrased_text": paraphrased_text,
            "language": language
        }
    except Exception as e:
        return {"success": False, "error": str(e)}

def main():
    # Analyser les arguments de la ligne de commande
    parser = argparse.ArgumentParser(description="Paraphrase de texte avec OpenAI")
    parser.add_argument("--text", help="Texte à paraphraser")
    parser.add_argument("--file", help="Chemin vers le fichier contenant le texte à paraphraser")
    parser.add_argument("--output", help="Chemin vers le fichier de sortie JSON")
    parser.add_argument("--language", default="fr", help="Code de langue (fr, en, etc.)")
    args = parser.parse_args()
    
    # Récupérer le texte à paraphraser
    text = ""
    if args.text:
        text = args.text
    elif args.file:
        try:
            with open(args.file, "r", encoding="utf-8") as f:
                text = f.read()
        except Exception as e:
            result = {"success": False, "error": f"Erreur lors de la lecture du fichier: {str(e)}"}
            print(json.dumps(result))
            sys.exit(1)
    else:
        result = {"success": False, "error": "Vous devez spécifier un texte ou un fichier"}
        print(json.dumps(result))
        sys.exit(1)
    
    # Paraphraser le texte
    result = paraphrase_text(text, args.language)
    
    # Enregistrer le résultat dans un fichier JSON si demandé
    if args.output:
        try:
            with open(args.output, "w", encoding="utf-8") as f:
                json.dump(result, f, ensure_ascii=False, indent=2)
            print(f"Résultat enregistré dans {args.output}")
        except Exception as e:
            error_msg = f"Erreur lors de l'écriture du fichier de sortie: {str(e)}"
            print(error_msg)
            result = {"success": False, "error": error_msg}
    
    # Afficher le résultat en JSON pour faciliter le traitement par PHP
    print(json.dumps(result))

if __name__ == "__main__":
    main()
