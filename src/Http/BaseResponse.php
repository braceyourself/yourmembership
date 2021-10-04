<?php namespace Braceyourself\Yourmembership\Http;

use Illuminate\Contracts\Support\Arrayable;

abstract class BaseResponse extends \Illuminate\Http\Client\Response implements Arrayable
{

    public function hasErrors()
    {
        $code = $this->json('ResponseStatus.ErrorCode');

        return $code !== null && !empty($code);
    }

    public function getErrorAsString()
    {
        $error_code = $this->json('ResponseStatus.ErrorCode');
        $message = trim($this->json('ResponseStatus.Message'));

        return "ErrorCode: $error_code\n  $message";
    }
}