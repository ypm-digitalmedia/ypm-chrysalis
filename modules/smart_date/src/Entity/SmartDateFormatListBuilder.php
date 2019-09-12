<?php

namespace Drupal\smart_date\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Smart date formats.
 *
 * @ingroup smart_date
 */
class SmartDateFormatListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');;
    $header['date_format'] = $this->t('Date Format');
    $header['time_format'] = $this->t('Time Format');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\smart_date\Entity\SmartDateFormat */
    $row['query'] = Link::createFromRoute(
      $entity->label(),
      'entity.smart_date_format.edit_form',
      ['smart_date_format' => $entity->id()]
    );
    // List the nodes that are elevated and excluded.
    $row['date_format']['data'] = $entity->get('date_format');
    $row['time_format']['data'] = $entity->get('time_format');
    return $row + parent::buildRow($entity);
  }

  /**
   * Turn a EntityReferenceFieldItemList into a render array of links.
   */
  protected function makeLinksFromRef($ref) {
    // No value means nothing to do.
    if (!$ref) {
      return NULL;
    }
    $entities = $ref->referencedEntities();
    $content = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#wrapper_attributes' => ['class' => 'container'],
    ];
    $links = [];
    foreach ($entities as $ref_entity) {
      $links[] = Link::fromTextAndUrl(
        $ref_entity->getTitle(),
        $ref_entity->toUrl()
      );
    }
    $content['#items'] = $links;
    return $content;
  }

}
