<?php

namespace Drupal\smart_date\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\TimestampItem;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\datetime\DateTimeComputed;

/**
 * Plugin implementation of the 'smartdate' field type.
 *
 * @FieldType(
 *   id = "smartdate",
 *   label = @Translation("Smart date range"),
 *   description = @Translation("Create and store timestamp ranges, with an intelligent UI."),
 *   default_widget = "smartdate_default",
 *   default_formatter = "smartdate_default",
 *   list_class = "\Drupal\smart_date\Plugin\Field\FieldType\SmartDateFieldItemList"
 * )
 */
class SmartDateItem extends TimestampItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('timestamp')
      ->setLabel(t('Start timestamp value'))
      ->setRequired(TRUE);

    $properties['start_time'] = DataDefinition::create('any')
      ->setLabel(t('Computed start date'))
      ->setDescription(t('The computed start DateTime object.'))
      ->setComputed(TRUE)
      ->setClass(DateTimeComputed::class)
      ->setSetting('date source', 'value');

    $properties['end_value'] = DataDefinition::create('timestamp')
      ->setLabel(t('End timestamp value'))
      ->setRequired(TRUE);

    $properties['end_time'] = DataDefinition::create('any')
      ->setLabel(t('Computed end date'))
      ->setDescription(t('The computed end DateTime object.'))
      ->setComputed(TRUE)
      ->setClass(DateTimeComputed::class)
      ->setSetting('date source', 'end_value');

    $properties['duration'] = DataDefinition::create('integer')
      ->setLabel(t('Duration, in minutes'))
      ->setRequired(FALSE);
    // TODO: figure out a way to validate as required but accept zero.

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'value' => [
          'description' => 'The start time value.',
          'type' => 'int',
        ],
        'end_value' => [
          'description' => 'The end time value.',
          'type' => 'int',
        ],
        'duration' => [
          'description' => 'The difference between start and end times, in minutes.',
          'type' => 'int',
          'size' => 'medium',
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    // Pick a random timestamp in the past year.
    $timestamp = \Drupal::time()->getRequestTime() - mt_rand(0, 86400 * 365);
    $duration = 60;
    $values['value'] = $timestamp;
    $values['end_value'] = $timestamp + $duration * 60;
    $values['duration'] = $duration;
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $start_value = $this->get('value')->getValue();
    $end_value = $this->get('end_value')->getValue();
    return ($start_value === NULL || $start_value === '') && ($end_value === NULL || $end_value === '');
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($property_name, $notify = TRUE) {
    // Enforce that the computed date is recalculated.
    if ($property_name == 'value') {
      $this->start_time = NULL;
    }
    elseif ($property_name == 'end_value') {
      $this->end_time = NULL;
    }
    parent::onChange($property_name, $notify);
  }

}
