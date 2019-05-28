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
    protected $loggerInstances = [];

    /**
     * @var array
     */
    protected $handlerInstances = [];

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

        $configuration = $this->configuration[$identifier];

        $logger = new Logger($identifier);

        $handlerSorter = new PositionalArraySorter($configuration['handler']);
        foreach ($handlerSorter->toArray() as $index => $handlerConfiguration) {
            $handler = null;
            if (is_string($handlerConfiguration)) {
                $handler = $this->getConfiguredHandler($handlerConfiguration);
            }

            if (is_array($handlerConfiguration)) {
                $handlerIdentifier = $identifier . md5(json_encode($handlerConfiguration));
                $handler = $this->instanciateHandler($handlerIdentifier, $handlerConfiguration);
            }

            if ($handler !== null) {
                $logger->pushHandler($handler);
            }
        }

        $this->loggerInstances[$identifier] = $logger;
        return $logger;
    }

    /**
     * @param array $configuration
     */
    public function injectConfiguration(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Creates a monolog instance.
     *
     * @param string $identifier An identifier for the logger
     * @param array $configuration
     * @return Logger
     */
    public function createFromConfiguation($identifier, array $configuration)
    {
        if (isset($this->loggerInstances[$identifier])) {
            return $this->loggerInstances[$identifier];
        }

        $logger = new Logger($identifier);

        $handlerSorter = new PositionalArraySorter($configuration['handler']);
        foreach ($handlerSorter->toArray() as $index => $handlerConfiguration) {
            if (is_string($handlerConfiguration)) {
                $handler = $this->getConfiguredHandler($handlerConfiguration);
            }

            if (is_array($handlerConfiguration)) {
                $handlerIdentifier = $identifier . md5(json_encode($handlerConfiguration));
                $handler = $this->instanciateHandler($handlerIdentifier, $handlerConfiguration);
            }

            if ($handler !== null) {
                $logger->pushHandler($handler);
            }
        }

        $this->loggerInstances[$identifier] = $logger;

        return $logger;
    }

    /**
     * @param $identifier
     * @return HandlerInterface
     * @throws InvalidConfigurationException
     * @api
     */
    public function getConfiguredHandler($identifier)
    {
        if (!isset($this->configuration['handler'][$identifier])) {
            throw new InvalidConfigurationException(sprintf('The required handler configuration for the given identifier "%s" was not found. Please configure a logger with this identifier in "Langeland.Monolog.handler"', $identifier), 1436767040);
        }

        return $this->instanciateHandler($identifier, $this->configuration['handler'][$identifier]);
    }

    /**
     * Home brew singleton because it is used so early.
     *
     * @return LoggerFactory
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * @param string $identifier
     * @param array $handlerConfiguration
     * @return HandlerInterface
     * @throws InvalidConfigurationException
     */
    protected function instanciateHandler($identifier, $handlerConfiguration)
    {
        if (!isset($this->handlerInstances[$identifier])) {
            $handlerClass = isset($handlerConfiguration['className']) ? $handlerConfiguration['className'] : null;

            if (!class_exists($handlerClass)) {
                throw new InvalidConfigurationException(sprintf('The given handler class "%s" does not exist, please check configuration for handler "%s".', $handlerClass, $identifier), 1436767219);
            }

            $arguments = (isset($handlerConfiguration['arguments']) && is_array($handlerConfiguration['arguments'])) ? $handlerConfiguration['arguments'] : [];
            $this->handlerInstances[$identifier] = ObjectAccess::instantiateClass($handlerClass, $arguments);
        }

        return $this->handlerInstances[$identifier];
    }
}
