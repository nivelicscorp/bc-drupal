<?php

use Rector\Core\Configuration\Option;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->import($vendor_dir .  '/palantirnet/drupal-rector/config/drupal-8/drupal-8-all-deprecations.php');
    $containerConfigurator->import($vendor_dir .  '/palantirnet/drupal-rector/config/drupal-9/drupal-9-all-deprecations.php');

    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::AUTOLOAD_PATHS, [
        $drupal_root . '/core',
        $drupal_root . '/modules',
        $drupal_root . '/profiles',
        $drupal_root . '/themes'
    ]);
    $parameters->set(Option::SKIP, ['*/upgrade_status/tests/modules/*']);
    $parameters->set(Option::FILE_EXTENSIONS, ['php', 'module', 'theme', 'install', 'profile', 'inc', 'engine']);
    $parameters->set(Option::AUTO_IMPORT_NAMES, true);
    $parameters->set(Option::IMPORT_SHORT_CLASSES, false);
    $parameters->set(Option::IMPORT_DOC_BLOCKS, false);

    $parameters->set('drupal_rector_notices_as_comments', true);
};
