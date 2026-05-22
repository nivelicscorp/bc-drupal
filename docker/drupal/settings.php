<?php

// phpcs:ignoreFile

$databases['default']['default'] = [
  'database' => getenv('DB_NAME'),
  'username' => getenv('DB_USER'),
  'password' => getenv('DB_PASSWORD'),
  'host' => getenv('DB_HOST'),
  'port' => '3306',
  'driver' => 'mysql',
  'prefix' => '',
  'collation' => 'utf8mb4_general_ci',
];

$settings['hash_salt'] = 'nB5tu2TTkVUVR3p6QYJSKgLFHQL26K2bHz3-TQq3wicioOwkJs-BZYy4WrVokHtH7ffQwaeLwr';

// $settings['config_sync_directory'] = '../config/sync';
$settings['config_sync_directory'] = '/opt/drupal/web/sites/default/config/sync';

// $config['elasticsearch_connector.cluster.elasticsearch']['url'] = getenv('ELASTICSEARCH_URL');

$settings['file_private_path'] = '../private/files';