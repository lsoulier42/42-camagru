<?php

declare(strict_types=1);

namespace App\Core;

use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

/**
 * Conteneur minimal (zéro dépendance) : résout une classe en construisant
 * ses dépendances par réflexion (autowiring sur le constructeur), avec des
 * fabriques explicites pour les cas particuliers (ex. PDO).
 *
 * Les instances sont des singletons : une même classe est résolue une seule
 * fois (une seule connexion PDO, des repositories réutilisables).
 */
final class Container
{
    /** @var array<string, callable(): object> */
    private array $factories = [];

    /** @var array<string, object> */
    private array $instances = [];

    public function set(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
    }

    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        $instance = isset($this->factories[$id])
            ? ($this->factories[$id])()
            : $this->autowire($id);

        return $this->instances[$id] = $instance;
    }

    private function autowire(string $class): object
    {
        if (!class_exists($class)) {
            throw new RuntimeException("Impossible de résoudre « {$class} » : classe inconnue.");
        }

        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                throw new RuntimeException(
                    "Impossible de résoudre le paramètre « {$parameter->getName()} » de {$class} : "
                    . 'le conteneur ne résout que des classes.'
                );
            }
            $arguments[] = $this->get($type->getName());
        }

        return $reflection->newInstanceArgs($arguments);
    }
}
