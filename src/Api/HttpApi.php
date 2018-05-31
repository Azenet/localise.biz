<?php

/*
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace FAPI\Localise\Api;

use FAPI\Localise\Exception\Domain as DomainExceptions;
use FAPI\Localise\Exception\DomainException;
use FAPI\Localise\Hydrator\NoopHydrator;
use Http\Client\HttpClient;
use FAPI\Localise\Hydrator\Hydrator;
use FAPI\Localise\RequestBuilder;
use Psr\Http\Message\ResponseInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
abstract class HttpApi
{
    /**
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * @var Hydrator
     */
    protected $hydrator;

    /**
     * @var RequestBuilder
     */
    protected $requestBuilder;

    /**
     * @param HttpClient     $httpClient
     * @param RequestBuilder $requestBuilder
     * @param Hydrator       $hydrator
     */
    public function __construct(HttpClient $httpClient, Hydrator $hydrator, RequestBuilder $requestBuilder)
    {
        $this->httpClient = $httpClient;
        $this->requestBuilder = $requestBuilder;
        if (!$hydrator instanceof NoopHydrator) {
            $this->hydrator = $hydrator;
        }
    }

    /**
     * Send a GET request with query parameters.
     *
     * @param string $path           Request path
     * @param array  $params         GET parameters
     * @param array  $requestHeaders Request Headers
     *
     * @return ResponseInterface
     */
    protected function httpGet($path, array $params = [], array $requestHeaders = [])
    {
        if (count($params) > 0) {
            $path .= '?'.http_build_query($params);
        }

        return $this->httpClient->sendRequest(
            $this->requestBuilder->create('GET', $path, $requestHeaders)
        );
    }

    /**
     * Send a POST request with JSON-encoded parameters.
     *
     * @param string $path           Request path
     * @param array  $params         POST parameters to be JSON encoded
     * @param array  $requestHeaders Request headers
     *
     * @return ResponseInterface
     */
    protected function httpPost($path, array $params = [], array $requestHeaders = [])
    {
        $requestHeaders['Content-Type'] = 'application/x-www-form-urlencoded';

        return $this->httpPostRaw($path, http_build_query($params), $requestHeaders);
    }

    /**
     * Send a POST request with raw data.
     *
     * @param string       $path           Request path
     * @param array|string $body           Request body
     * @param array        $requestHeaders Request headers
     *
     * @return ResponseInterface
     */
    protected function httpPostRaw($path, $body, array $requestHeaders = [])
    {
        return $response = $this->httpClient->sendRequest(
            $this->requestBuilder->create('POST', $path, $requestHeaders, $body)
        );
    }

    /**
     * Send a PUT request with JSON-encoded parameters.
     *
     * @param string $path           Request path
     * @param array  $params         POST parameters to be JSON encoded
     * @param array  $requestHeaders Request headers
     *
     * @return ResponseInterface
     */
    protected function httpPut($path, array $params = [], array $requestHeaders = [])
    {
        $requestHeaders['Content-Type'] = 'application/x-www-form-urlencoded';

        return $this->httpClient->sendRequest(
            $this->requestBuilder->create('PUT', $path, $requestHeaders, http_build_query($params))
        );
    }

    /**
     * Send a PATCH request with JSON-encoded parameters.
     *
     * @param string $path           Request path
     * @param array  $params         PATCH parameters to be JSON encoded
     * @param array  $requestHeaders Request headers
     *
     * @return ResponseInterface
     */
    protected function httpPatch($path, array $params = [], array $requestHeaders = [])
    {
        $requestHeaders['Content-Type'] = 'application/json';

        return $this->httpClient->sendRequest(
            $this->requestBuilder->create('PATCH', $path, $requestHeaders, json_encode($params))
        );
    }

    /**
     * Send a DELETE request with JSON-encoded parameters.
     *
     * @param string $path           Request path
     * @param array  $params         POST parameters to be JSON encoded
     * @param array  $requestHeaders Request headers
     *
     * @return ResponseInterface
     */
    protected function httpDelete($path, array $params = [], array $requestHeaders = [])
    {
        $requestHeaders['Content-Type'] = 'application/x-www-form-urlencoded';

        return $this->httpClient->sendRequest(
            $this->requestBuilder->create('DELETE', $path, $requestHeaders, http_build_query($params))
        );
    }

    /**
     * Handle HTTP errors.
     *
     * Call is controlled by the specific API methods.
     *
     * @param ResponseInterface $response
     *
     * @throws DomainException
     */
    protected function handleErrors(ResponseInterface $response)
    {
        $body = $response->getBody()->__toString();

        $content = json_decode($body, true);
        $message = '';
        if (JSON_ERROR_NONE === json_last_error()) {
            $message = $content['error'];
        }

        switch ($response->getStatusCode()) {
            case 401:
                throw new DomainExceptions\InvalidApiKeyException($message);
                break;
            case 403:
                throw new DomainExceptions\InsufficientPrivilegesException($message);
                break;
            case 404:
                throw new DomainExceptions\NotFoundException($message);
                break;

            default:
                throw new DomainExceptions\UnknownErrorException($message);
                break;
        }
    }
}
