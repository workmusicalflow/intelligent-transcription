#!/bin/bash

# Script de vérification qualité pour le frontend Vue.js
# Utilise vue-tsc pour maintenir la qualité TypeScript

echo "🔍 Vérification qualité TypeScript..."
echo "=================================="

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonction pour afficher avec couleur
print_status() {
    local status=$1
    local message=$2
    case $status in
        "success")
            echo -e "${GREEN}✅ $message${NC}"
            ;;
        "error")
            echo -e "${RED}❌ $message${NC}"
            ;;
        "warning")
            echo -e "${YELLOW}⚠️  $message${NC}"
            ;;
        "info")
            echo -e "${BLUE}ℹ️  $message${NC}"
            ;;
    esac
}

# Vérifier que nous sommes dans le bon répertoire
if [ ! -f "package.json" ]; then
    print_status "error" "Erreur: package.json non trouvé. Exécutez ce script depuis le répertoire frontend."
    exit 1
fi

# Démarrer la vérification
print_status "info" "Démarrage de la vérification qualité..."

# 1. Vérification TypeScript avec vue-tsc
print_status "info" "Vérification TypeScript avec vue-tsc..."
if npx vue-tsc --noEmit --pretty > /tmp/tsc-output.log 2>&1; then
    print_status "success" "TypeScript: Aucune erreur détectée"
else
    print_status "error" "TypeScript: Erreurs détectées"
    echo -e "${YELLOW}Détails des erreurs:${NC}"
    cat /tmp/tsc-output.log
    echo ""
    echo -e "${BLUE}💡 Conseils pour corriger:${NC}"
    echo "   • Vérifiez les types manquants"
    echo "   • Assurez-vous que toutes les props sont correctement typées"
    echo "   • Vérifiez les refs de template"
    echo ""
    exit 1
fi

# 2. Vérification de la structure des composants
print_status "info" "Vérification de la structure des composants..."

# Compter les composants implémentés vs non implémentés
TOTAL_COMPONENTS=$(find src/views -name "*.vue" | wc -l)
TODO_COMPONENTS=$(grep -r "TODO.*Implement" src/views --include="*.vue" | wc -l)
IMPLEMENTED_COMPONENTS=$((TOTAL_COMPONENTS - TODO_COMPONENTS))

echo -e "${BLUE}📊 Statistiques des composants:${NC}"
echo "   • Total: $TOTAL_COMPONENTS composants"
echo "   • Implémentés: $IMPLEMENTED_COMPONENTS composants"
echo "   • À implémenter: $TODO_COMPONENTS composants"

if [ $TODO_COMPONENTS -gt 0 ]; then
    print_status "warning" "$TODO_COMPONENTS composants restent à implémenter"
    echo -e "${YELLOW}Composants avec TODO:${NC}"
    grep -r "TODO.*Implement" src/views --include="*.vue" -l | sed 's/^/   • /'
else
    print_status "success" "Tous les composants sont implémentés"
fi

# 3. Vérification des imports manquants
print_status "info" "Vérification des imports manquants..."
MISSING_IMPORTS=$(grep -r "import.*from.*undefined" src --include="*.vue" --include="*.ts" | wc -l)
if [ $MISSING_IMPORTS -eq 0 ]; then
    print_status "success" "Aucun import manquant détecté"
else
    print_status "warning" "$MISSING_IMPORTS imports potentiellement problématiques"
fi

# 4. Vérification de la configuration Tailwind
print_status "info" "Vérification de la configuration Tailwind..."
if [ -f "tailwind.config.js" ]; then
    print_status "success" "Configuration Tailwind trouvée"
else
    print_status "warning" "Configuration Tailwind manquante"
fi

# 5. Vérification des tests
print_status "info" "Vérification des tests..."
TEST_FILES=$(find src -name "*.test.ts" -o -name "*.spec.ts" | grep -v disabled | wc -l)
DISABLED_TESTS=$(find src -name "*.test.ts.disabled" -o -name "*.spec.ts.disabled" | wc -l)

echo -e "${BLUE}🧪 Statistiques des tests:${NC}"
echo "   • Tests actifs: $TEST_FILES"
echo "   • Tests désactivés: $DISABLED_TESTS"

if [ $TEST_FILES -gt 0 ]; then
    print_status "success" "Tests unitaires présents"
else
    print_status "warning" "Aucun test unitaire actif"
fi

# 6. Vérification des performances potentielles
print_status "info" "Vérification des bonnes pratiques..."

# Vérifier l'utilisation de console.log en production
CONSOLE_LOGS=$(grep -r "console\.log" src --include="*.vue" --include="*.ts" | wc -l)
if [ $CONSOLE_LOGS -eq 0 ]; then
    print_status "success" "Aucun console.log détecté"
else
    print_status "warning" "$CONSOLE_LOGS console.log trouvés (à nettoyer pour la production)"
fi

# Vérifier les computed non utilisés ou les refs non réactives
UNUSED_REFS=$(grep -r "ref<.*>.*=" src --include="*.vue" | grep -v "\.value" | wc -l)
if [ $UNUSED_REFS -lt 5 ]; then
    print_status "success" "Utilisation des refs semble correcte"
else
    print_status "warning" "Vérifiez l'utilisation des refs (.value manquant?)"
fi

# 7. Score global
print_status "info" "Calcul du score qualité..."

SCORE=100
[ $TODO_COMPONENTS -gt 0 ] && SCORE=$((SCORE - TODO_COMPONENTS * 5))
[ $MISSING_IMPORTS -gt 0 ] && SCORE=$((SCORE - MISSING_IMPORTS * 10))
[ $TEST_FILES -eq 0 ] && SCORE=$((SCORE - 20))
[ $CONSOLE_LOGS -gt 0 ] && SCORE=$((SCORE - CONSOLE_LOGS * 2))
[ $DISABLED_TESTS -gt 0 ] && SCORE=$((SCORE - DISABLED_TESTS * 5))

echo ""
echo "=================================="
if [ $SCORE -ge 90 ]; then
    print_status "success" "Score qualité: $SCORE/100 - Excellent!"
elif [ $SCORE -ge 80 ]; then
    print_status "success" "Score qualité: $SCORE/100 - Très bien"
elif [ $SCORE -ge 70 ]; then
    print_status "warning" "Score qualité: $SCORE/100 - Bien, amélioration possible"
else
    print_status "warning" "Score qualité: $SCORE/100 - Nécessite des améliorations"
fi

echo ""
print_status "info" "Vérification terminée!"
echo -e "${BLUE}💡 Prochaines étapes recommandées:${NC}"
echo "   1. Implémenter les composants TODO restants"
echo "   2. Ajouter des tests unitaires"
echo "   3. Nettoyer les console.log"
echo "   4. Vérifier les performances"

# Nettoyage
rm -f /tmp/tsc-output.log

exit 0