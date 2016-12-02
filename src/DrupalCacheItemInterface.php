<?php

namespace DrupalPsr\Cache;

use Psr\Cache\CacheItemInterface;

/**
 * {@inheritDoc}
 */
interface DrupalCacheItemInterface extends CacheItemInterface {
  /**
   * Get the expire timestamp of the item.
   * 
   * @return mixed
   */
  public function getExpireTimestamp();

  /**
   * Add tags to the already existing item tags.
   * 
   * @param array $tags
   */
  public function addTags(array $tags);

  /**
   * Get all the tags of the item.
   * 
   * @return array
   */
  public function getTags();

  /**
   * Tells you if the item is valid.
   * 
   * Sometimes it is desired to retrieve invalid items (for example expired, or
   * items that have been invalidated), because generating them would be too
   * expensive, or for other reasons.
   * 
   * @return bool
   */
  public function isValid();
}