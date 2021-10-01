<?php namespace Braceyourself\Yourmembership;


use Illuminate\Support\Collection;
use Mtownsend\XmlToArray\XmlToArray;

class Response extends \Illuminate\Http\Client\Response
{
    public Collection $data;
    private $request_method;
    private $error_code;
    private $error_info;

    public function __construct($response, $request_method)
    {
        parent::__construct($response);

        $data = collect($this->xml());
        $this->request_method = $request_method;
        $this->error_code = $data->get('ErrCode');
        $this->error_info = $data->get('ExtendedErrorInfo');
        $this->data = collect($data->get($request_method));

    }

    public function xml($key = null, $default = null)
    {
        if (!$this->decoded) {
            $this->decoded = XmlToArray::convert($this->body());
        }

        if (is_null($key)) {
            return $this->decoded;
        }


        return data_get($this->decoded, $key, $default);
    }

}
