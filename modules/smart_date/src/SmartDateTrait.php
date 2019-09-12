<?php

namespace Drupal\smart_date;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\smart_date\Entity\SmartDateFormatInterface;

/**
 * Provides friendly methods for smart date range.
 */
trait SmartDateTrait {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    // TODO: intellident switching between retrieval methods
    // Look for a defined format and use it if specified.
    $format_label = $this->getSetting('format');
    if ($format_label) {
      $entity_storage_manager = \Drupal::entityTypeManager()
        ->getStorage('smart_date_format');
      $format = $entity_storage_manager->load($format_label);
      $settings = $format->getOptions();
    }
    else {
      $settings = [
        'separator' => $this->getSetting('separator'),
        'join' => $this->getSetting('join'),
        'time_format' => $this->getSetting('time_format'),
        'time_hour_format' => $this->getSetting('time_hour_format'),
        'date_format' => $this->getSetting('date_format'),
        'date_first' => $this->getSetting('date_first'),
        'ampm_reduce' => $this->getSetting('ampm_reduce'),
        'allday_label' => $this->getSetting('allday_label'),
      ];
    }

    foreach ($items as $delta => $item) {
      if (!empty($item->value) && !empty($item->end_value)) {
        $elements[$delta] = static::formatSmartDate($item->value, $item->end_value, $settings);

        if (!empty($item->_attributes)) {
          $elements[$delta]['#attributes'] += $item->_attributes;
          // Unset field item attributes since they have been included in the
          // formatter output and should not be rendered in the field template.
          unset($item->_attributes);
        }
      }
    }

