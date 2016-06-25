<?php
/**
 * @file
 * Contains \Drupal\ddd_forms_talk\Form\DddForm.
 */

namespace Drupal\ddd_forms_talk\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * Class DddForm.
 */
class DddForm extends FormBase implements ContainerInjectionInterface {

  /**
   * The Renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * DddForm constructor.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The Renderer service.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
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

  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $submitted_data = [
      'Personal Info' => $form_state->getValue('personal_info'),
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

}
