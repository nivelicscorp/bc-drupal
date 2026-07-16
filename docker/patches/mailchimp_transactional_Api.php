<?php

declare(strict_types=1);

namespace Drupal\mailchimp_transactional;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Service class to integrate with Mailchimp Transactional.
 */
class Api implements ApiInterface {
  use StringTranslationTrait;

  /**
   * The Config Factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The Logger Factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $log;

  /**
   * The http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs the service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory service.
   * @param \GuzzleHttp\Client $http_client
   *   The http client.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory, Client $http_client, MessengerInterface $messenger) {
    $this->config = $config_factory->get('mailchimp_transactional.settings');
    $this->log = $logger_factory->get('mailchimp_transactional');
    $this->httpClient = $http_client;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function isLibraryInstalled(): bool {
    return class_exists('\MailchimpTransactional\ApiClient');
  }

  /**
   * {@inheritdoc}
   */
  public function getMessages($email): array {
    if (!$mailchimp_transactional = $this->getApiObject()) {
      return [];
    }

    $result = $mailchimp_transactional->messages->search(['query' => 'email:' . $email]);
    if ($result instanceof RequestException) {
      $this->messenger->addError($this->t('Mailchimp Transactional: %message', ['%message' => $result->getMessage()]));
      $this->log->error($result->getMessage());
      return [];
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplates(): array {
    if (!$mailchimp_transactional = $this->getApiObject()) {
      return [];
    }

    $result = $mailchimp_transactional->templates->list();
    if ($result instanceof RequestException) {
      $this->messenger->addError($this->t('Mailchimp Transactional: %message', ['%message' => $result->getMessage()]));
      $this->log->error($result->getMessage());
      return [];
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubAccounts(): array {
    if (!$mailchimp_transactional = $this->getApiObject()) {
      return [];
    }

    try {
      $result = $mailchimp_transactional->subaccounts->list();
      if ($result instanceof RequestException) {
        $this->log->notice('Mailchimp Transactional subaccounts not available: %message', ['%message' => $result->getMessage()]);
        return [];
      }
      return $result;
    }
    catch (\Exception $e) {
      // Restricted transactional API keys do not have permission to call
      // subaccounts/list. This is not a critical error — the module works
      // fine without subaccounts.
      $this->log->notice('Mailchimp Transactional subaccounts not available (likely a restricted API key): %message', ['%message' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getUser(): ?object {
    if (!$mailchimp_transactional = $this->getApiObject()) {
      return NULL;
    }

    try {
      $result = $mailchimp_transactional->users->info();
      if ($result instanceof RequestException) {
        $this->log->notice('Mailchimp Transactional: %message', ['%message' => $result->getMessage()]);
        return NULL;
      }
      return $result;
    }
    catch (\Exception $e) {
      $this->log->notice('Mailchimp Transactional users/info not available: %message', ['%message' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTagsAllTimeSeries(): array {
    if (!$mailchimp_transactional = $this->getApiObject()) {
      return [];
    }

    $result = $mailchimp_transactional->tags->allTimeSeries();
    if ($result instanceof RequestException) {
      $this->messenger->addError($this->t('Mailchimp Transactional: %message', ['%message' => $result->getMessage()]));
      $this->log->error($result->getMessage());
      return [];
    }

    return $result;
  }

  /**
   * Validate an API key.
   *
   * Tries users/ping first. If it fails (restricted key), falls back to
   * a messages/send test to confirm the key is valid for sending.
   *
   * @return bool
   *   TRUE if the API key is valid for sending, FALSE otherwise.
   */
  public function isApiKeyValid($api_key = NULL): bool {
    if ($mailchimp_transactional = $this->getNewApiObject($api_key)) {
      try {
        return $mailchimp_transactional->users->ping() === 'PONG!';
      }
      catch (\Exception $e) {
        // Restricted transactional keys cannot call users/ping.
        // Fall back to messages/send to validate the key works for sending.
        try {
          $result = $mailchimp_transactional->messages->send([
            'message' => [
              'from_email' => 'test@example.com',
              'to' => [['email' => 'test@example.com', 'type' => 'to']],
              'subject' => 'API key validation',
              'text' => 'test',
            ],
          ]);
          return is_array($result);
        }
        catch (\Exception $sendException) {
          $this->log->error('Mailchimp Transactional API key validation failed: %message', ['%message' => $sendException->getMessage()]);
          return FALSE;
        }
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function sendTemplate(array $message, $template_id, array $template_content): array {
    $mailer = $this->getApiObject();
    if ($mailer === FALSE) {
      return [
        (object) [
          'status' => 'error',
          'email' => $message['message']['to'][0]['email'] ?? 'recipient',
          'message' => 'Failed to instantiate the Mailchimp Transactional API client. Check the logs for more information.',
        ],
      ];
    }

    $result = $mailer->messages->sendTemplate([
      'message' => $message,
      'template_name' => $template_id,
      'template_content' => $template_content,
    ]);

    if ($result instanceof RequestException) {
      $this->messenger->addError($this->t('Mailchimp Transactional: %message', ['%message' => $result->getMessage()]));
      $this->log->error($result->getMessage());

      return [
        (object) [
          'status' => 'error',
          'email' => $message['message']['to'][0]['email'] ?? 'recipient',
          'message' => $result->getMessage(),
        ],
      ];
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function send(array $message): array {
    $mailer = $this->getApiObject();
    if ($mailer === FALSE) {
      return [
        (object) [
          'status' => 'error',
          'email' => $message['message']['to'][0]['email'] ?? 'recipient',
          'message' => 'Failed to instantiate the Mailchimp Transactional API client. Check the logs for more information.',
        ],
      ];
    }

    $result = $mailer->messages->send($message);

    if ($result instanceof RequestException) {
      $this->messenger->addError($this->t('Could not load Mailchimp Transactional API: %message', ['%message' => $result->getMessage()]));
      $this->log->error($result->getMessage());

      return [
        (object) [
          'status' => 'error',
          'email' => $message['message']['to'][0]['email'] ?? 'recipient',
          'message' => $result->getMessage(),
        ],
      ];
    }

    return $result;
  }

  /**
   * Return Mailchimp Transactional API object.
   *
   * Allows communication with the mailchimp_transactional server.
   *
   * @param bool $reset
   *   Pass in TRUE to reset the statically cached object.
   * @param string $api_key
   *   API key to authorize Mailchimp Transactional API.
   *
   * @return \MailchimpTransactional\ApiClient|bool
   *   Mailchimp Transactional Object upon success
   *   FALSE if 'mailchimp_transactional_api_key' is unset
   */
  private function getApiObject($reset = FALSE, $api_key = NULL) {
    $api =& drupal_static(__FUNCTION__, NULL);
    if ($api === NULL || $reset || $api_key) {
      $api = $this->getNewApiObject($api_key);
    }
    return $api;
  }

  /**
   * Return a new Mailchimp Transactional API object without looking in cache.
   *
   * @param string $api_key
   *   API key to authorize Mailchimp Transactional API.
   */
  private function getNewApiObject($api_key) {
    if (!$this->isLibraryInstalled()) {
      $msg = $this->t('Failed to load Mailchimp Transactional PHP library. Please refer to the installation requirements.');
      $this->log->error($msg);
      $this->messenger->addError($msg);
      return NULL;
    }

    $api_key ?? $api_key = $this->config->get('api_key');
    $api_timeout = $this->config->get('api_timeout');
    if (empty($api_key)) {
      $msg = $this->t('Failed to load Mailchimp Transactional API Key. Please check your Mailchimp Transactional settings.');
      $this->log->error($msg);
      $this->messenger->addError($msg);
      return FALSE;
    }
    // We allow the class name to be overridden, following the example of core's
    // mailsystem, in order to use alternate Mailchimp Transactional classes.
    $class_name = $this->config->get('api_classname');
    return new $class_name($this->httpClient, $api_key, $api_timeout);
  }

}
