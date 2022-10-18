<?php

namespace Drupal\text_avatar;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\File\FileSystem;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileRepository;

/**
 * Mytask module Services.
 */
class TextAvatarServices {

  /**
   * The file.repository service.
   *
   * @var \Drupal\file\FileRepository
   */
  protected $fileRepository;

  /**
   * Configuration Factory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * FileSystem definition.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * Constructs a FileRepository object.
   *
   * @param \Drupal\file\FileRepository $file_repository
   *   The file.repository service.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config.factory service.
   * @param \Drupal\Core\File\FileSystem $file_system
   *   The file_system service.
   */
  public function __construct(FileRepository $file_repository, ConfigFactory $config_factory, FileSystem $file_system) {
    $this->fileRepository = $file_repository;
    $this->configFactory = $config_factory->get('text_avatar.settings');
    $this->fileSystem = $file_system;
  }

  /**
   * Create a custom avatar image from initials.
   */
  public function newAvatar($text) {

    $text = strtoupper($text);

    $path = 'public://' . $this->configFactory->get('folder');
    $imageType = $this->configFactory->get('imagetype');
    $fontType = $this->configFactory->get('font');
    switch ($fontType) {
      case 1:
        $font = getcwd() . '/modules/custom/text_avatar/fonts/Lato-Regular.ttf';
        break;

      case 2:
        $font = getcwd() . '/modules/custom/text_avatar/fonts/Lora-Regular.ttf';
        break;

      case 3:
        $font = getcwd() . '/modules/custom/text_avatar/fonts/BebasNeue-Regular.ttf';
        break;

      case 4:
        $font = getcwd() . '/modules/custom/text_avatar/fonts/DancingScript-Regular.ttf';
        break;

      case 5:
        $font = getcwd() . '/modules/custom/text_avatar/fonts/RobotoMono-Regular.ttf';
        break;

      default:
        $font = getcwd() . '/modules/custom/text_avatar/fonts/Lato-Regular.ttf';
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
   * If user picture empty, generate an avatar image.
   */
  public function setUserPicture($entity) {

    if (!isset($entity->user_picture->target_id)) {
      $i = substr($entity->name->value, 0, 1);

      $fid = $this->newAvatar($i);

      $entity->set('user_picture', $fid);
      $entity->save();
    }
  }

}