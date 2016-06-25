<?php
/**
 * @file
 * Contains \Drupal\ddd_forms_talk\Form\DddForm.
 */

namespace Drupal\ddd_forms_talk\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use \Drupal\Core\Ajax\AjaxResponse;
use \Drupal\Core\Ajax\HtmlCommand;

/**
 * Class DddForm.
 */
class DddForm extends FormBase {

  /**
   * The Renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * The Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * The Subscription Options.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $subscriptionsOptions;

  /**
   * DddForm constructor.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The Renderer service.
   * @param EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(RendererInterface $renderer, EntityTypeManagerInterface $entity_manager) {
    $this->renderer = $renderer;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ddd_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'ddd_forms_talk/default';

    $form['intro'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Fill the Form to subscribe our amazing Magazines'),
    ];

    $form['personal_info'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Personal Information'),
    );

    $form['personal_info']['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#description' => $this->t('Input your name'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $this->config('ddd_forms_talk.settings')->get('personal_info.name'),
    );

    $form['personal_info']['surname'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Surname'),
      '#placeholder' => $this->t('Input you surname'),
      '#description' => $this->t("This cannot be 'X'"),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $this->config('ddd_forms_talk.settings')->get('personal_info.surname'),
      '#required' => TRUE,
    );

    $form['personal_info']['type'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Are you representing a Company?'),
      '#default_value' => $this->config('ddd_forms_talk.settings')->get('personal_info.type'),
    );

    $form['personal_info']['company_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Company'),
      '#description' => $this->t('Input your Company name'),
      '#maxlength' => 255,
      '#size' => 64,
      '#states' => [
        'invisible' => [
          ':input[name="personal_info[type]"]' => ['checked' => FALSE],
        ],
      ],
    );

    $email_description = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('This is an HTML5 email field, and will be properly validated (out of the box)'),
      '#attributes' => [
        'class' => [
          'form-text-red',
        ],
      ],
    ];

    $form['personal_info']['email'] = array(
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#description' => $email_description,
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $this->config('ddd_forms_talk.settings')->get('personal_info.email'),
    );

    $this->subscriptionsOptions = [
      '0' => 'Newsletter',
    ];

    $magazines = $this->entityManager->getStorage('node')->loadMultiple();

    foreach ($magazines as $id => $magazine) {
      $this->subscriptionsOptions[$id] = $magazine->getTitle();
    }

    $form['subscription'] = [
      '#type' => 'select',
      '#title' => $this->t('Annual Subscription'),
      '#description' => $this->t('Choose the Newsletter or the Magazine you want to subscribe for 1 year'),
      '#options' => $this->subscriptionsOptions,
      '#default_value' => 'Newsletter',
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'subscriptionSelectAjax'],
        'event' => 'change',
        'effect' => 'fade',
        'speed' => 'default',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Fetching data ...'),
        ],
      ],
    ];

    $form['magazine_view'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => NULL,
      '#attributes' => [
        'id' => 'magazine-view',
      ],
    ];

    // Submit button.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send'),
      '#states' => [
        'invisible' => [
          ':input[name="personal_info[email]"]' => ['value' => ''],
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Validation of the Surname (cannot be 'X').
    if (strtolower($form_state->getValue('personal_info')['surname']) == 'x') {
      $form_state->setErrorByName(
        'personal_info][surname',
        $this->t("Alt! We don't subscribe to Mister X ... !!!")
      );
    }

    // Validation of the Company Name.
    if ($form_state->getValue('personal_info')['type'] == TRUE
    && empty($form_state->getValue('personal_info')['company_name'])) {
      $form_state->setErrorByName(
        'personal_info][company_name',
        $this->t("Please, don't forget to send us your Company name, as well")
      );
    }

    // !Important
    // Set the form "magazine_view" corresponding with the subscription input.
    $selected_subscription = $form_state->getValue('subscription');
    if ('0' != $selected_subscription) {
      $magazine_view = $this->generateSubscriptionFormElementView('node', $selected_subscription, 'subscription_form');
      // !Important: keep the id selector, for subsequent ajax calls.
      $magazine_view['#attributes'] = [
        'id' => 'magazine-view',
      ];
      $form['magazine_view'] = $magazine_view;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $submitted_data = [
      'Personal Info' => $form_state->getValue('personal_info'),
      'Annual Subscription' => $this->subscriptionsOptions[$form_state->getValue('subscription')],
    ];
    $submitted_data_render_array = [
      '#theme' => 'ddd_forms_talk_form_submission',
      '#submitted_data' => $submitted_data,
      '#attached' => [
        'library' => ['ddd_forms_talk/default'],
      ],
    ];

    drupal_set_message($this->t('The Form was correctly sent with the following values: @values',
      [
        '@values' => $this->renderer->render($submitted_data_render_array),
      ]));
  }

  /**
   * Ajax callback for the Subscription Select.
   */
  public function subscriptionSelectAjax(array &$form, FormStateInterface $form_state) {

    // Fetch the selected subscription node id.
    $selected_subscription = $form_state->getValue('subscription');
    $response = new AjaxResponse();

    if ('0' != $selected_subscription) {
      // If the selected option is not '0' => 'Newsletter',
      // fetch the entity view.
      $response_body_array = $this->generateSubscriptionFormElementView('node', $selected_subscription, 'subscription_form');
      // !Important: keep the id selector, for subsequent ajax calls.
      $response_body_array['#attributes'] = [
        'id' => 'magazine-view',
      ];
      $response->addCommand(new HtmlCommand('#magazine-view', $response_body_array));
    }
    else {
      // Else, return an empty render array, keeping the id selector,
      // for subsequent ajax calls.
      $response_body_array = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => NULL,
        '#attributes' => [
          'id' => 'magazine-view',
        ],
      ];
      $response->addCommand(new HtmlCommand('#magazine-view', $response_body_array));
    }
    return $response;

  }

  /**
   * Generate the Entity View Render Array.
   *
   * @param string $entity_type
   *   The entity type.
   * @param int $id
   *   The entity id.
   * @param string $view_mode
   *   The view mode.
   *
   * @return array
   *   The response render array
   */
  protected function generateSubscriptionFormElementView($entity_type, $id, $view_mode) {
    $entity = $this->entityManager->getStorage($entity_type)
      ->load($id);
    $view_builder = $this->entityManager->getViewBuilder('node');
    return $view_builder->view($entity, $view_mode);
  }

}
