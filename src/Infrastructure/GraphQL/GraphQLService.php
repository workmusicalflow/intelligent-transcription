<?php

namespace Infrastructure\GraphQL;

use TheCodingMachine\GraphQLite\SchemaFactory;
use TheCodingMachine\GraphQLite\Context\Context;
use GraphQL\Type\Schema;
use GraphQL\GraphQL;
use GraphQL\Error\DebugFlag;
use Psr\Container\ContainerInterface;
use Infrastructure\Container\ServiceLocator;

/**
 * Service principal GraphQL avec GraphQLite
 */
class GraphQLService
{
    private Schema $schema;
    private ContainerInterface $container;

    public function __construct()
    {
        $this->container = ServiceLocator::getContainer();
        $this->schema = $this->createSchema();
    }

    /**
     * Crée le schéma GraphQL
     */
    private function createSchema(): Schema
    {
        /** @var \Psr\SimpleCache\CacheInterface $cache */
        $cache = $this->container->get(\Psr\SimpleCache\CacheInterface::class);
        
        // Vérifier que nous avons bien une instance de CacheInterface
        if (!$cache instanceof \Psr\SimpleCache\CacheInterface) {
            throw new \RuntimeException('Cache service must implement Psr\\SimpleCache\\CacheInterface');
        }
        
        // SchemaFactory attend (cache, container) et non (container, cache)
        $factory = new SchemaFactory(
            $cache,
            $this->container
        );

        // Configuration des namespaces (nouvelle méthode non dépréciée)
        $factory->addNamespace('Infrastructure\\GraphQL\\Controller');
        $factory->addNamespace('Infrastructure\\GraphQL\\Type');

        // Configuration de production vs développement
        if ($_ENV['APP_ENV'] === 'production') {
            $factory->prodMode();
        } else {
            $factory->devMode();
        }

        return $factory->createSchema();
    }

    /**
     * Exécute une requête GraphQL
     */
    public function executeQuery(
        string $query,
        ?array $variables = null,
        ?string $operationName = null,
        ?array $context = null
    ): array {
        try {
            // Créer le contexte avec les données fournies
            $contextData = $context ?? [];
            $graphqlContext = new Context($contextData);

            // Exécuter la requête
            $result = GraphQL::executeQuery(
                $this->schema,
                $query,
                null,
                $graphqlContext,
                $variables,
                $operationName
            );

            // Configuration du debug selon l'environnement
            $debug = $_ENV['APP_ENV'] === 'development'
                ? DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE
                : DebugFlag::NONE;

            return $result->toArray($debug);
        } catch (\Exception $e) {
            return [
                'errors' => [[
                    'message' => $e->getMessage(),
                    'locations' => [],
                    'path' => []
                ]]
            ];
        }
    }

    /**
     * Retourne le schéma SDL pour introspection
     */
    public function getSchemaSDL(): string
    {
        return \GraphQL\Utils\SchemaPrinter::doPrint($this->schema);
    }

    /**
     * Valide une requête GraphQL
     */
    public function validateQuery(string $query): array
    {
        try {
            $ast = \GraphQL\Language\Parser::parse($query);
            $errors = \GraphQL\Validator\DocumentValidator::validate($this->schema, $ast);

            return array_map(function ($error) {
                return $error->getMessage();
            }, $errors);
        } catch (\Exception $e) {
            return [$e->getMessage()];
        }
    }
}
