<?php

namespace clickrain\stratus\tests\_support;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

/**
 * Locates the plugin's own classes so tests can walk them without anyone
 * having to maintain a list by hand. A class added to src/ is covered the
 * moment it is committed.
 */
final class PluginSource
{
    public const ROOT_NAMESPACE = 'clickrain\\stratus\\';

    public static function srcPath(): string
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'src';
    }

    /**
     * Every class, interface, trait and enum declared under src/.
     *
     * @return list<class-string>
     */
    public static function classNames(): array
    {
        $src = self::srcPath();
        $names = [];

        /** @var SplFileInfo $file */
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src)) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relative = substr($file->getPathname(), strlen($src) + 1);
            $name = self::ROOT_NAMESPACE . str_replace(DIRECTORY_SEPARATOR, '\\', substr($relative, 0, -4));

            // src/config.php and the translation files are plain arrays living
            // in a PSR-4 tree, so ask the autoloader rather than assuming.
            if (class_exists($name) || interface_exists($name) || trait_exists($name) || enum_exists($name)) {
                $names[] = $name;
            }
        }

        sort($names);

        return $names;
    }

    /**
     * Every type the given class inherits from: parent classes, all interfaces
     * and all traits, at any depth.
     *
     * @return list<ReflectionClass>
     */
    public static function ancestors(ReflectionClass $class): array
    {
        $ancestors = [];

        for ($parent = $class->getParentClass(); $parent !== false; $parent = $parent->getParentClass()) {
            $ancestors[$parent->getName()] = $parent;
        }

        foreach ($class->getInterfaces() as $interface) {
            $ancestors[$interface->getName()] = $interface;
        }

        foreach (self::traits($class) as $trait) {
            $ancestors[$trait->getName()] = $trait;
        }

        return array_values($ancestors);
    }

    /**
     * @return list<ReflectionClass>
     */
    private static function traits(ReflectionClass $class): array
    {
        $traits = [];

        for ($current = $class; $current !== false; $current = $current->getParentClass()) {
            foreach ($current->getTraits() as $trait) {
                $traits[$trait->getName()] = $trait;
                foreach (self::traits($trait) as $nested) {
                    $traits[$nested->getName()] = $nested;
                }
            }
        }

        return array_values($traits);
    }
}
