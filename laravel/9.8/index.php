<?php

// Create a simple "default" Doctrine ORM configuration for Annotations
use App\Models\Post;
use App\Models\PostRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;

require_once 'vendor/autoload.php';

$isDevMode = true;
$proxyDir = null;
$cache = null;
$useSimpleAnnotationReader = false;

$config = Setup::createAnnotationMetadataConfiguration(array(__DIR__."/src"), $isDevMode, $proxyDir, null, $useSimpleAnnotationReader);
// or if you prefer yaml or XML
// $config = Setup::createXMLMetadataConfiguration(array(__DIR__."/config/xml"), $isDevMode);
// $config = Setup::createYAMLMetadataConfiguration(array(__DIR__."/config/yaml"), $isDevMode);

// database configuration parameters
$conn = array(
    'driver' => 'pdo_sqlite',
    'path' => __DIR__ . '/db.sqlite',
);

// obtaining the entity manager
$entityManager = EntityManager::create($conn, $config);
$postRepository = new PostRepository($entityManager, $entityManager->getClassMetadata(Post::class));
$post = $postRepository->find(1);
//$post = $entityManager->getRepository(Post::class);
var_dump($post::class);

$posts = $postRepository->getRecentBugs();
$p = $posts[0];
$v = $p->getName();

return;

