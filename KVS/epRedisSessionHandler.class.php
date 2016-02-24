<?php

/**
 * Class to Handle Redis functionality
 *
 * It has following Redis functons
 * . create connection to Redis Server
 * . close connection to Redis Server
 * . set, get and delete key-value from Redis server
 * . check for expire, ttl and exists of Redis Key
 *
 * It has set and get Option funtion too
 * . setOption to modify or add a new key in option array
 * . getOption to get the value from key of option array
 *
 * @author Sanjeev(sanjeev_chaurasia@epark.co.jp)
 */
class epRedisSessionHandler extends BaseEparkRedisClient
{
  /**
   * constructor
   * . the constructor creates a Redis Instance
   *
   * @param array $options
   *
   * @return null
   *
   * @throws RedisException if fails to connect
   */
  public function __construct(array $options)
  {
    include(sfContext::getInstance()->getConfigCache()->checkConfig("config/redis_settings.yml"));
    $options["servers"] = sfConfig::get("redis_settings_servers");
    $redisOption = sfConfig::get("redis_settings_options");

    $options["prefix"] = $redisOption["prefix"] . "_" . $options["prefix"];
    $parameters = $options["servers"];
    $this->setOptions($options);

    $redis_connection = new EparkPhpRedis();
    $redis_command = new EparkPhpRedisCommand();
    parent::__construct($parameters, $options, $redis_connection, $redis_command);
  }

  /**
   * return the option of the specified key.
   *
   * @param string $key
   *
   * @return Ambigous <null, string, integer, array>
   */
  public function getOption($key)
  {
    return isset($this->options[$key]) ? $this->options[$key] : null;
  }

  /**
   * All the options that are set will return.
   *
   * @param void
   * @return array
   */
  public function getOptions()
  {
    return $this->options;
  }

  /**
   * add a key to the options array.
   *
   * @param string $key
   * @param mixed $value
   *
   * @return void
   */
  public function setOption($key, $value)
  {
    $this->options[$key] = $value;
  }

  /**
   * set the options.
   *
   * @param array $options
   *
   * @return void
   */
  public function setOptions(array $options)
  {
    $this->options = $options;
  }

  /**
   * function to get redis value from key
   *
   * @param string $key
   *
   * @return mixed $value or false
   */
  public function getData($key)
  {
    if ($this->exists($key)) {
      $this->expired($key);
      return $this->get($key);
    } else {
      return false;
    }
  }

  /**
   * function to set the key value in Redis database
   *
   * @param string $key
   * @param mixed $value
   *
   * @return boolean
   */
  public function setData($key, $value)
  {
    return $this->set($key, $value, $this->getOption('lifetime'));
  }

  /**
   * check for thr presence of $key in the Redis database
   *
   * @param string $key
   *
   * @return boolean
   */
  public function has($key)
  {
    return $this->exists($key);
  }

  /**
   * Delete $key-$value from Redis database.
   *
   * @param string $key
   *
   * @return integer
   */
  public function remove($key)
  {
    return $this->delete($key);
  }

  /**
   * Set the $key ttl
   *
   * @param string $key
   *
   * @return boolean
   */
  public function expired($key)
  {
    $this->expire($key, $this->getOption('lifetime'));
  }

  /**
   * Disconnects from the Redis instance.
   *
   * @param void
   *
   * @return void
   */
  public function closeConnection()
  {
    $this->close();
  }

  /**
   * Returns the time to live left for a given key in seconds.
   *
   * @param string $key
   *
   * @return long
   */
  public function lifetime($key)
  {
    return $this->ttl($key);
  }

}
