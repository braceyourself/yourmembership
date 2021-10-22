<?php namespace Braceyourself\Yourmembership\Http;

use Illuminate\Support\Collection;
use Mtownsend\XmlToArray\XmlToArray;

class XmlResponse extends BaseResponse
{

    public Collection $data;
    private $request_method;
    private $error_code;
    private $error_info;

    public function __construct(\GuzzleHttp\Psr7\Response $response, $request_method)
    {
        parent::__construct($response);

        $data = collect($this->xml());
        $this->request_method = $request_method;
        $this->error_code = $data->get('ErrCode');
        $this->error_info = $data->get('ExtendedErrorInfo');
        $this->data = collect($data->get($request_method));

    }



    public function getSessionId()
    {
        return $this->xml(['Session.Create', 'SessionID']);
    }


    public function xml($key = null, $default = null)
    {
//        $xml = (string)\Str::of(html_entity_decode($this->body()));
//            ->replaceMatches('/<p>([^<]*)<\/p>/', fn($m) => $m[1] ?? null)
//            ->replaceMatches('/<a href=".*">(.*)<\/a>/', fn($m) => $m[1] ?? null);
        $xml = $this->body();

        if (!$this->decoded) {
            try {
                $this->decoded = XmlToArray::convert($xml);
            } catch (\ErrorException $e) {
                throw new XmlParserException($e->getMessage(), $e->getCode(), $e);
            }
        }

        if (is_null($key)) {
            return $this->decoded;
        }


        return data_get($this->decoded, $key, $default);
    }

    public function toArray()
    {
        return $this->data->toArray();
    }
}
