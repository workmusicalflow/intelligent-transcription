#!/usr/bin/env python3

"""
Script de prétraitement audio pour réduire la taille des fichiers avant transcription
Ce script utilise FFmpeg pour convertir les fichiers audio/vidéo en format MP3 avec un bitrate réduit
"""

import os
import sys
import json
import argparse
import subprocess
import tempfile
from pathlib import Path

def get_file_size_mb(file_path):
    """Retourne la taille du fichier en Mo"""
    return os.path.getsize(file_path) / (1024 * 1024)

def preprocess_audio(input_file, output_dir=None, target_size_mb=25):
    """
    Prétraite un fichier audio/vidéo pour réduire sa taille
    
    Args:
        input_file (str): Chemin vers le fichier d'entrée
        output_dir (str, optional): Répertoire de sortie
        target_size_mb (int, optional): Taille cible en Mo
        
    Returns:
        dict: Résultat du prétraitement
    """
    try:
        # Vérifier si le fichier existe
        if not os.path.exists(input_file):
            return {"success": False, "error": f"Le fichier {input_file} n'existe pas"}
        
        # Vérifier si FFmpeg est installé
        try:
            subprocess.run(["ffmpeg", "-version"], stdout=subprocess.PIPE, stderr=subprocess.PIPE, check=True)
        except (subprocess.SubprocessError, FileNotFoundError):
            return {"success": False, "error": "FFmpeg n'est pas installé ou n'est pas dans le PATH"}
        
        # Obtenir la taille du fichier en Mo
        file_size_mb = get_file_size_mb(input_file)
        
        # Si le fichier est déjà plus petit que la taille cible, le copier simplement
        if file_size_mb <= target_size_mb:
            if output_dir:
                output_file = os.path.join(output_dir, os.path.basename(input_file))
                if os.path.abspath(input_file) != os.path.abspath(output_file):
                    import shutil
                    shutil.copy2(input_file, output_file)
            else:
                output_file = input_file
                
            return {
                "success": True,
                "input_file": input_file,
                "output_file": output_file,
                "original_size_mb": file_size_mb,
                "new_size_mb": file_size_mb,
                "message": "Le fichier est déjà plus petit que la taille cible"
            }
        
        # Déterminer le fichier de sortie
        if output_dir:
            os.makedirs(output_dir, exist_ok=True)
            base_name = os.path.splitext(os.path.basename(input_file))[0]
            output_file = os.path.join(output_dir, f"{base_name}_preprocessed.mp3")
        else:
            # Créer un fichier temporaire si aucun répertoire de sortie n'est spécifié
            temp_dir = tempfile.gettempdir()
            base_name = os.path.splitext(os.path.basename(input_file))[0]
            output_file = os.path.join(temp_dir, f"{base_name}_preprocessed.mp3")
        
        # Calculer le bitrate cible en fonction de la taille cible
        # Formule approximative: bitrate (kbps) = taille cible (Mo) * 8192 / durée (s)
        # Nous utilisons une estimation de la durée basée sur la taille actuelle et un bitrate moyen de 128 kbps
        estimated_duration_s = (file_size_mb * 8192) / 128
        target_bitrate_kbps = int((target_size_mb * 8192) / estimated_duration_s)
        
        # Limiter le bitrate entre 32 et 192 kbps pour assurer une qualité acceptable
        target_bitrate_kbps = max(32, min(192, target_bitrate_kbps))
        
        # Exécuter FFmpeg pour convertir le fichier
        command = [
            "ffmpeg",
            "-i", input_file,
            "-c:a", "libmp3lame",
            "-b:a", f"{target_bitrate_kbps}k",
            "-ac", "1",  # Mono
            "-ar", "22050",  # Fréquence d'échantillonnage réduite
            "-y",  # Écraser le fichier de sortie s'il existe
            output_file
        ]
        
        process = subprocess.run(command, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        
        if process.returncode != 0:
            return {"success": False, "error": f"Erreur FFmpeg: {process.stderr.decode('utf-8')}"}
        
        # Vérifier la taille du fichier de sortie
        new_size_mb = get_file_size_mb(output_file)
        
        return {
            "success": True,
            "input_file": input_file,
            "output_file": output_file,
            "original_size_mb": file_size_mb,
            "new_size_mb": new_size_mb,
            "bitrate_kbps": target_bitrate_kbps,
            "message": f"Fichier prétraité avec succès. Taille réduite de {file_size_mb:.2f} Mo à {new_size_mb:.2f} Mo"
        }
    except Exception as e:
        return {"success": False, "error": str(e)}

def main():
    parser = argparse.ArgumentParser(description="Prétraitement audio pour réduire la taille des fichiers")
    parser.add_argument("--file", required=True, help="Chemin vers le fichier audio/vidéo")
    parser.add_argument("--output_dir", help="Répertoire de sortie")
    parser.add_argument("--target_size_mb", type=int, default=25, help="Taille cible en Mo (par défaut: 25)")
    args = parser.parse_args()
    
    result = preprocess_audio(args.file, args.output_dir, args.target_size_mb)
    print(json.dumps(result, indent=2))

if __name__ == "__main__":
    main()
