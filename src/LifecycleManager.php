<?php

namespace Axm\Raxm;

use Axm;
use Axm\Raxm\RaxmManager;
use Axm\Raxm\ComponentCheckSum;
use Axm\Raxm\ComponentProperties;


class LifecycleManager extends RaxmManager
{
  protected static $id;
  protected static $initialData;
  protected static $effects = [];
  public static $initialresponse;


  public static function initialFingerprint(): array
  {
    $app = Axm::app();
    $hash = hash('sha256', random_bytes(16));

    return [
      'id'     => $hash,
      'name'   => strtolower(self::$ucfirstComponentName),
      'locale' => 'EN',
      'path'   => ltrim($app->request->getUriString(), '/'),
      'method' => $app->request->getMethod()
    ];
  }


  public static function initialEffects(): array
  {
    return [
      'listeners' => []
    ];
  }


  public static function createDataServerMemo(): array
  {
    $checksum = [
      'checksum' => ComponentCheckSum::generate(
        static::initialFingerprint(),
        static::initialServerMemo()
      )
    ];

    return array_merge(static::initialServerMemo(), $checksum);
  }


  public static function initialServerMemo(): array
  {
    $hash = hash('sha256', random_bytes(16));

    return [
      'children' => [],
      'errors'   => [],
      'htmlHash' => $hash,
      'data'     => static::addDatatoInitialResponse(),
      'dataMeta' => [],
    ];
  }


  public static function addDatatoInitialResponse(): array
  {
    $properties = [];
    $publicProperties = ComponentProperties::getPublicProperties(static::getInstanceNowComponent()) ?? [];
    $properties = array_merge($properties, $publicProperties);

    return $properties;
  }
}
