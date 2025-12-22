<?php

namespace App\Services\Sms;

use Smsapi\Client\Curl\SmsapiHttpClient as BaseSmsapiHttpClient;

class SmsapiHttpClientNoVerify extends BaseSmsapiHttpClient
{
    protected function getCurlOptions(): array
    {
        $options = parent::getCurlOptions();
        
        // Disable SSL verification for local development
        $options[CURLOPT_SSL_VERIFYHOST] = 0;
        $options[CURLOPT_SSL_VERIFYPEER] = false;
        
        return $options;
    }
}
