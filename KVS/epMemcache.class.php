<?php
class epMemcache extends epDataCache
{
  /**
   * Memcache クラスのインスタンス
   * @var Memcache
   */
  protected $memcache;

  /**
   * コンストラクタ
   *
   * @throws EparkException
   */
  public function __construct(array $options)
  {
    $this->memcache = new Memcache();

    if (is_array($options["servers"]) && count($options["servers"]) > 0) {
      foreach ($options["servers"] as $server) {
        $host = isset($server["host"]) ? $server["host"] : "localhost";
        $port = isset($server["port"]) ? $server["port"] : 11211;
        if (!$this->memcache->addServer($host, $port, isset($server["persistent"]) ? $server["persistent"] : true)) {
          throw new EparkException(sprintf('memcache サーバへの接続に失敗しました。 (%s:%s).', $host, $port));
        }
      }

      // オプションを設定しておく
      $this->setOptions($options);
    }
    else {
      throw new EparkException("memcache_servers.yml の設定が間違っています。");
    }
  }

  /**
   * memcache オブジェクトを返却します。
   *
   * @return Memcache
   */
  public function getObject()
  {
    return $this->memcache;
  }

  /**
   * memcache から指定されたキーの値を取得し、返却します。
   *
   * @param string $key
   * @param mixed  $default
   * @return Ambigous <string, unknown>
   */
  public function get($key, $default = null)
  {
    $value = $this->memcache->get($this->getOption('prefix').$key);

    return false === $value ? $default : $value;
  }

  /**
   * memcache に指定されたキーの値を設定します。
   *
   * @param string  $key
   * @param mixed   $data
   * @param integer $lifetime
   * @return boolean
   */
  public function set($key, $data, $lifetime = null)
  {
    $lifetime = null === $lifetime ? $this->getOption('lifetime') : $lifetime;

    if (false !== $this->memcache->replace($this->getOption('prefix').$key, $data, false, time() + $lifetime))
    {
      return true;
    }

    return $this->memcache->set($this->getOption('prefix').$key, $data, false, time() + $lifetime);
  }

  /**
   * memcache に指定されたキーの値が設定されているか。
   *
   * @param string $key
   * @return boolean
   */
  public function has($key)
  {
    return !(false === $this->memcache->get($this->getOption('prefix').$key));
  }

  /**
   * memcahce から指定されたキーの値を削除します。
   *
   * @param string $key
   * @return void
   */
  public function remove($key)
  {
    return $this->memcache->delete($this->getOption('prefix').$key);
  }

  /**
   * memcache から全ての値を削除します。
   *
   * @return boolean
   */
  public function clean()
  {
    return $this->memcache->flush();
  }

  /**
   * memcache から複数の値を取得します。
   *
   * @param string $keys
   * @return multitype:unknown
   */
  public function getMany(array $keys)
  {
    $values = array();
    foreach ($this->memcache->get(array_map(create_function('$k', 'return "'.$this->getOption('prefix').'".$k;'), $keys)) as $key => $value)
    {
      $values[str_replace($this->getOption('prefix'), '', $key)] = $value;
    }

    return $values;
  }

  /**
   * memcache との接続を閉じます
   *
   * @return boolean
   */
  public function close()
  {
    return $this->memcache->close();
  }
}