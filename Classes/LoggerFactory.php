<?php

namespace Langeland\Monolog;

use Monolog\Handler\HandlerInterface;
use Neos\Flow\Log\PsrLoggerFactoryInterface;
use Neos\Utility\ObjectAccess;
use Neos\Flow\Annotations as Flow;
use Monolog\Logger;
use Neos\Flow\Configuration\Exception\InvalidConfigurationException;
use Neos\Utility\PositionalArraySorter;

/**
 * Class LoggerFactory
 *
 * @Flow\Proxy(false)
 */
class LoggerFactory implements PsrLoggerFactoryInterface
{
    /**
     * @var array
     */
    protected $instances = [];

    /**
     * @var array
     */
    protected $processorsInstances = [];

    /**
     * @var array
     */
    protected $configuration;

    /**
     * LoggerFactory constructor.
     *
     * @param array $configuration
     *
     * @Flow\Autowiring(false)
     */
    public function __construct(array $configuration = [])
    {
        $this->configuration = $configuration;
    }

    /**
     * @param array $configuration
     */
    public function injectConfiguration(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Home brew singleton because it is used so early.
     *
     * @return LoggerFactory
     * @throws \Exception
     */
    public static function getInstance()
    {
        throw new \Exception('Not implemented', 1559241877);
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * @param array $configuration
     * @return LoggerFactory|static
     */
    public static function create(array $configuration)
    {
        return new self($configuration);
    }

    /**
     * @param string $identifier
     * @return Logger|\Psr\Log\LoggerInterface
     * @throws InvalidConfigurationException
     */
    public function get(string $identifier)
    {
        if (isset($this->loggerInstances[$identifier])) {
            return $this->loggerInstances[$identifier];
        }

        $logger = new Logger($identifier);
        $configuration = $this->configuration[$identifier];
        $handlerSorter = new PositionalArraySorter($configuration);

        foreach ($handlerSorter->toArray() as $index => $handlerConfiguration) {
            $formatter = null;
            $handler = null;
            $processors = [];

            $handler = $this->instantiateObject($handlerConfiguration['handler']);

            if ($handler !== null) {

                /**
                 * Customizing the log format
                 */
                if (array_key_exists('formatter', $handlerConfiguration)) {
                    $formatter = $this->instantiateObject($handlerConfiguration['formatter']);
                    if ($formatter !== null) {
                        $handler->setFormatter($formatter);
                    }
                }

                /**
                 * Adding extra data in the records
                 */
                if (array_key_exists('processors', $handlerConfiguration)) {
                    foreach ($handlerConfiguration['processors'] as $processorConfiguration) {
                        $processors[] = $this->instantiateObject($processorConfiguration);
                    }
                }

                if ($processors !== []) {
                    foreach ($processors as $processor) {
                        $handler->pushProcessor($processor);
                    }
                }

                $logger->pushHandler($handler);
            }
        }

        $this->loggerInstances[$identifier] = $logger;
        return $logger;
    }

    /**
     * @param $configuration
     * @return mixed
     * @throws InvalidConfigurationException
     */
    protected function instantiateObject($configuration)
    {

        if (is_string($configuration)) {
            $className = $configuration;
            $arguments = [];
        } elseif (is_array($configuration)) {
            $className = $configuration['className'];
            $arguments = isset($configuration['arguments']) ? $configuration['arguments'] : null;
        } else {
            throw new InvalidConfigurationException('Invalid config', 1559295599);
        }
        $identifier = $className . md5(json_encode($arguments));

        if (!isset($this->instances[$identifier])) {
            if (!class_exists($className)) {
                throw new InvalidConfigurationException(sprintf('The given formatter class "%s" does not exist, please check configuration for formatter "%s".', $formatterClass, $identifier), 1559242632);
            }
            $this->instances[$identifier] = ObjectAccess::instantiateClass($className, $arguments);
        }

        return $this->instances[$identifier];
    }

}
