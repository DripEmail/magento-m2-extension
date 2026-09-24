<?php

namespace Drip\Connect\Model\Http;

use Laminas\Http\Client as LaminasHttpClient;

/**
 * Rest client
 */
class Client extends LaminasHttpClient
{
    /** @var \Monolog\Logger */
    protected $logger;

    /** @var \Drip\Connect\Model\Http\RequestIDFactory */
    protected $requestIdFactory;

    public function __construct(
        $uri,
        array $config,
        \Monolog\Logger $logger,
        \Drip\Connect\Model\Http\RequestIDFactory $requestIdFactory
    ) {
        $this->logger = $logger;
        $this->requestIdFactory = $requestIdFactory;
        parent::__construct($uri, $config);
    }

    /**
     * Send the HTTP request and return an HTTP response object
     *
     * @param string $method
     * @return \Laminas\Http\Response
     * @throws \Laminas\Http\Client\Exception\ExceptionInterface
     */
    public function request($method = null)
    {
        // ID unique to each outgoing API request.
        // ID unique to each triggering Magento page load. Useful for
        // debouncing multiple events within a single Magento request.
        $headers = [
            'X-Drip-Connect-Request-Id' => uniqid(),
            'X-OMS-Request-Id' => $this->requestIdFactory->create()->requestId(),
        ];

        $this->setHeaders($headers);
        $this->setMethod($method);

        $requestBody = $this->getRequest()->getContent();
        $requestUrl = $this->getUri()->toString();
        $response = $this->send();
        $responseData = $response->getBody();

        $this->logger->info("[{$headers['X-Drip-Connect-Request-Id']}] Request Url: {$requestUrl}");
        $this->logger->info("[{$headers['X-Drip-Connect-Request-Id']}] Request Body: {$requestBody}");
        $this->logger->info("[{$headers['X-Drip-Connect-Request-Id']}] Response: {$responseData}");

        return $response;
    }
}
