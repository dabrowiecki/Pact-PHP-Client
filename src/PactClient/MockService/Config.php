<?php

namespace Madkom\PactClient\MockService;

use Madkom\PactClient\PactException;

/**
 * Configuration defining the default PhpPact Ruby Standalone server.
 * Class Config
 */
class Config
{
    const DEFAULT_SPECIFICATION_VERSION = '2.0.0';

    /**
     * Host on which to bind the service.
     *
     * @var string
     */
    private $host = 'localhost';

    /**
     * Port on which to run the service.
     *
     * @var int
     */
    private $port = 7200;

    /**
     * @var bool
     */
    private $secure = false;

    /**
     * Consumer name.
     *
     * @var string
     */
    private $consumer;

    /**
     * Provider name.
     *
     * @var string
     */
    private $provider;

    /**
     * Directory to which the pacts will be written.
     *
     * @var string
     */
    private $pactDir;

    /**
     * `overwrite` or `merge`. Use `merge` when running multiple mock service
     * instances in parallel for the same consumer/provider pair. Ensure the
     * pact file is deleted before running tests when using this option so that
     * interactions deleted from the code are not maintained in the file.
     *
     * @var string
     */
    private $pactFileWriteMode = 'overwrite';

    /**
     * The pact specification version to use when writing the pact. Note that only versions 1 and 2 are currently supported.
     *
     * @var float
     */
    private $pactSpecificationVersion;

    /**
     * File to which to log output.
     *
     * @var string
     */
    private $log;

    /** @var bool */
    private $cors = false;

    /**
     * The max allowed time the mock server has to be available in. Otherwise it is considered as sick.
     *
     * @var int
     */
    private $healthCheckTimeout;

    public function __construct()
    {
        $this
            ->setHost($this->parseEnv('PACT_MOCK_SERVER_HOST'))
            ->setPort($this->parseEnv('PACT_MOCK_SERVER_PORT'))
            ->setConsumer($this->parseEnv('PACT_CONSUMER_NAME'))
            ->setProvider($this->parseEnv('PACT_PROVIDER_NAME'))
            ->setPactDir($this->parseEnv('PACT_OUTPUT_DIR', false))
            ->setCors($this->parseEnv('PACT_CORS', false));

        if ($logDir = $this->parseEnv('PACT_LOG', false)) {
            $this->setLog($logDir);
        }

        $timeout = $this->parseEnv('PACT_MOCK_SERVER_HEALTH_CHECK_TIMEOUT', false);
        if (!$timeout) {
            $timeout = 10;
        }
        $this->setHealthCheckTimeout($timeout);

        $version = $this->parseEnv('PACT_SPECIFICATION_VERSION', false);
        if (!$version) {
            $version = static::DEFAULT_SPECIFICATION_VERSION;
        }

        $this->setPactSpecificationVersion($version);
    }

    private function parseEnv($variableName, $required = true)
    {
        $result = null;

        if (\getenv($variableName) === 'false') {
            $result = false;
        } elseif (\getenv($variableName) === 'true') {
            $result = true;
        }
        if (\getenv($variableName) !== false) {
            $result = \getenv($variableName);
        }

        if ($required === true && $result === null) {
            throw new PactException("$variableName is undefined");
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * {@inheritdoc}
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * {@inheritdoc}
     */
    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isSecure()
    {
        return $this->secure;
    }

    /**
     * {@inheritdoc}
     */
    public function setSecure($secure)
    {
        $this->secure = $secure;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getBaseUri()
    {
        $protocol = $this->secure ? 'https' : 'http';

        return "{$protocol}://{$this->getHost()}:{$this->getPort()}";
    }

    /**
     * {@inheritdoc}
     */
    public function getConsumer()
    {
        return $this->consumer;
    }

    /**
     * {@inheritdoc}
     */
    public function setConsumer($consumer)
    {
        $this->consumer = $consumer;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * {@inheritdoc}
     */
    public function setProvider($provider)
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPactDir()
    {
        if ($this->pactDir === null) {
            return \sys_get_temp_dir();
        }

        return $this->pactDir;
    }

    /**
     * {@inheritdoc}
     */
    public function setPactDir($pactDir)
    {
        if ('\\' !== \DIRECTORY_SEPARATOR) {
            $pactDir = \str_replace('\\', \DIRECTORY_SEPARATOR, $pactDir);
        }

        $this->pactDir = $pactDir;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPactFileWriteMode()
    {
        return $this->pactFileWriteMode;
    }

    /**
     * {@inheritdoc}
     */
    public function setPactFileWriteMode($pactFileWriteMode)
    {
        $options = ['overwrite', 'merge'];

        if (!\in_array($pactFileWriteMode, $options)) {
            $implodedOptions = \implode(', ', $options);

            throw new \InvalidArgumentException("Invalid PhpPact File Write Mode, value must be one of the following: {$implodedOptions}.");
        }

        $this->pactFileWriteMode = $pactFileWriteMode;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPactSpecificationVersion()
    {
        return $this->pactSpecificationVersion;
    }

    /**
     * {@inheritdoc}
     */
    public function setPactSpecificationVersion($pactSpecificationVersion)
    {
        $this->pactSpecificationVersion = $pactSpecificationVersion;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * {@inheritdoc}
     */
    public function setLog($log)
    {
        $this->log = $log;

        return $this;
    }

    public function hasCors()
    {
        return $this->cors;
    }

    public function setCors($flag)
    {
        if ($flag === 'true') {
            $this->cors = true;
        } elseif ($flag === 'false') {
            $this->cors = false;
        } else {
            $this->cors = (bool) $flag;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setHealthCheckTimeout($timeout)
    {
        $this->healthCheckTimeout = $timeout;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getHealthCheckTimeout()
    {
        return $this->healthCheckTimeout;
    }
}
