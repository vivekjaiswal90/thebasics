<?php

class epMemcacheHandler
{
  /**
   * クラスのインスタンス
   * @var epDataCache
   */
  protected static $instances;

  /**
   * 指定された識別子の epMemcacheHandler インスタンスを返却します。
   *
   * @param string $name
   * @return epDataCache
   */
  public static function getInstance($name, array $options = array())
  {
    if (empty(self::$instances[$name])) {
      // インスタンスが存在しない場合は新しく作成する
      self::$instances[$name] = self::createInstance($options);
    }

    return self::$instances[$name];
  }

  /**
   * キャッシュ管理クラスのインスタンスを生成して返します。
   *
   * @param array $options
   * @return epDataCache
   * @throws EparkException
   */
  public static function createInstance(array $options = array())
  {
    // Memcacheサーバ設定のロード
    include(sfContext::getInstance()->getConfigCache()->checkConfig("config/memcache_settings.yml"));
    // オプションにMemcacheサーバ設定を追加
    $options["servers"] = sfConfig::get("memcache_settings_servers");
    $class = sfConfig::get("memcache_settings_class");
    // Prefixに環境名を追加（namespace代わり）
    $options["prefix"] = sfConfig::get("memcache_settings_namespace")."_".$options["prefix"];

    // クラスの存在チェック
    if (!class_exists($class)) {
      throw new EparkException(sprintf("指定されたクラスは存在しません。(class: %s)", $class));
    }

    // クラスが存在する場合はepark用のラップクラス名に変換する
    $class = "ep".$class;

    // 指定クラスのインスタンスを作成
    $cacheClass = new $class($options);

    // インスタンス化されたクラスがepDataCacheの子クラスかチェック
    if (!$cacheClass instanceof epDataCache) {
      throw new EparkException(sprintf("指定されたクラスはキャッシュ管理クラスではありません。(class: %s)", $class));
    }

    return $cacheClass;
  }
}
