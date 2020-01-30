<?php

namespace Drupal\smart_date\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldWidget\DateTimeWidgetBase;
use Drupal\smart_date\Plugin\Field\FieldType\SmartDateListItemBase;

/**
 * Base class for the 'smartdate_*' widgets.
 */
class SmartDateWidgetBase extends DateTimeWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Wrap all of the select elements with a fieldset.
    $element['#theme_wrappers'][] = 'fieldset';

    $element['#element_validate'][] = [$this, 'validateStartEnd'];
    $element['value']['#title'] = $this->t('Start');
    $element['value']['#date_year_range'] = '1902:2037';
    $default_value = isset($items[$delta]->value) ? DrupalDateTime::createFromTimestamp($items[$delta]->value) : '';
    $default_end_value = isset($items[$delta]->end_value) ? DrupalDateTime::createFromTimestamp($items[$delta]->end_value) : '';
    $element['value']['#default_value'] = $default_value;

    $element['end_value'] = [
        '#title' => $this->t('End'),
        '#default_value' => $default_end_value,
      ] + $element['value'];
    $element['value']['#attributes']['class'] = ['time-start'];
    $element['end_value']['#attributes']['class'] = ['time-end'];

    $defaults = $this->fieldDefinition->getDefaultValueLiteral()[0];

    $default_duration = isset($items[$delta]->duration) ? $items[$delta]->duration : $defaults['default_duration'];
    // Parse the allowed duration increments and create labels if not provided.
    $increments = SmartDateListItemBase::parseValues($defaults['default_duration_increments']);
    foreach ($increments as $key => $label) {
      if (strcmp($key, $label) !== 0) {
        // Label provided, so no extra logic required.
        continue;
      }
      if (is_numeric($key)) {
        // Anything but whole minutes will create errors with the time field.
        $num = (int) $key;
        $increments[$key] = t('@count minutes', ['@count' => $num]);
      }
      elseif ($key == 'custom') {
        $increments[$key] = t('Custom');
      }
      else {
        // Note sure what else we would encounter, so escape it.
        $increments[$key] = t('@key (unrecognized format)', ['@key' => $key]);
      }
    }
    if (!array_key_exists($default_duration, $increments)) {
      if (array_key_exists('custom', $increments)) {
        $default_duration = 'custom';
      }
      else {
        // TODO: throw some kind of error/warning if invalid duration?
        $default_duration = '';
      }
    }
    $element['duration'] = [
      '#title' => $this->t('Duration'),
      '#type' => 'select',
      '#options' => $increments,
      '#default_value' => $default_duration,
      '#attributes' => ['data-default' => $defaults['default_duration']],
    ];
    $form['#attached']['library'][] = 'smart_date/smart_date';

    if ($items[$delta]->start_time) {
      /** @var \Drupal\Core\Datetime\DrupalDateTime $start_time */
      $start_time = $items[$delta]->start_time;
      $element['value']['#default_value'] = $this->createDefaultValue($start_time, $element['value']['#date_timezone']);
    }

    if ($items[$delta]->end_time) {
      /** @var \Drupal\Core\Datetime\DrupalDateTime $end_time */
      $end_time = $items[$delta]->end_time;
      $element['end_value']['#default_value'] = $this->createDefaultValue($end_time, $element['end_value']['#date_timezone']);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // The widget form element type has transformed the value to a
    // DrupalDateTime object at this point. We need to convert it back to the
    // storage timestamp.
    foreach ($values as &$item) {
      if (!empty($item['value']) && $item['value'] instanceof DrupalDateTime) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $start_time */
        $start_time = $item['value'];

        // Adjust the date for storage.
        $item['value'] = $start_time->getTimestamp();
      }

      if (!empty($item['end_value']) && $item['end_value'] instanceof DrupalDateTime) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $end_time */
        $end_time = $item['end_value'];

        // Adjust the date for storage.
        $item['end_value'] = $end_time->getTimestamp();
      }
      if ($item['duration'] == 'custom') {
        // If using a custom duration, calculate based on start and end times.
        if(isset($start_time) && isset($end_time) && $start_time instanceof DrupalDateTime && $end_time instanceof DrupalDateTime) {
          $item['duration'] = (int) ($item['end_value'] - $item['value']) / 60;
        }
      }
    }

    return $values;
  }

  /**
   * Ensure that the start date <= the end date via #element_validate callback.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public function validateStartEnd(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $start_time = $element['value']['#value']['object'];
    $end_time = $element['end_value']['#value']['object'];

    if ($start_time instanceof DrupalDateTime && $end_time instanceof DrupalDateTime) {
      if ($start_time->getTimestamp() !== $end_time->getTimestamp()) {
        $interval = $start_time->diff($end_time);
        if ($interval->invert === 1) {
          $form_state->setError($element, $this->t('The @title end date cannot be before the start date', ['@title' => $element['#title']]));
        }
      }
    }
  }

}
