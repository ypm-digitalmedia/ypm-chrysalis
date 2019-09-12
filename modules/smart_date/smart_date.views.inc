<?php

/**
 * @file
 * Provides views data for the smart_date module.
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_views_data().
 */
function smart_date_field_views_data(FieldStorageConfigInterface $field) {
  $data = views_field_default_views_data($field);
  $field_type = $field->getType();
  $field_name = $field->getName();
  // Override the default handlers.
  $columns = [
    'value' => 'date',
    'end_value' => 'date',
    'duration' => 'numeric',
  ];
  // Provide human-readable property names.
  $labels = [
    'value' => 'Start',
    'end_value' => 'End',
    'duration' => 'Duration',
  ];
  // Provide human-readable property help text.
  $desc = [
    'value' => 'The start of the specified date/time range.',
    'end_value' => 'The end of the specified date/time range.',
    'duration' => 'The duration of the specified date/time range.',
  ];
  // The set of views handlers we want to manipulate.
  $types = [
    'field',
    'filter',
    'sort',
    'argument',
  ];
  foreach ($data as $table_name => $table_data) {
    if (!isset($table_data[$field_name])) {
      continue;
    }
    $base = $table_data[$field_name];
    foreach ($columns as $column => $plugin_id) {
      foreach ($types as $type) {
        if (isset($data[$table_name][$field_name . '_' . $column][$type]) || $type == 'field') {
          // Override the default data with our custom values.
          $data[$table_name][$field_name . '_' . $column][$type]['title'] = $base['title'] . ' - ' . t($labels[$column]);
          $data[$table_name][$field_name . '_' . $column][$type]['id'] = $plugin_id;
          $data[$table_name][$field_name . '_' . $column][$type]['help'] = t($desc[$column]);
          $data[$table_name][$field_name . '_' . $column][$type]['field_name'] = $field_name;
          $data[$table_name][$field_name . '_' . $column][$type]['property'] = $column;
        }
      }
    }
  }

  return $data;
}
