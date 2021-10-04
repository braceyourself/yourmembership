<?php namespace Braceyourself\Yourmembership\Http;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Spatie\ArrayToXml\ArrayToXml;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Http\Client\ConnectionException;

class XmlRequest extends PendingRequest
{
    public function __construct(Factory $factory = null)
    {
        parent::__construct($factory);
    }

    public function post(string $url, array $data = [])
    {
        return $this->send('POST', $url, [
            $this->bodyFormat => $data,
        ]);
    }

    public function send(string $method, string $url, array $options = [])
    {
        $request_method = $url;
        $this->withBody(
            $this->convertForRequest($url, $options),
            'application/x-www-form-urlencoded'
        );

        $url = ltrim(rtrim($this->baseUrl, '/') . '/' . ltrim($url, '/'), '/');
        $options = $this->setUpRequestBody($options);

        return retry($this->tries ?? 1, function () use ($method, $url, $options, $request_method) {
            try {
                $options = $this->mergeOptions([
                    'laravel_data' => $this->parseRequestData($method, $url, $options),
                    'on_stats'     => function ($transferStats) {
                        $this->transferStats = $transferStats;
                    },
                ], $options);

                $request = $this->buildClient()->request($method, $url, $options);

                return tap(new XmlResponse($request, $request_method), function ($response) {
                    $response->cookies = $this->cookies;
                    $response->transferStats = $this->transferStats;

                    if ($this->tries > 1 && !$response->successful()) {
                        $response->throw();
                    }
                });
            } catch (ConnectException $e) {
                throw new ConnectionException($e->getMessage(), 0, $e);
            }
        }, $this->retryDelay ?? 100);
    }

    public function convertForRequest(&$method, &$options)
    {
        $options = collect($options);
        $data = $options->pull('query') ?? $options->pull('json') ?? [];
        $data = array_merge_recursive($this->options['body'], $data);
        unset($this->options['body']);

        $call_data = \Arr::get($data, 'Call', '');
        $data['Call'] = [
            '_attributes' => [
                'Method' => $method
            ]
        ];
        if (is_array($call_data)) {
            $data['Call'] = array_merge($data['Call'], $call_data);
        } else {
            $data['Call']['_value'] = $call_data;
        }


        $data = (new ArrayToXml($data,
            'YourMembership',
            true,
            'utf-8'
        ))->prettify()->toXml();

        $options->put('body', $data);
        $options = $options->toArray();
        $method = '';

        return $data;
    }

    private function setUpRequestBody($options)
    {
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
        }

        [$this->pendingBody, $this->pendingFiles] = [null, []];

        return $options;
    }
}
