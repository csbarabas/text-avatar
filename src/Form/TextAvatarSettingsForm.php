<?php

namespace Drupal\text_avatar\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Text Avatar settings for this site.
 */
class TextAvatarSettingsForm extends ConfigFormBase {

  /**
   * FileSystem definition.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * Constructs a new SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\File\FileSystem $file_system
   *   The file_system service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, FileSystem $file_system) {
    parent::__construct($config_factory);
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('file_system'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'text_avatar_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['text_avatar.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $path = 'public://' . $this->config('text_avatar.settings')->get('folder');
    $isWritable = $this->fileSystem->prepareDirectory($path, FileSystemInterface::MODIFY_PERMISSIONS);

    if ($isWritable) {
      $writable = $this->t('The folder name where save the avatar images');
    }
    else {
      $writable = '<strong>' . $this->t('The directory %directory is not writable.', ['%directory' => $path]) . '</strong>';
    }

    $form['folder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Folder'),
      '#description' => $writable,
      '#default_value' => $this->config('text_avatar.settings')->get('folder'),
      '#disabled' => TRUE,
    ];

    $form['imagetype'] = [
      '#type' => 'select',
      '#title' => $this->t('Image type'),
      '#options' => [
        '1' => 'png',
        '2' => 'jpeg',
      ],
      '#default_value' => $this->config('text_avatar.settings')->get('imagetype'),
    ];

    $form['font'] = [
      '#type' => 'select',
      '#title' => $this->t('Font type'),
      '#options' => [
        '1' => 'Sans Serif',
        '2' => 'Serif',
        '3' => 'Display',
        '4' => 'Handwriting',
        '5' => 'Monospace',
      ],
      '#default_value' => $this->config('text_avatar.settings')->get('font'),
    ];

    $form['action'] = [
      '#type' => 'radios',
      '#title' => $this->t('Generate avatar when'),
      '#options' => [
        '0' => $this->t('Create a new user'),
        '1' => $this->t('Edit a user'),
        '2' => $this->t('Both'),
      ],
      '#default_value' => $this->config('text_avatar.settings')->get('action'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    if ($form_state->getValue('folder') === '') {
      $form_state->setErrorByName('folder', $this->t('The folder cannot be empty'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('text_avatar.settings')
      ->set('folder', $form_state->getValue('folder'))
      ->set('imagetype', $form_state->getValue('imagetype'))
      ->set('action', $form_state->getValue('action'))
      ->set('font', $form_state->getValue('font'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
