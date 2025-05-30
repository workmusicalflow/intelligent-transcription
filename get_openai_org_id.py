#!/usr/bin/env python3
import os
import sys

try:
    import openai
except ImportError:
    print("Erreur : le module 'openai' n'est pas installé.\nInstallez-le avec : pip install openai")
    sys.exit(1)

def get_organization_id():
    """
    Tente de récupérer l'ID d'organisation OpenAI depuis la variable d'environnement OPENAI_ORG_ID.
    Affiche un message d'aide si l'ID n'est pas disponible.
    """
    org_id = os.getenv('OPENAI_ORG_ID')
    if org_id:
        print(f"ID d'organisation trouvé dans l'environnement : {org_id}")
        return org_id

    print("Aucun ID d'organisation trouvé dans l'environnement (OPENAI_ORG_ID non défini).")
    print("Vous pouvez le trouver dans votre tableau de bord OpenAI : https://platform.openai.com/account/org-settings")
    print("Ou bien, ajoutez la variable d'environnement OPENAI_ORG_ID à votre système.")
    return None

if __name__ == "__main__":
    org_id = get_organization_id()
    if org_id:
        print(f"\n✓ ID d'organisation prêt à être utilisé : {org_id}")
        print("Cet identifiant peut être utilisé pour la configuration avancée de l'API OpenAI.")
    else:
        print("\n✗ Impossible de récupérer l'ID d'organisation automatiquement.")
