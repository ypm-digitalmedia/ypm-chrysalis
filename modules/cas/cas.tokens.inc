<?php

/**
 * @file
 * Token module integration.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function cas_token_info() {
  return [
    'types' => [
      'cas' => [
        'name' => t('CAS'),
        'description' => t('Tokens related to the CAS module.'),
      ],
    ],
    'tokens' => [
      'cas' => [
        'username' => [
          'name' => t('Username'),
          'description' => t('On most sites this is the same as the Drupal username.'),
        ],
      ],
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function cas_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'cas') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'username':
          if (array_key_exists('cas_username', $data)) {
            $replacements[$original] = $data['cas_username'];
          }
          else {
            // If it's not there, then check the session data instead.
            $session = \Drupal::request()->getSession();
            if ($session->has('cas_username')) {
              $replacements[$original] = $session->get('cas_username');
            }
          }
          break;
      }
    }
  }

  return $replacements;
}
