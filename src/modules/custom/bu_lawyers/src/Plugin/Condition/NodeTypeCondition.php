<?php

namespace Drupal\bu_lawyers\Plugin\Condition;

use Drupal\rules\Core\RulesConditionBase;
use Drupal\Core\Plugin\Context\ContextInterface;
use Drupal\node\Entity\NodeType;

/**
 * @Condition(
 *   id = "node_type",
 *   label = @Translation("Node Type"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node", required = TRUE)
 *   }
 * )
 */
class NodeTypeCondition extends RulesConditionBase {

  /**
   * {@inheritdoc}
   */
  public function evaluate(): bool {
    $node = $this->getContextValue('node');
    return $node && $node->bundle() == $this->configuration['node_type'];
  }

  /**
   * {@inheritdoc}
   */
  public function summarize(): string {
    return $this->t('Node type is @type', ['@type' => $this->configuration['node_type']]);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineConfiguration(): array {
    return [
      'node_type' => [
        'type' => 'string',
        'label' => $this->t('Node type'),
      ],
    ];
  }
}