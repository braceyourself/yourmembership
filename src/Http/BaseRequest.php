<?php

namespace Braceyourself\Yourmembership\Http;

use GuzzleHttp\Exception\ConnectException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;

abstract class BaseRequest extends PendingRequest
{
    public function createResponse()
    {
        return new Response(...func_get_args());
    }

    public function send(string $method, string $url, array $options = [])
    {
        $url = ltrim(rtrim($this->baseUrl, '/') . '/' . ltrim($url, '/'), '/');

        if (isset($options[$this->bodyFormat])) {
            if ($this->bodyFormat === 'multipart') {
                $options[$this->bodyFormat] = $this->parseMultipartBodyFormat($options[$this->bodyFormat]);
            } elseif ($this->bodyFormat === 'body') {
                $options[$this->bodyFormat] = $this->pendingBody;
            }

            if (is_array($options[$this->bodyFormat])) {
                $options[$this->bodyFormat] = array_merge(
                    $options[$this->bodyFormat], $this->pendingFiles
                );
            }
        } else {
            $options[$this->bodyFormat] = $this->pendingBody;
        }

        [$this->pendingBody, $this->pendingFiles] = [null, []];

        if ($this->async) {
            return $this->makePromise($method, $url, $options);
        }

        return retry($this->tries ?? 1, function () use ($method, $url, $options) {
            try {
                return tap($this->createResponse($this->sendRequest($method, $url, $options)), function ($response) {
                    $this->populateResponse($response);

                    if ($this->tries > 1 && !$response->successful()) {
                        $response->throw();
                    }

                    $this->dispatchResponseReceivedEvent($response);
                });
            } catch (ConnectException $e) {
                $this->dispatchConnectionFailedEvent();

                throw new ConnectionException($e->getMessage(), 0, $e);
            }
        }, $this->retryDelay ?? 100, $this->retryWhenCallback);
    }
}