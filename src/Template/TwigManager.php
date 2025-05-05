<?php

namespace Template;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class TwigManager
{
    private static ?Environment $instance = null;
    private static array $globalVars = [];

    /**
     * Initialiser l'instance Twig
     *
     * @param string $templatesDir Chemin vers le répertoire des templates
     * @param bool $useCache Si true, active le cache Twig
     * @param string|null $cacheDir Chemin vers le répertoire de cache
     * @return Environment
     */
    public static function getInstance(
        string $templatesDir = TEMPLATES_DIR,
        bool $useCache = false,
        ?string $cacheDir = null
    ): Environment {
        if (self::$instance === null) {
            $loader = new FilesystemLoader($templatesDir);

            $options = [];
            if ($useCache) {
                $options['cache'] = $cacheDir ?: \CACHE_DIR;
            }
            $options['debug'] = \DEBUG_MODE;
            $options['auto_reload'] = \DEBUG_MODE;

            self::$instance = new Environment($loader, $options);

            // Ajouter les variables et fonctions globales
            self::registerGlobals();
            self::registerFunctions();
        }

        return self::$instance;
    }

    /**
     * Ajouter une variable globale disponible dans tous les templates
     *
     * @param string $name Nom de la variable
     * @param mixed $value Valeur de la variable
     */
    public static function addGlobal(string $name, $value): void
    {
        self::$globalVars[$name] = $value;

        if (self::$instance !== null) {
            self::$instance->addGlobal($name, $value);
        }
    }

    /**
     * Enregistrer les variables globales
     */
    private static function registerGlobals(): void
    {
        // Ajouter certaines constantes de l'application
        self::addGlobal('app_name', 'Transcription Audio');
        self::addGlobal('app_version', '1.0.0');
        self::addGlobal('base_url', \BASE_URL ?? '');
        self::addGlobal('is_debug', \DEBUG_MODE);
        
        // Ajouter des informations d'authentification
        if (class_exists('\\Services\\AuthService')) {
            // Initialize authentication if not already done
            if (method_exists('\\Services\\AuthService', 'init')) {
                \Services\AuthService::init();
            }
            
            // Add authentication globals
            self::addGlobal('is_authenticated', method_exists('\\Services\\AuthService', 'isAuthenticated') 
                ? \Services\AuthService::isAuthenticated() 
                : false);
                
            self::addGlobal('is_admin', method_exists('\\Services\\AuthService', 'isAdmin') 
                ? \Services\AuthService::isAdmin() 
                : false);
            
            // Add current user if authenticated
            if (method_exists('\\Services\\AuthService', 'isAuthenticated') && 
                \Services\AuthService::isAuthenticated() &&
                method_exists('\\Services\\AuthService', 'getCurrentUser')) {
                    
                $currentUser = \Services\AuthService::getCurrentUser();
                self::addGlobal('current_user', method_exists($currentUser, 'toArray') 
                    ? $currentUser->toArray() 
                    : []);
            }
        }

        // Ajouter les variables personnalisées
        foreach (self::$globalVars as $name => $value) {
            self::$instance->addGlobal($name, $value);
        }
    }

    /**
     * Enregistrer les fonctions personnalisées
     */
    private static function registerFunctions(): void
    {
        // Fonction pour vérifier si une chaîne commence par une autre
        self::$instance->addFunction(new TwigFunction('str_starts_with', function ($haystack, $needle) {
            return str_starts_with($haystack, $needle);
        }));

        // Fonction pour générer un URL avec les paramètres GET
        self::$instance->addFunction(new TwigFunction('url', function ($path, array $params = []) {
            $url = $path;
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
            return $url;
        }));

        // Fonction pour traduire le texte (à implémenter plus tard)
        self::$instance->addFunction(new TwigFunction('t', function ($text) {
            // Pour l'instant, juste retourner le texte
            return $text;
        }));

        // Fonction pour formater une date
        self::$instance->addFunction(new TwigFunction('format_date', function ($timestamp, $format = 'd/m/Y H:i') {
            return date($format, is_numeric($timestamp) ? $timestamp : strtotime($timestamp));
        }));
    }

    /**
     * Rendre un template avec des variables
     *
     * @param string $template Nom du template à rendre
     * @param array $variables Variables à passer au template
     * @return string Contenu HTML généré
     */
    public static function render(string $template, array $variables = []): string
    {
        return self::getInstance()->render($template, $variables);
    }

    /**
     * Rendre un template directement dans la sortie
     *
     * @param string $template Nom du template à rendre
     * @param array $variables Variables à passer au template
     */
    public static function display(string $template, array $variables = []): void
    {
        echo self::render($template, $variables);
    }
}
