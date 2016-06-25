<?php

/**
 * @file
 * Contains \Drupal\ddd_forms_talk\Form\DddFormConfig.
 */

namespace Drupal\ddd_forms_talk\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DddFormConfig.
 */
class DddFormConfig extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ddd_forms_talk.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ddd_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#tree'] = TRUE;

    $default_config = $this->config('ddd_forms_talk.settings');

    $form['personal_info']['type'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Set Company as default value'),
      '#default_value' => $default_config->get('personal_info.type'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $profile_default_type = $form_state->getValue('personal_info')['type'];

    $this->config('ddd_forms_talk.settings')
      ->set('personal_info.type', $profile_default_type)
      ->save();
  }

}
