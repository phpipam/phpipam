<?php
/**
 * Regenerates public/api/openapi.yaml from the #[OA\...] attributes found on
 * public/api/controllers/*.php and public/api/docs/generator/annotations/*.php.
 *
 * Dev/build-time only script, never executed by the running application.
 * swagger-php's generator uses PHP Reflection, so the target classes must be
 * loaded (require'd) before scanning — they are never instantiated here.
 *
 * Usage:
 *   composer install
 *   php public/api/docs/generator/generate.php
 */

$root = dirname(__DIR__, 4);

$autoload = $root.'/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "Composer dependencies are not installed.\nRun: composer install\n");
    exit(1);
}
require $autoload;

require $root.'/functions/version.php';

// Load the class hierarchy the controllers depend on, without running the
// app bootstrap (no config, no DB, no session).
require $root.'/functions/classes/class.Params.php';
require $root.'/functions/classes/class.Result.php';
require $root.'/public/api/controllers/Common.php';

$controllerFiles = [
    'Sections.php',
    'Subnets.php',
    'Addresses.php',
    'Vlans.php',
    'Vrfs.php',
    'L2domains.php',
    'Devices.php',
    'Circuits.php',
    'Nat.php',
    'Prefix.php',
    'Tools.php',
    'Search.php',
    'User.php',
];

$sources = [];
foreach (['OpenApiSpec.php', 'AppIdParameter.php'] as $file) {
    $path = __DIR__.'/annotations/'.$file;
    require $path;
    $sources[] = $path;
}
foreach ($controllerFiles as $file) {
    $path = $root.'/public/api/controllers/'.$file;
    require $path;
    $sources[] = $path;
}

$openapi = (new \OpenApi\Generator())->generate($sources);

if ($openapi === null) {
    fwrite(STDERR, "No OpenAPI annotations found, aborting.\n");
    exit(1);
}

$openapi->info->version = VERSION_VISIBLE;

$outputFile = $root.'/public/api/openapi.yaml';
file_put_contents($outputFile, $openapi->toYaml());

echo "Generated $outputFile\n";
