<?php


namespace App\Helpers;


class ResultData
{

    private $errors;
    private $data;
    private $status;
    private $message;
    private $codes;


    public function __construct()
    {

        #default values
        $this->errors = [];
        $this->data = null;
        $this->status = true;
        $this->codes = [
            'no_user' => 'هیچ کاربری با این مشخصات یافت نشد',
            'notFoundErrorCode' => 'not found code',
        ];
        $this->message = '';


    }


    private function getMessage()
    {
        return $this->message;
    }


    private function setMessage($code, $additional_data = null)
    {
        if (array_key_exists($code, $this->codes)) {
            $this->message = $this->codes[$code];
            $this->message .= $additional_data;
        } else {
            $this->message = $this->codes['notFoundErrorCode'] . 'code:' . $code;
        }

        $this->setStatus(false);
    }


    /**
     * @return array
     */
    private function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @param array $errors
     * @return $this
     */
    public function setErrors(array $errors)
    {
        #errors is array
        $this->errors = $errors;

        $this->setStatus(false);
        $this->setData(null);

        return $this;
    }


    private function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return mixed
     */
    private function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    private function setStatus($status)
    {
        if ($status == 0 OR $status = false) {
            $this->status = false;
        }

    }

    /**
     * show
     */
    public function get()
    {
        if ($this->status == false) {
            $this->setData(null);
        }
        $data = [
            'status' => $this->getStatus(),
            'errors' => $this->getErrors(),
            'data' => $this->getData(),
        ];

        header('Content-Type: application/json');

        return response()->json($data);

    }
}
