<?php namespace Braceyourself\Yourmembership;

use Braceyourself\Yourmembership\Http\BaseResponse;

class InvalidSessionId extends \Exception
{
    public function __construct(BaseResponse $response)
    {
        parent::__construct(
            "Could not retrieve Session ID. [ErrCode: " . $response->xml('ErrCode') . "] ErrDesc: " . $response->xml('ErrDesc')
        );
    }

}
