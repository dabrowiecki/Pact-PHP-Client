<?php

namespace Madkom\PactClient;

use Madkom\PactClient\MockService\Config;
use PHPUnit_Framework_AssertionFailedError as AssertionFailedError;
use PHPUnit_Framework_Test as Test;
use PHPUnit_Framework_TestListener as TestListener;
use PHPUnit_Framework_TestSuite as TestSuite;
use Http\Client\Curl\Client;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Discovery\StreamFactoryDiscovery;
use Madkom\PactClient\Http\HttpMockServiceCollaborator;
use Exception;


/**
 * PACT listener that can be used with environment variables and easily attached to PHPUnit configuration.
 * Class PactTestListener
 */
class Listener implements TestListener
{
    /** @var MockServer */
    private $server;

    /**
     * Name of the test suite configured in your phpunit config.
     *
     * @var string
     */
    private $testSuiteNames;

    /** @var MockServerConfigInterface */
    private $mockServerConfig;

    /** @var bool */
    private $failed = false;

    private $startMock = false;

    /**
     * PactTestListener constructor.
     *
     * @param string[] $testSuiteNames test suite names that need evaluated with the listener
     *
     * @throws MissingEnvVariableException
     */
    public function __construct(array $testSuiteNames, $startMock = false)
    {
        $this->testSuiteNames   = $testSuiteNames;
        $this->mockServerConfig = new Config();
        $this->startMock = $startMock;
    }

    /**
     * @param TestSuite $suite
     *
     * @throws Exception
     */
    public function startTestSuite(TestSuite $suite)
    {

        if (!\in_array($suite->getName(), $this->testSuiteNames)) {
$this->testSuiteNames = [$suite->getName()];
            $this->server = new MockService($this->mockServerConfig);
            $this->server->start();
        } else {
            throw new \Exception($suite->getName());
        }
}

    public function addError(Test $test, Exception $e, $time)
    {
        $this->failed = true;
    }

    public function addFailure(Test $test, AssertionFailedError $e, $time)
    {
        $this->failed = true;
    }

    public function addIncompleteTest(Test $test, Exception $e, $time) {}

    public function addRiskyTest(Test $test, Exception $e, $time) {}

    public function addSkippedTest(Test $test, Exception $e, $time) {}

    public function startTest(Test $test) {}

    public function endTest(Test $test, $time) {}

    protected function getClient()
    {
        return new Client(
            MessageFactoryDiscovery::find(),
            StreamFactoryDiscovery::find()
        );
    }

    /**
     * @return \Madkom\PactClient\Http\HttpMockServiceCollaborator;
     */
    protected function getPact()
    {
        return new HttpMockServiceCollaborator(
            $this->getClient(),
            $this->mockServerConfig->getBaseUri(),
            $this->mockServerConfig->getConsumer(),
            $this->mockServerConfig->getProvider(),
            $this->mockServerConfig->getPactDir()
        );
    }

    /**
     * Publish JSON results to PACT Broker and stop the Mock Server.
     *
     * @param TestSuite $suite
     */
    public function endTestSuite(TestSuite $suite)
    {
        if (!\in_array($suite->getName(), $this->testSuiteNames)) return;

        try {
            $pact = $this->getPact();
            $pact->verify();

            if ($this->failed === true) {
                print 'A unit test has failed. Skipping PACT file generation.';
            } else {
                $pact->finishProviderVerificationProcess();
            }
        } finally {
            if ($this->startMock)
            {
                $this->server->stop();
            }
        }
    }
}
