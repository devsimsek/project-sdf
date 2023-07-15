<?php

namespace SDF;

/**
 * SDF controller system
 * @property Loader $load
 */
class Controller extends Core
{

  public object $load;
  /**
   * Fuse, A brand new View Engine
   * @var mixed|object
   */
  private Fuse $fuse;

  public function __construct()
  {
    // To access loaded models,
    // libraries and so.
    $this->load =& self::core_loadClass('Loader');
    $this->fuse =& self::core_loadClass('Fuse');
  }

  /**
   * Returns Application's configuration.
   * @param string|null $key
   * @return false|mixed
   */
  public function get_config(string $key = null): mixed
  {
    return self::core_getConfig('app', $key);
  }

  /**
   * Loads custom configuration
   * @param string $config
   * @param string|null $key
   * @return array
   */
  public function load_config(string $config, string $key = null): array
  {
    return self::core_getConfig($config, $key);
  }

}
