<?php namespace Braceyourself\Yourmembership;

use Braceyourself\Yourmembership\Http\BaseResponse;

class InvalidSessionId extends \Exception
{
    public function __construct(BaseResponse $response)
    {
        $code = method_exists($response, 'xml')
            ? $response->xml('ErrCode')
            : $response->json('ResponseStatus.ErrorCode');

        $desc = method_exists($response, 'xml')
            ? $response->xml('ErrDesc')
            : $response->json('ResponseStatus.Message');

        parent::__construct(
            "Could not retrieve Session ID. [ErrCode: $code] ErrDesc: $desc"
        );
    }

}
