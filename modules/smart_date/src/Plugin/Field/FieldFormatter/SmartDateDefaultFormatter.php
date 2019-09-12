<?php

namespace Drupal\smart_date\Plugin\Field\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldFormatter\DateTimeDefaultFormatter;
use Drupal\smart_date\Entity\SmartDateFormat;
use Drupal\smart_date\SmartDateTrait;

/**
 * Plugin implementation of the 'Default' formatter for 'smartdate' fields.
 *
 * This formatter renders the time range using <time> elements, with
 * configurable date formats (from the list of configured formats) and a
 * separator.
 *
 * @FieldFormatter(
 *   id = "smartdate_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "smartdate"
 *   }
 * )
 */
class SmartDateDefaultFormatter extends DateTimeDefaultFormatter {

  use SmartDateTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'format' => 'default',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  protected function formatDate($date) {
    // Try to load the format from the field settings. If that doesn't work, use
    // the default format, which cannot be deleted.
    $format = static::loadSmartDateFormat($this->getSetting('format'))
      ?: static::loadSmartDateFormat('default');

    // If a timezone override is set, get its machine name.
    $timezone = $this->getSetting('timezone_override')
      ?: $date->getTimezone()->getName();

    // This (formatDate()) function only formats one date, so we pass the same
    // date to both the start and end dates of SmartDateTrait::formatSmartDate()
    // which will only display one date.
    return static::formatSmartDate($date->getTimestamp(), $date->getTimestamp(), $format->getOptions(), $timezone, 'string');
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // Use the upstream settings form, which gives us a control to override the
    // timezone.
    $form = parent::settingsForm($form, $form_state);

    // Remove the upstream format_type control, since we want the user to choose
    // a Smart Date Format instead.
    unset($form['format_type']);

    // Ask the user to choose a Smart Date Format.
    $smartDateFormatOptions = $this->getAvailableSmartDateFormatOptions();
    $form['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Smart Date Format'),
      '#description' => $this->t('Choose which display configuration to use.'),
      '#default_value' => $this->getSetting('format'),
      '#options' => $smartDateFormatOptions,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = $this->getSetting('timezone_override') === ''
      ? t('No timezone override.')
      : t('Timezone overridden to %timezone.', [
        '%timezone' => $this->getSetting('timezone_override'),
      ]);

    $summary[] = t('Smart date format: %format.', [
      '%format' => $this->getSetting('format'),
    ]);

    return $summary;
  }

  /**
   * Get an array of available Smart Date format options.
   *
   * @return string[]
   *   An array of Smart Date Format machine names keyed to Smart Date Format
   *   names, suitable for use in an #options array.
   */
  protected function getAvailableSmartDateFormatOptions() {
    $formatOptions = [];

    $smartDateFormats = \Drupal::entityTypeManager()
      ->getStorage('smart_date_format')
      ->loadMultiple();

    foreach ($smartDateFormats as $type => $format) {
      if ($format instanceof SmartDateFormat) {
        $formatted = static::formatSmartDate(time(), time() + 3600, $format->getOptions(), NULL, 'string');
        $formatOptions[$type] = $format->label() . ' (' . $formatted . ')';
      }
    }

    return $formatOptions;
  }

}
