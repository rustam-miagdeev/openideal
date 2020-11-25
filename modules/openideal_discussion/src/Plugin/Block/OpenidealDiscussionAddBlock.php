<?php

namespace Drupal\openideal_discussion\Plugin\Block;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Drupal\openideal_challenge\OpenidealContextEntityTrait;
use Drupal\openideal_idea\OpenidealHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide add discussion block.
 *
 * @Block(
 *   id = "openidel_discussion_add",
 *   admin_label = @Translation("Add disscusion block"),
 *   category = @Translation("Openideal"),
 *   context = {
 *      "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node"),
 *       required = FALSE,
 *     )
 *   }
 * )
 */
class OpenidealDiscussionAddBlock extends BlockBase implements ContainerFactoryPluginInterface {

  use OpenidealContextEntityTrait;

  /**
   * Openideal helper.
   *
   * @var \Drupal\openideal_idea\OpenidealHelper
   */
  protected $helper;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Constructs a new OpenidealIdeaGoBack object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\openideal_idea\OpenidealHelper $helper
   *   Openideal helper.
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   Current user.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    OpenidealHelper $helper,
    AccountProxy $currentUser
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->helper = $helper;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('openideal_idea.helper'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function build() {
    $build = [];
    if ($node = $this->getEntity($this->getContexts())) {
      $group = $this->helper->getGroupFromIdeaNode($node);
      $member = $this->helper->getGroupMember($this->currentUser, $node);
      $url = Url::fromRoute('entity.group_content.create_form', [
        'group' => $group->id(),
        'plugin_id' => 'group_node:discussion',
      ]);

      // @todo Make count query for better performance.
      $discussions = $group->getContent('group_node:discussion');
      $count = count($discussions);

      $build['content'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['d-flex', 'justify-content-between']],
        'title' => [
          '#type' => 'html_tag',
          '#tag' => 'h3',
          '#value' => $this->t('Export Review (@count)', ['@count' => $count]),
        ],
        'link' => [
          '#type' => 'link',
          '#url' => $url,
          '#attributes' => [
            'data-dialog-type' => 'bootstrap4_modal',
            'data-dialog-options' => Json::encode([
              'title' => $this->t('Expert Review'),
              'dialogClasses' => 'modal-xl',
            ]),
            'class' => ['use-ajax', 'text-uppercase'],
          ],
          '#title' => $this->t('Add Expert Review'),
          '#access' => ($member && $member->hasPermission('create group_node:discussion entity')),
        ],
      ];
      $build['#cache']['tags'] = $node->getCacheTags();
    }
    return $build;

  }

}
