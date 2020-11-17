<?php


namespace App\Utils;


use Facebook\Exceptions\FacebookSDKException;
use Facebook\HttpClients\FacebookHttpClientInterface;

class FGuzzle6HttpClient implements FacebookHttpClientInterface
{

    /**
     * @var \GuzzleHttp\Client
     */
    private $client;

    /**
     * FGuzzle6HttpClient constructor.
     * @param $client
     */
    public function __construct(\GuzzleHttp\Client $client)
    {
        $this->client = $client;
    }


    public function send($url, $method, $body, array $headers, $timeOut)
    {
        $request = new \GuzzleHttp\Psr7\Request($method, $url, $headers, $body);
        try {
            $response = $this->client->send($request, ['timeout' => $timeOut]);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            throw new FacebookSDKException($e);
        }

        $httpStatusCode = $response->getStatusCode();
        $responseHeaders = $response->getHeaders();

        // GraphRawResponse预计用于键的每个值的字符串而不是一个阵列，所以我们implode()
        foreach ($responseHeaders as $key => $values) {
            $responseHeaders[$key] = implode(', ', $values);
        }

        $responseBody = $response->getBody()->getContents();


        return new \Facebook\Http\GraphRawResponse(
            $responseHeaders,
            $responseBody,
            $httpStatusCode);
    }


}