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

// Trusted host patterns
$settings['trusted_host_patterns'] = [
  '^bc\.com\.co$',
  '^www\.bc\.com\.co$',
  '^.+\.elb\.amazonaws\.com$',
];

// Reverse proxy settings (behind ALB/CloudFront)
$settings['reverse_proxy'] = TRUE;
$settings['reverse_proxy_header'] = 'X_FORWARDED_FOR';
$settings['reverse_proxy_addresses'] = [];

// Force HTTPS when behind load balancer
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
  $_SERVER['HTTPS'] = 'on';
  $settings['base_url'] = 'https://www.bc.com.co';
}

// Hide error messages from end users in production
$config['system.logging']['error_level'] = 'hide';
