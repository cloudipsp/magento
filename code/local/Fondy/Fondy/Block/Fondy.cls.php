<?php
class FondyForm
{
    const RESPONCE_SUCCESS = 'success';
    const RESPONCE_FAIL = 'failure';
    const ORDER_SEPARATOR = '#';
    const SIGNATURE_SEPARATOR = '|';
    const ORDER_APPROVED = 'approved';
    const ORDER_DECLINED = 'declined';

    public static function getSignature($data, $password, $encoded = true)
    {
        $data = array_filter($data, function($var) {
            return $var !== '' && $var !== null;
        });
        ksort($data);
        $str = $password;
        foreach ($data as $k => $v) {
            $str .= FondyForm::SIGNATURE_SEPARATOR . $v;
        }
        if ($encoded) {
            return sha1($str);
        } else {
            return $str;
        }
    }
    public static function isPaymentValid($oplataSettings, $response)
    {
		$responseSignature = $response['signature'];
		$data = $response['data'];
		$response = json_decode(base64_decode($response['data']),TRUE)['order'];

        if ($oplataSettings['merchant_id'] != $response['merchant_id']) {
            return 'An error has occurred during payment. Merchant data is incorrect.';
        }
        if ($response['order_status'] == FondyForm::ORDER_DECLINED) {
            Mage::throwException('An error has occurred during payment. Order is declined.');
        }
		if ($response['order_status'] != FondyForm::ORDER_APPROVED) {
            Mage::throwException('An error has occurred during payment. Order is not approv.');
        }
		if ($responseSignature != sha1($oplataSettings['secret_key'] .'|'. $data)) {
            return 'An error has occurred during payment. Signature is not valid.';
        }
        return true;
    }


}

