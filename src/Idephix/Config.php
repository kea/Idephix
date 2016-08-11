<?php
namespace Idephix;

use Idephix\Exception\InvalidConfigurationException;

class Config implements DictionaryAccess
{
    const TARGETS = 'targets';
    const SSHCLIENT = 'sshClient';
    const EXTENSIONS = 'extensions';

    private $dictionary;

    private function __construct(Dictionary $dictionary)
    {
        $this->dictionary = $dictionary;
    }

    public static function fromArray($data)
    {
        return new static(Dictionary::fromArray($data));
    }

    public static function dry()
    {
        return new static(Dictionary::dry());
    }

    public static function parseFile($configFile)
    {
        if (is_null($configFile)) {
            return static::dry();
        }
        
        try {
            new \SplFileObject($configFile);
        } catch (\RuntimeException $e) {
            throw new InvalidConfigurationException('The config file does not exists or is not readable');
        }

        /** @var Config $config */
        $config = require_once $configFile;

        if (!$config instanceof Config) {
            throw new InvalidConfigurationException('The config must be an instance of Idephix\Config');
        }

        return $config;
    }

    public function targets()
    {
        return is_null($this[self::TARGETS]) ? array() : $this[self::TARGETS];
    }

    public function extensions()
    {
        return $this->get(self::EXTENSIONS, array());
    }

    public function offsetExists($offset)
    {
        return $this->dictionary->offsetExists($offset);
    }

    public function offsetGet($offset)
    {
        return $this->dictionary->offsetGet($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->dictionary->offsetSet($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->dictionary->offsetUnset($offset);
    }

    public function get($offset, $default = null)
    {
        return $this->dictionary->get($offset, $default);
    }

    public function set($key, $value)
    {
        $this->dictionary->set($key, $value);
    }
}
