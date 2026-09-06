<?php

namespace App\Classes;

use Illuminate\Contracts\Support\Jsonable;

class AjaxResponse implements Jsonable
{
    private $success;

    private $message;

    private $data;

    private function __construct()
    {
        $this->success = true;
        $this->message = null;
        $this->data = [];
    }

    public static function success(): self
    {
        $response = new self;
        $response->success = true;

        return $response;
    }

    public static function failed(): self
    {
        $response = new self;
        $response->success = false;

        return $response;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function onCondition(bool $condition, callable $callback): self
    {
        if ($condition) {
            call_user_func($callback, $this);
        }

        return $this;
    }

    public function toJson($options = 0): string
    {
        return json_encode([
            'success' => $this->success,
            'message' => $this->message,
            'data' => $this->data,
        ]);
    }
}
