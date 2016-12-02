<?php

namespace DrupalPsr\Cache;

use Drupal\Core\Cache\Cache;
use Fig\Cache\BasicCacheItemAccessorsTrait;
use Fig\Cache\BasicCacheItemTrait;

/**
 * Class Item
 * @package DrupalPsr\Cache
 */
class Item implements DrupalCacheItemInterface{
  use BasicCacheItemTrait;
  use BasicCacheItemAccessorsTrait;

  /**
   * The tags for the cache item.
   *
   * @var array
   */
  private $tags;

  /**
   * Flag to signal if the item is valid.
   *
   * @var bool
   */
  private $valid;

  /**
   * Constructs a new DrupalCacheItem.
   *
   * @param string $key
   *   The key of the cache item this object represents.
   * @param \stdClass $data
   *   An associative array of data from the Memory Pool.
   */
  public function __construct($key, \stdClass $data) {
    $this->key = $key;
    $this->value = $data->data;
    $expiration = new \DateTime();
    $expiration->setTimestamp($data->expire);
    $this->expiresAt($expiration);
    $this->hit = $data->hit;
    $this->tags = $data->tags;
    $this->valid = $data->valid;
  }

  public function getExpireTimestamp() {
    return $this->expiration->getTimestamp();
  }

  public function addTags(array $tags) {
    $this->tags = Cache::mergeTags($this->tags, $tags);
  }

  public function getTags() {
   return $this->tags;
  }

  public function isValid() {
    return $this->valid;
  }


}