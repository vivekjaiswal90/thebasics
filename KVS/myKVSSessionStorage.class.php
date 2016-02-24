<?php

/**
 * class to store session $key and $value in
 * . Redis database
 * . Memcache database
 *
 * class manages the session
 *
 * @author Sanjeev(sanjeev_chaurasia@epark.co.jp)
 */
class myKVSSessionStorage extends sfSessionStorage
{
  /**
   * epMemcache Object
   */
  protected $memcache;

  /**
   * epRedisSessionHandler object
   */
  protected $redis;

  /**
   * flag to check the existance of session
   */
  protected static $isSessionStarted = false;

  /**
   * the function initializes the session
   *
   * @param array $options
   *
   * @return void
   * @see sfSessionStorage::initialize()
   */
  public function initialize($options = array())
  {
    $agent = new myUserAgent(sfContext::getInstance()->getRequest());
    //checking the existance of key,
    //this is required when calling the myKVSSessionStorage in test case
    if(!array_key_exists("sp_use_cookie", $options)){
      $options["sp_use_cookie"] = true;
    }

    //setting auto start false as to enable session in the parent class
    $options['auto_start'] = false;

    // check for mobile and smartphone
    if ((!$options["sp_use_cookie"] && $agent->isMobile()) || $agent->isKtai()) {
      ini_set('session.use_trans_sid', 1);
      ini_set('session.use_cookies', 0);
    }

    // disable auto_start
    $options['auto_start'] = false;

    // initialize the parent class variables
    parent::initialize($options);

    // use this object as the session handler
    session_set_save_handler(array($this, 'sessionOpen'),
                             array($this, 'sessionClose'),
                             array($this, 'sessionRead'),
                             array($this, 'sessionWrite'),
                             array($this, 'sessionDestroy'),
                             array($this, 'sessionGC'));

    // Start If the session has not yet been started
    if (!self::$isSessionStarted)
      session_start();
    self::$isSessionStarted = true;
  }

  /**
   * function creates the memcache and redis instance.
   *
   * @param void
   *
   * @return boolean
   */
  public function sessionOpen()
  {
    // Redis configuration data load
    $redisOptions = array();
    $redisOptions["lifetime"] = $this->options["session_cookie_lifetime"];     // Lifetime value of GC of data
    $redisOptions["prefix"] = $this->options["session_name"];                  // Prefix of key session name
    // epRedis object
    $this->redis = new epRedisSessionHandler($redisOptions);

    // Memcache configuration data load
    $memcacheOptions = array();
    $memcacheOptions["lifetime"] = ini_get("session.gc_maxlifetime");          // Lifetime value of GC of data
    $memcacheOptions["prefix"] = $this->options["session_name"];               // Prefix of key session name
    // set an instance of the Memcache handler
    $this->memcache = epMemcacheHandler::getInstance($this->options["session_name"], $memcacheOptions);
    return true;
  }

  /**
   * closes the connection made to Redis and Memcache.
   *
   * @param void
   *
   * @return boolean
   */
  public function sessionClose()
  {
    // CLOSE connection
    $this->redis->closeConnection();
    // SET the redis instance as null
    $this->redis = null;

    // CLOSE connection
    $this->memcache->close();
    // SET the memcache instance as null
    $this->memcache = null;

    return true;
  }

  /**
   * returns $value of the $key.
   *
   * @param string $id
   *
   * @return mixed $value
   */
  public function sessionRead($id)
  {
    $data = '';

    // If the session has not been started
    if (empty($this->redis) || empty($this->memcache)) {
      // to start a session
      $this->sessionOpen();
    }
    //If key is present in the Redis
    if ($this->redis->has($id)) {
      //get data from the key
      $data = $this->redis->getData($id);
    } elseif ($this->memcache->has($id)) {
      //get memcache data from key
      $data = $this->memcache->get($id, '');
      $this->redis->setData($id, $data);
    }

      return $data;
  }

  /**
   * fuction to set $key-$value.
   *
   * @param string $id
   * @param string $data
   *
   * @return boolean
   */
  public function sessionWrite($id, $data)
  {
    // If the session has not been started
    if (empty($this->redis) || empty($this->memcache)) {
      // You want to start a session
      $this->sessionOpen();
    }

    $this->memcache->set($id, $data);
    return $this->redis->setData($id, $data);
  }

  /**
   * function to delete key-value.
   *
   * @param string $id
   *
   * @return boolean
   */
  public function sessionDestroy($id)
  {
    // If the session has not been started
    if (empty($this->redis) || empty($this->memcache)) {
      //to start a session
      $this->sessionOpen();
    }
    //to remove the memecache data
    $this->memcache->remove($id);
    //to delete the redis data
    return $this->redis->remove($id);
  }

  /**
   * function to flush key-value of expired session
   *
   * @param integer $lifetime
   *
   * @return void
   */
  public function sessionGC($lifetime)
  {
  }

  /**
   * Regenerates id that represents this storage.
   *
   * @param  boolean $destroy Destroy session when regenerating?
   *
   * @return boolean True if session regenerated, false if error
   *
   */
  public function regenerate($destroy = false)
  {
    if (self::$sessionIdRegenerated)
    {
      return;
    }

    $currentId = session_id();

    parent::regenerate($destroy);

    $newId = session_id();
    $this->sessionRead($newId);

    return $this->sessionWrite($newId, $this->sessionRead($currentId));
  }

  /**
   * Executes the shutdown procedure.
   *
   * @param void
   *
   * @return void
   */
  public function shutdown()
  {
    parent::shutdown();
  }
}
