<?php

namespace Madkom\PactClient;

use Madkom\PactClient\Http\HttpMockServiceCollaborator;
use Madkom\PactClient\MockService\Config;
use Symfony\Component\Process\Process;
use Http\Client\Exception\NetworkException;

class MockService {
    /** @var int command exit code */
    private $exitCode;

    /**
     * @var MockService\Config
     */
    private $config;

    /**
     * @var Process
     */
    private $process;

    /**
     * @var HttpMockServiceCollaborator
     */
    protected $pact;

    protected $output = '';

    public function __construct(Config $config, HttpMockServiceCollaborator $pact)
    {
        $this->config = $config;
        $this->pact = $pact;
        $this->exitCode  = -1;
    }

    public function start()
    {
        $command = realpath(dirname(__FILE__) . '/../../bin/pact-mock-service');
        $arguments = $this->getArguments();

        $this->process = new Process($command . ' ' . \implode(' ', $arguments));

        $processId =  $this->process->start(array($this, 'output'));

        $this->verifyHealthCheck();

        return $processId;
    }

    public function output($type, $data)
    {
        if ($type == Process::OUT)
        {
            $this->output .= "\n\e[34m$data\e[39m";
        } else {
            $this->output .= "\n\e[31m$data\e[39m";
        }
    }

    public function stop()
    {
        return $this->process->stop();
    }

    public function verifyHealthCheck()
    {
        if (!$this->pact) return;

        // Verify that the service is up.
        $tries    = 0;
        $maxTries = $this->config->getHealthCheckTimeout();
        do {
            ++$tries;

            try {
                return $this->pact->healthCheck();
            } catch (NetworkException $e) {
                \sleep(1);
            }
        } while ($tries <= $maxTries);

        throw new PactException("Failed to make connection to Mock Server in {$maxTries} attempts.{$this->output}");
    }

    /**
     * Build an array of command arguments.
     *
     * @return array
     */
    private function getArguments()
    {
        $results = [];

        $results[] = 'service';
        $results[] = "--consumer={$this->config->getConsumer()}";
        $results[] = "--provider={$this->config->getProvider()}";
        $results[] = "--pact-dir={$this->config->getPactDir()}";
        $results[] = "--pact-file-write-mode={$this->config->getPactFileWriteMode()}";
        $results[] = "--host={$this->config->getHost()}";
        $results[] = "--port={$this->config->getPort()}";

        if ($this->config->hasCors()) {
            $results[] = '--cors=true';
        }

        if ($this->config->getPactSpecificationVersion() !== null) {
            $results[] = "--pact-specification-version={$this->config->getPactSpecificationVersion()}";
        }

        if ($this->config->getLog() !== null) {
            $results[] = "--log={$this->config->getLog()}";
        }

        return $results;
    }
}