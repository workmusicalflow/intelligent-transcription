#!/bin/bash

# Script pour initialiser l'environnement virtuel Python
# Ce script crée un environnement virtuel et installe les dépendances nécessaires

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Initialisation de l'environnement Python pour la transcription audio...${NC}"

# Vérifier si Python 3 est installé
if ! command -v python3 &> /dev/null; then
    echo -e "${RED}Python 3 n'est pas installé. Veuillez l'installer avant de continuer.${NC}"
    exit 1
fi

# Vérifier si pip est installé
if ! command -v pip3 &> /dev/null; then
    echo -e "${RED}pip3 n'est pas installé. Veuillez l'installer avant de continuer.${NC}"
    exit 1
fi

# Vérifier si venv est disponible
if ! python3 -m venv --help &> /dev/null; then
    echo -e "${RED}Le module venv n'est pas disponible. Veuillez l'installer avant de continuer.${NC}"
    echo -e "${YELLOW}Sur Ubuntu/Debian: sudo apt-get install python3-venv${NC}"
    echo -e "${YELLOW}Sur macOS: pip3 install virtualenv${NC}"
    exit 1
fi

# Créer l'environnement virtuel s'il n'existe pas
if [ ! -d "venv" ]; then
    echo -e "${YELLOW}Création de l'environnement virtuel dans venv...${NC}"
    python3 -m venv venv
    if [ $? -ne 0 ]; then
        echo -e "${RED}Erreur lors de la création de l'environnement virtuel.${NC}"
        exit 1
    fi
    echo -e "${GREEN}Environnement virtuel créé avec succès.${NC}"
else
    echo -e "${YELLOW}L'environnement virtuel existe déjà dans venv.${NC}"
fi

# Activer l'environnement virtuel et installer les dépendances
echo -e "${YELLOW}Installation des dépendances...${NC}"
source venv/bin/activate
if [ $? -ne 0 ]; then
    echo -e "${RED}Erreur lors de l'activation de l'environnement virtuel.${NC}"
    exit 1
fi

# Mettre à jour pip
echo -e "${YELLOW}Mise à jour de pip...${NC}"
pip install --upgrade pip
if [ $? -ne 0 ]; then
    echo -e "${RED}Erreur lors de la mise à jour de pip.${NC}"
    exit 1
fi

# Installer les dépendances depuis le fichier requirements.txt
echo -e "${YELLOW}Installation des dépendances depuis requirements.txt...${NC}"
pip install -r requirements.txt
if [ $? -ne 0 ]; then
    echo -e "${RED}Erreur lors de l'installation des dépendances.${NC}"
    exit 1
fi

echo -e "${GREEN}Installation des dépendances terminée avec succès.${NC}"
echo -e "${YELLOW}Pour activer l'environnement virtuel, exécutez:${NC}"
echo -e "${YELLOW}source venv/bin/activate${NC}"

# Désactiver l'environnement virtuel
deactivate

# Mettre à jour le fichier config.php pour utiliser le nouvel environnement virtuel
echo -e "${YELLOW}Mise à jour du fichier config.php...${NC}"
sed -i.bak "s|define('PYTHON_PATH', '.*')|define('PYTHON_PATH', __DIR__ . '/venv/bin/python')|" config.php
if [ $? -ne 0 ]; then
    echo -e "${RED}Erreur lors de la mise à jour du fichier config.php.${NC}"
    exit 1
fi

echo -e "${GREEN}Configuration de l'environnement Python terminée.${NC}"
echo -e "${GREEN}Vous pouvez maintenant utiliser l'application de transcription audio.${NC}"