    return $elements;
  }

  /**
   * Creates a formatted date value as a string.
   *
   * @param object $start_ts
   *   A timestamp.
   * @param object $end_ts
   *   A timestamp.
   * @param array $settings
   *   The formatter settings.
   * @param array $timezone
   *   An optional timezone override.
   *
   * @return string
   *   A formatted date string using the chosen format.
   */
  static public function formatSmartDate($start_ts, $end_ts, $settings, $timezone = NULL, $return_type = '') {
    // Don't need to reduce dates unless conditions are met.
    $date_reduce = FALSE;
    // Apply date format from the display config.
    if ($settings['date_format']) {
      $range['start']['date'] = \Drupal::service('date.formatter')->format($start_ts, '', $settings['date_format'], $timezone);
      $range['end']['date'] = \Drupal::service('date.formatter')->format($end_ts, '', $settings['date_format'], $timezone);

      if ($range['start']['date'] == $range['end']['date']) {
        if ($settings['date_first']) {
          unset($range['end']['date']);
        }
        else {
          unset($range['start']['date']);
        }
      }
      else {
        // If a date range and reduce is set, reduce duplication in the dates.
        $date_reduce = $settings['ampm_reduce'];
        // Don't reduce am/pm if spanning more than one day.
        $settings['ampm_reduce'] = FALSE;
      }
    }
    // If not rendering times, we can stop here.
    if (!$settings['time_format']) {
      return static::rangeFormat($range, $settings, $return_type);
    }
    $temp_start = date('H:i', $start_ts);
    $temp_end = date('H:i', $end_ts);

    // If one of the dates are missing, they must have been the same.
    if (!isset($range['start']['date']) || !isset($range['end']['date'])) {

      // check for zero duration
      if ($temp_start == $temp_end) {
        if ($settings['date_first']) {
          $range['start']['time'] = static::timeFormat($end_ts, $settings, $timezone);
        }
        else {
          $range['end']['time'] = static::timeFormat($end_ts, $settings, $timezone);
        }
        return static::rangeFormat($range, $settings, $return_type);
      }

      // If the conditions that make this necessary aren't met, set to skip.
      if (!$settings['ampm_reduce'] || (date('a', $start_ts) != date('a', $end_ts))) {
        $settings['ampm_reduce'] = FALSE;
      }
    }
    // Check for an all-day range.
    if ($temp_start == '00:00' && $temp_end == '23:59') {
      if ($settings['allday_label']) {
        if (($settings['date_first'] && isset($range['end']['date'])) || empty($range['start']['date'])) {
          $range['end']['time'] = $settings['allday_label'];
        }
        else {
          $range['start']['time'] = $settings['allday_label'];
        }
      }
      if ($date_reduce) {
        // Reduce duplication in date only range
        // First attempt has the following limitations, to reduce complexity:
        // * Day ranges only work either d or j, and no other day tokens
        // * Not able to handle S token unless adjacent to day
        // * Month, day ranges only work if year at start or end
        $start = getdate($start_ts);
        $end = getdate($end_ts);
        // If the years are different, no deduplication necessary.
        if ($start['year'] == $end['year']) {
          $valid_days = [];
          $invalid_days = [];
          // Check for workable day tokens.
          preg_match_all('/[dj]/', $settings['date_format'], $valid_days, PREG_OFFSET_CAPTURE);
          // Check for challenging day tokens.
          preg_match_all('/[DNlwz]/', $settings['date_format'], $invalid_days, PREG_OFFSET_CAPTURE);
          // TODO: add handling for S token
          // If specific conditions are met format as a range within the month.
          if ($start['month'] == $end['month'] && count($valid_days[0]) == 1 && count($invalid_days[0]) == 0) {
            // Split the date string at the valid day token.
            $day_loc = $valid_days[0][0][1];
            // Don't remove the S token from the start if present.
            if ($s_loc = strpos($settings['date_format'], 'S', $day_loc)) {
              $offset = 1 + $s_loc - $day_loc;
            }
            else {
              $offset = 1;
            }
            $start_format = substr($settings['date_format'], 0, $day_loc + $offset);
            $end_format = substr($settings['date_format'], $day_loc);

            $range['start']['date'] = \Drupal::service('date.formatter')
              ->format($start_ts, '', $start_format, $timezone);
            $range['end']['date'] = \Drupal::service('date.formatter')
              ->format($end_ts, '', $end_format, $timezone);
          }
          else {
            if (strpos($settings['date_format'], 'Y') === 0) {
              $year_pos = 0;
            }
            elseif (strpos($settings['date_format'], 'Y') == (strlen($settings['date_format']) - 1)) {
              $year_pos = -1;
            }
            else {
              // Too complicated if year is in the middle.
              $year_pos = FALSE;
            }
            if ($year_pos !== FALSE) {
              $valid_tokens = [];
              // Check for workable day or month tokens.
              preg_match_all('/[djDNlwzSFmMn]/', $settings['date_format'], $valid_tokens, PREG_OFFSET_CAPTURE);
              if ($valid_tokens) {
                if ($year_pos == 0) {
                  // Year is at the beginning, so change the end to start at the
                  // first valid token after it.
                  $first_token = $valid_tokens[0][0];
                  $end_format = substr($settings['date_format'], $first_token[1]);
                  $range['end']['date'] = \Drupal::service('date.formatter')
                    ->format($end_ts, '', $end_format, $timezone);
                }
                else {
                  $last_token = array_pop($valid_tokens[0]);
                  $start_format = substr($settings['date_format'], 0, $last_token[1] + 1);
                  $range['start']['date'] = \Drupal::service('date.formatter')
                    ->format($start_ts, '', $start_format, $timezone);
                }
              }
            }
          }
        }
      }
      return static::rangeFormat($range, $settings, $return_type);
    }

    $range['start']['time'] = static::timeFormat($start_ts, $settings, $timezone, TRUE);
    $range['end']['time'] = static::timeFormat($end_ts, $settings, $timezone);
    return static::rangeFormat($range, $settings, $return_type);
  }

  /**
   * Load a Smart Date Format from a format name.
   *
   * @param string $formatName
   *   The machine name of a Smart Date Format.
   *
   * @return NULL|\Drupal\smart_date\Entity\SmartDateFormatInterface
   *   A Smart Date Format configuration entity, or NULL if one with the given
   *   name could not be found.
   */
  static private function loadSmartDateFormat($formatName) {
    $format = NULL;

    $loadedFormat = \Drupal::getContainer()
      ->get('entity_type.manager')
      ->getStorage('smart_date_format')
      ->load($formatName);

    if ($format instanceof SmartDateFormatInterface) {
      $format = $loadedFormat;
    }

    return $format;
  }

  /**
   * Format a provided range, using provided settings.
   *
   * @param array $range
   *   The date/time range to format.
   * @param array $settings
   *   The date/time range to format.
   * @param string $return_type
   *   An option to specify that a string should be returned. If left empty,
   *   a render array will be returned instead.
   *
   * @return string|array
   *   The formatted range.
   */
  static private function rangeFormat($range, $settings, $return_type = '') {
    // If a string is requested, return that.
    if ($return_type == 'string') {
      $pieces = [];
      foreach ($range as $key => $parts) {
        if ($parts) {
          if (!$settings['date_first']) {
            // Time should be first so reverse the array.
            krsort($parts);
          }
          $pieces[] = implode($settings['join'], $parts);
        }
      }
      return implode($settings['separator'], $pieces);
    }
    // Otherwise, return a render array so it can be altered.
    foreach ($range as $key => &$parts) {
      if ($parts && is_array($parts) && count($parts) > 1) {
        $parts['join'] = $settings['join'];
        if ($settings['date_first']) {
          // Date should be first so sort the array.
          ksort($parts);
        }
        else {
          // Time should be first so reverse the array.
          krsort($parts);
        }
      }
      elseif (!$parts) {
        unset($range[$key]);
      }
    }
    if (count($range) > 1) {
      $range['separator'] = $settings['separator'];
      krsort($range);
    }
    // Otherwise, return a nested array.
    $output = static::array_to_render($range);
    $output['#attributes']['class'] = ['smart_date_range'];
    return $output;
  }

  /**
   * Helper function to turn a simple, nested array into a render array.
   *
   * @param array $array
   *   An array, potentially nested, to be converted.
   *
   * @return array
   *   The nested render array.
   */
  static private function array_to_render($array) {
    if (!is_array($array)) {
      return false;
    }
    $output = [];
    // Iterate though the array
    foreach ($array as $key => $child) {
      $child == array_pop($array);
      if (is_array($child)) {
        $output[$key] = static::array_to_render($child);
      }
      else {
        $output[$key] = [
          '#markup' => $child,
        ];
      }
    }
    return $output;
  }

  /**
   * Helper function to apply time formats
   *
   * @param integer $time
   *   The timestamp to format.
   * @param array $settings
   *   The settings that will be used for formatting.
   * @param string $timezone
   *   An optional timezone override.
   * @param boolean $is_start
   *   If this is the start time in a range, it requires special treatment.
   *
   * @return string
   *   The formatted time.
   */
  static private function timeFormat($time, $settings, $timezone = NULL, $is_start = FALSE) {
    $format = $settings['time_format'];
    if (!empty($settings['time_hour_format']) && date('i', $time) == '00') {
      $format = $settings['time_hour_format'];
    }
    if ($is_start) {
      if ($settings['ampm_reduce']) {
        // Remove am/pm if configured to.
        $format = preg_replace('/\s*(?<![\\\\])a/i', '', $format);
      }
      // Remove the timezone at the start of a time range.
      $format = preg_replace('/\s*(?<![\\\\])[eOPTZ]/i', '', $format);
    }
    return \Drupal::service('date.formatter')->format($time, '', $format, $timezone);
  }

}
