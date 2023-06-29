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

    return [
      'id'     => randomHash(20, false),
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
    return [
      'children' => [],
      'errors'   => [],
      'htmlHash' => randomHash(8, false),
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
