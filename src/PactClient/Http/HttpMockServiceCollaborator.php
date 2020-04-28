<?php

namespace Madkom\PactClient\Http;

use Http\Client\HttpClient;
use Madkom\PactClient\Domain\Interaction\Interaction;
use Madkom\PactClient\Http\Service\RequestBuilder;
use Madkom\PactClient\PactException;
use Psr\Http\Message\ResponseInterface;

/**
 * Class ConsumerPactBuilder
 * Usage:
 * ->given
 * ->uponReceiving
 * ->with
 * ->willRespondWith
 * @package Madkom\PactClient\Http
 * @author  Dariusz Gafka <d.gafka@madkom.pl>
 */
class HttpMockServiceCollaborator
{
    /**
     * @var HttpClient
     */
    private $client;
    /**
     * @var string
     */
    private $host;
    /**
     * @var RequestBuilder
     */
    private $requestBuilder;
    /**
     * @var string
     */
    private $consumerName;
    /**
     * @var string
     */
    private $providerName;
    /**
     * @var string
     */
    private $contractDir;

    /**
     * ConsumerPactBuilder constructor.
     *
     * @param HttpClient $client All Http Clients, which implement Httplug interface
     * @param string     $host   Example http://localhost:1234
     * @param string     $consumerName
     * @param string     $providerName
     * @param string     $contractDir If no passed, it take contract dir set up on mock-service startup
     */
    public function __construct(HttpClient $client, $host, $consumerName, $providerName, $contractDir = null)
    {
        $this->client = $client;
        $this->host = $host;
        $this->consumerName = $consumerName;
        $this->providerName = $providerName;
        $this->contractDir = getcwd() . '/' . $contractDir;

        $this->requestBuilder = new RequestBuilder($this->host);
    }

    /**
     * Set up interaction between consumer and provider
     *
     * @param Interaction $interaction
     *
     * @throws PactException
     */
    public function setupInteraction(Interaction $interaction)
    {
        $response = $this->client->sendRequest($this->requestBuilder->buildRemoveExpectationsRequest());
        $this->validateResponse($response);

        $response = $this->client->sendRequest($this->requestBuilder->buildCreateInteractionRequest($interaction));
        $this->validateResponse($response);
    }

    /**
     * It verifies set up interaction
     */
    public function verify()
    {
        $response = $this->client->sendRequest($this->requestBuilder->buildVerifyInteractionRequest());

        $this->validateResponse($response);
    }

    public function healthCheck()
    {
        $response = $this->client->sendRequest($this->requestBuilder->buildHealthCheckRequest());

        if ($response->getStatusCode() !== 200
            || $response->getBody()->getContents() !== "Mock service running\n") {
            throw new ConnectionException('Failed to receive a successful response from the Mock Server.');
        }

        return true;
    }

    protected function savePact($response)
    {
        $status = file_put_contents(
            "{$this->contractDir}/{$this->consumerName}-{$this->providerName}.json",
            $response->getBody()->getContents()
        );

        if ($status === false) {
            throw new PactException("Unable to save pact to {$this->contractDir}/{$this->consumerName}-{$this->providerName}.json");
        }
    }

    /**
     * It ends provider verification process
     *
     */
    public function finishProviderVerificationProcess()
    {
        $response = $this->client->sendRequest(
            $this->requestBuilder->buildEndProviderTestRequest(
                $this->consumerName,
                $this->providerName,
                $this->contractDir
            )
        );

        $this->validateResponse($response);
        $this->savePact($response);
    }

    /**
     * @param ResponseInterface $response
     *
     * @throws PactException
     */
    private function validateResponse(ResponseInterface $response)
    {
        if ($response->getStatusCode() != 200) {
            throw new PactException($response->getBody()->getContents());
        }
    }
}
