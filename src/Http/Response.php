<?php namespace Braceyourself\Yourmembership\Http;

use Braceyourself\Yourmembership\Contracts\HasSessionId;

class Response extends BaseResponse implements HasSessionId
{
    public function getSessionId()
    {
        return $this->json('SessionId');
    }

    public function toArray()
    {
        return $this->json();
    }

    public function json($key = null, $default = null)
    {
        if (!$this->decoded) {
            $this->decoded = json_decode($this->body(), true);
        }

        if ($key === null && is_int($this->decoded)) {
            return ['status' => $this->decoded];
        }

        if ($key === null && $this->decoded === null && empty($this->body())) {
            return [
                'status'  => $this->status(),
            ];
        }

        if (is_null($key)) {
            return $this->decoded;
        }

        return data_get($this->decoded, $key, $default);
    }
}