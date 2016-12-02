<?php

namespace DrupalPsr\Cache;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

/**
 * A PSR-6 cache pool that uses the Drupal caching system.
 */
class Pool implements CacheItemPoolInterface {

  /**
   * The cache backend that will be used.
   *
   * @var CacheBackendInterface.
   */
  private $cacheBackend;

  /**
   * The deferred cache items.
   *
   * @var CacheItemInterface[];
   */
  protected $deferredItems;

  /**
   * Constructor for Pool.
   *
   * @param CacheBackendInterface $cacheBackend
   */
  public function __construct(CacheBackendInterface $cacheBackend) {
    $this->cacheBackend = $cacheBackend;
  }

  /**
   * Destructor for Pool.
   */
  function __destruct() {
    $this->commit();
  }

  /**
   * {@inheritdoc}
   */
  public function getItem($key, $allow_invalid = false) {
    // This method will either return True or throw an appropriate exception.
    $this->validateKey($key);

    if ($this->hasItem($key)) {
      try {
        $data = $this->cacheBackend->get($key, $allow_invalid);
        $data->hit = true;
      } catch (\Exception $e) {
        $data = $this->emptyItem();
      }
    }
    else {
      $data = $this->emptyItem();
    }

    return new Item($key, $data);
  }

  /**
   * Returns an empty item definition.
   *
   * @return array
   */
  protected function emptyItem() {
    return (object) [
      'data' => null,
      'hit' => false,
      'expire' => Cache::PERMANENT,
      'tags' => [],
      'valid' => true
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getItems(array $keys = [], $allow_invalid = false) {
    // This method will throw an appropriate exception if any key is not valid.
    array_map([$this, 'validateKey'], $keys);

    $collection = [];
    foreach ($keys as $key) {
      $collection[$key] = $this->getItem($key, $allow_invalid);
    }
    return $collection;
  }

  /**
   * {@inheritdoc}
   */
  public function hasItem($key) {
    // This method will either return True or throw an appropriate exception.
    $this->validateKey($key);

    // Make sure all the deferred items are saved.
    $this->commit();

    try {
      $success = $this->cacheBackend->get($key) !== false;
    } catch (\Exception $e) {
      $success = false;
    }

    return $success;
  }

  /**
   * {@inheritdoc}
   */
  public function clear() {
    try {
      $this->deferredItems = [];
      $this->cacheBackend->deleteAll();
      $success = true;
    } catch (\Exception $e) {
      $success = false;
    }
    return $success;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItem($key) {
    try {
      $this->cacheBackend->delete($key);
      $success = true;
    } catch (\Exception $e) {
      $success = false;
    }
    return $success;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItems(array $keys) {
    try {
      $this->cacheBackend->deleteMultiple($keys);
      $success = true;
    } catch (\Exception $e) {
      $success = false;
    }
    return $success;
  }

  /**
   * {@inheritdoc}
   */
  public function save(CacheItemInterface $item) {
    try {
      $this->cacheBackend->set($item->getKey(), $item->get(), $item->getExpireTimestamp(), $item->getTags());
      $success = true;
    } catch (\Exception $e) {
      $success = false;
    }
    return $success;
  }

  /**
   * {@inheritdoc}
   */
  public function saveDeferred(CacheItemInterface $item) {
    $this->deferredItems []= $item;
    return true;
  }

  /**
   * {@inheritdoc}
   */
  public function commit() {
    $items = [];
    foreach ($this->deferredItems as $deferredItem) {
      $items[$deferredItem->getKey()] = [
        'data' => $deferredItem->get(),
        'expire' => $deferredItem->getExpireTimestamp(),
        'tags' => $deferredItem->getTags()
      ];
    }
    $this->deferredItems = [];

    try {
      $this->cacheBackend->setMultiple($items);
      $success = true;
    } catch (\Exception $e) {
      $success = false;
    }
    return $success;
  }

  /**
   * Determines if the specified key is legal under PSR-6.
   *
   * @param string $key
   *   The key to validate.
   * @throws InvalidArgumentException
   *   An exception implementing The Cache InvalidArgumentException interface
   *   will be thrown if the key does not validate.
   * @return bool
   *   TRUE if the specified key is legal.
   */
  private function validateKey($key) {
    if (preg_match("/[^A-Z,^a-z,^0-9,^_.]/u", $key)) {
      throw new InvalidKeyException(format_string('The key @key contains invalid PSR-6 characters.', ['@key' => $key]), 1);
    }
    return true;
  }

}