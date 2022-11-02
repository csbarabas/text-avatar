<?php

namespace Drupal\text_avatar\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\FileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Text Avatar settings for this site.
 */
class TextAvatarSettingsForm extends ConfigFormBase {

  /**
   * EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity_type.manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, FileSystem $file_system, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->fileSystem = $file_system;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('file_system'),
      $container->get('entity_type.manager'),
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
        'png' => 'png',
        'jpeg' => 'jpeg',
      ],
      '#default_value' => $this->config('text_avatar.settings')->get('imagetype'),
    ];

    $form['ttf'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Default font'),
      '#description' => $this->t('The default font (*.ttf) to use for create a letter avatar image.'),
      '#upload_location' => 'public://' . $this->config('text_avatar.settings')->get('folder'),
      '#default_value' => [$this->config('text_avatar.settings')->get('ttf')] ?? [],
      '#upload_validators' => [
        'file_validate_extensions' => ['ttf'],
      ],
      '#multiple' => FALSE,
      '#required' => TRUE,
    ];

    $form['action'] = [
      '#type' => 'radios',
      '#title' => $this->t('Generate avatar when'),
      '#options' => [
        'new_user' => $this->t('Create a new user'),
        'edit_user' => $this->t('Edit a user'),
        'both' => $this->t('Both'),
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
    $fid = reset($form_state->getValue('ttf'));
    $fileStorage = $this->entityTypeManager->getStorage('file');
    $file = $fileStorage->load($fid);
    if ($file instanceof FileInterface) {
      $file->setPermanent();
      $file->save();
    }

    $this->config('text_avatar.settings')
      ->set('folder', $form_state->getValue('folder'))
      ->set('imagetype', $form_state->getValue('imagetype'))
      ->set('action', $form_state->getValue('action'))
      ->set('ttf', $fid)
      ->save();
    parent::submitForm($form, $form_state);
  }

}
