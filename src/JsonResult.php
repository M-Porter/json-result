<?php

namespace MPorter\JsonResult;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Loosely based on https://jsonapi.org/format/#document-top-level
 *
 * @package MPorter\JsonResult
 */
class JsonResult
{
    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var mixed
     */
    private $data = null;

    /**
     * @var array
     */
    private $meta = null;

    /**
     * @var int|null
     */
    private $status = Response::HTTP_OK;

    /**
     * @return JsonResult
     */
    public static function make(): JsonResult
    {
        return new static();
    }

    /**
     * Appends error object to array of errors for response.
     *
     * @param string $title
     * @param int $status
     * @param string|null $code
     * @param string|null $detail
     *
     * @return JsonResult
     */
    public function addError(
        string $title,
        int $status = Response::HTTP_BAD_REQUEST,
        string $code = null,
        string $detail = null
    ): self {
        $error = [
            'title' => $title,
            'status' => (string) $status,
        ];

        if ($code) {
            $error['code'] = $code;
        }

        if ($detail) {
            $error['detail'] = $detail;
        }

        $this->errors[] = $error;

        return $this;
    }

    /**
     * Sets the data payload for the object
     *
     * @param mixed $data
     * @return self
     */
    public function setData($data = null): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @param int $status
     * @return self
     */
    public function setStatusCode(int $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @param array $meta
     * @return self
     */
    public function setMeta(array $meta): self
    {
        $this->meta = $meta;
        return $this;
    }

    /**
     * Builds and returns an appropriate response based on the instance.
     *
     * If an error has been added to the result instance, the set status code
     * is overridden with either a 400 or 500 error, whichever is greater based
     * on the added errors.
     *
     * @return JsonResponse
     */
    public function build(): JsonResponse
    {
        $response = new JsonResponse();

        $status = $this->status;
        $body = [];

        if ($this->meta) {
            $body['meta'] = $this->meta;
        }

        if (count($this->errors)) {
            $body['errors'] = $this->errors;

            foreach ($body['errors'] as $error) {
                if ($error['status'] >= 500) {
                    $status = Response::HTTP_INTERNAL_SERVER_ERROR;
                    break; // highest we can go so break here
                }

                if ($error['status'] >= 400) {
                    $status = Response::HTTP_BAD_REQUEST;
                }
            }
        } else {
            $body['data'] = $this->data;
        }

        $response->setStatusCode($status);
        $response->setData($body);

        return $response;
    }
}
