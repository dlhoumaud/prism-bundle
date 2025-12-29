<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

// Essayer d'abord l'autoload du projet parent (quand le bundle est installé via path)
if (file_exists(dirname(__DIR__) . '/../vendor/autoload.php')) {
    $loader = require dirname(__DIR__) . '/../vendor/autoload.php';

    // Ajouter manuellement le namespace Tests\Prism\ pour les tests du bundle
    $loader->addPsr4('Tests\\Prism\\', dirname(__DIR__) . '/tests/');
} elseif (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    // Sinon, utiliser l'autoload du bundle lui-même (développement standalone)
    require dirname(__DIR__) . '/vendor/autoload.php';
} else {
    throw new RuntimeException('Unable to find autoload.php');
}

if (file_exists(dirname(__DIR__) . '/../.env.test.local')) {
    (new Dotenv())->bootEnv(dirname(__DIR__) . '/../.env.test.local');
}

if (isset($_SERVER['APP_DEBUG']) && $_SERVER['APP_DEBUG']) {
    umask(0000);
}
