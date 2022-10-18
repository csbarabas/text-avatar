<?php

namespace Drupal\text_avatar;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\File\FileSystem;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileRepository;
use Drupal\user\UserInterface;

/**
 * Text avatar module Services.
 */
class TextAvatarServices {

  /**
   * The file.repository service.
   *
   * @var \Drupal\file\FileRepository
   */
  protected $fileRepository;

  /**
   * The text_avatar.settings config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * FileSystem definition.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * ExtensionPAthResolver definition.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected $extensionPathResolver;

  /**
   * Construct a TextAvatarService.
   *
   * @param \Drupal\file\FileRepository $file_repository
   *   The file.repository service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config.factory service.
   * @param \Drupal\Core\File\FileSystem $file_system
   *   The file_system service.
   * @param \Drupal\Core\Extension\ExtensionPathResolver $extension_path_resolver
   *   The extension.path.resolver service.
   */
  public function __construct(FileRepository $file_repository, ConfigFactoryInterface $config_factory, FileSystem $file_system, ExtensionPathResolver $extension_path_resolver) {
    $this->fileRepository = $file_repository;
    $this->config = $config_factory->get('text_avatar.settings');
    $this->fileSystem = $file_system;
    $this->extensionPathResolver = $extension_path_resolver;
  }

  /**
   * Create a custom avatar image from initials.
   *
   * @param string $text
   *   The initials to use.
   *
   * @return string|int|null
   *   Return the new picture file id.
   */
  public function newAvatar(string $text) {
    $currentDirectory = $this->extensionPathResolver->getPath('module', 'text_avatar');

    $text = strtoupper($text);

    $path = 'public://' . $this->config->get('folder');
    $imageType = $this->config->get('imagetype');
    $fontType = $this->config->get('font');
    switch ($fontType) {
      case 1:
        $font = $currentDirectory . '/fonts/Lato-Regular.ttf';
        break;

      case 2:
        $font = $currentDirectory . '/fonts/Lora-Regular.ttf';
        break;

      case 3:
        $font = $currentDirectory . '/fonts/BebasNeue-Regular.ttf';
        break;

      case 4:
        $font = $currentDirectory . '/fonts/DancingScript-Regular.ttf';
        break;

      case 5:
        $font = $currentDirectory . '/fonts/RobotoMono-Regular.ttf';
        break;

      default:
        $font = $currentDirectory . '/fonts/Lato-Regular.ttf';
    }

    $red = rand(0, 255);
    $green = rand(0, 255);
    $blue = rand(0, 255);

    $im = imagecreate(310, 310);

    imagecolorallocate($im, $red, $green, $blue);
    $text_color = imagecolorallocate($im, 255, 255, 255);

    $size = 100;
    $angle = 0;
    $xi = imagesx($im);
    $yi = imagesy($im);

    $box = imagettfbbox($size, $angle, $font, $text);

    $xr = abs(max($box[2], $box[4]));
    $yr = abs(max($box[5], $box[7]));

    $x = intval(($xi - $xr) / 2);
    $y = intval(($yi + $yr) / 2);

    imagettftext($im, $size, $angle, $x, $y, $text_color, $font, $text);

    ob_start();
    if ($imageType == '1') {
      $filetype = 'png';
      imagepng($im);
    }
    else {
      $filetype = 'jpeg';
      imagejpeg($im);
    }
    $im_string = ob_get_contents();
    ob_end_clean();

    $isWritable = $this->fileSystem->prepareDirectory($path, FileSystemInterface::MODIFY_PERMISSIONS);
    if ($isWritable) {
      $filesaved = $this->fileRepository->writeData($im_string, $path . '/avatar_' . $text . '.' . $filetype, 0);
    }
    else {
      $filesaved = $this->fileRepository->writeData($im_string, 'public://avatar_' . $text . '.' . $filetype, 0);
    }

    $fid = $filesaved->id();

    imagedestroy($im);

    return $fid;
  }

  /**
   * If user picture empty, generate an avatar image and save to user_picture.
   *
   * @param \Drupal\user\UserInterface $entity
   *   The user object.
   */
  public function setUserPicture(UserInterface $entity): void {

    if (!isset($entity->user_picture->target_id)) {
      $i = substr($entity->name->value, 0, 1);

      $fid = $this->newAvatar($i);

      $entity->set('user_picture', $fid);
      $entity->save();
    }
  }

}
