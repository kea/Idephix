<?php
namespace Idephix;

use Idephix\Exception\InvalidConfigurationException;
use Idephix\SSH\SshClient;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Config implements DictionaryAccess
{
    const ENVS = 'envs';
    const SSHCLIENT = 'ssh_client';
    const EXTENSIONS = 'extensions';

    private $dictionary;

    private function __construct(Dictionary $dictionary)
    {
        $this->dictionary = $dictionary;
    }

    public static function fromArray($data)
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(array(
            self::ENVS => array(),
            self::EXTENSIONS => array(),
            self::SSHCLIENT => new SshClient(),
        ));

        $resolver->setAllowedTypes(self::SSHCLIENT, '\Idephix\SSH\SshClient');
        $resolver->setAllowedTypes(self::ENVS, 'array');
        $resolver->setAllowedTypes(self::EXTENSIONS, 'array');

        try {
            $resolvedConfig = $resolver->resolve($data);
        } catch (UndefinedOptionsException $e) {
            throw new InvalidConfigurationException($e->getMessage());
        } catch (InvalidOptionsException $e) {
            throw new InvalidConfigurationException($e->getMessage());
        }

        $resolvedConfig['envs'] = array_map(
            function ($envData) {
                if (!is_array($envData)) {
                    throw new InvalidConfigurationException("Each env must be an array \"$envData\" given'");
                }

                $envData['hosts'] = empty($envData['hosts']) ? array(null) : $envData['hosts'];

                $sshParamsResolver = new OptionsResolver();
                $sshParamsResolver->setDefaults(
                    array(
                        'user' => '',
                        'password' => '',
                        'public_key_file' => '',
                        'private_key_file' => '',
                        'private_key_file_pwd' => '',
                        'ssh_port' => '22'
                    )
                );
                
                $envData['ssh_params'] = $sshParamsResolver->resolve(
                    empty($envData['ssh_params']) ? array() : $envData['ssh_params']
                );

                return $envData;
            },

            $resolvedConfig['envs']
        );

        return new static(Dictionary::fromArray($resolvedConfig));
    }

    public static function dry()
    {
        return self::fromArray(array());
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

        if (!is_array($config)) {
            throw new InvalidConfigurationException('The config file must return an array');
        }

        return static::fromArray($config);
    }

    public function environments()
    {
        return is_null($this[self::ENVS]) ? array() : $this[self::ENVS];
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
