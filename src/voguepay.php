<?php
namespace VoguePay;
include dirname(__FILE__).'/merchant.php';
use VoguePay\MerchantConfiguration;
include dirname(__FILE__).'/connection.php';
use VoguePay\Connection;
include dirname(__FILE__).'/responses.php';
use VoguePay\Responses;
class VoguePay {
    public static function card ($data) {
        // making data an std class
        $data = json_decode(json_encode($data));
        $reference = time().mt_rand(0,9999999);
        //generate confirmation hash
        $hash = hash('sha512', $data->merchant->apiToken.MerchantConfiguration::card().$data->merchant->merchantEmail.$reference);
        // process card details
        $cardDetails = (object) [
            "card" => [
                "name" => $data->card->name, // Name of card holder
                "pan" => preg_replace('/\s+/', '', $data->card->pan), // Card Pan
                "month" => preg_replace('/\s+/', '', $data->card->month), // Expiry month 01-12
                "year" => preg_replace('/\s+/', '', $data->card->year), // expiry year, expected 2 digits e.g 21
                "cvv" => preg_replace('/\s+/', '', $data->card->cvv) // card cvv details
            ]
        ];

        if (empty($data->demo)) $data->demo = false;
        //expected data request
        $payLoad = (object) [
            "merchant" => $data->merchant->merchantID, // merchant ID
            "task" => MerchantConfiguration::card(), //Operation to be performed
            "ref" => $reference, // Random Reference
            "hash" => $hash, // Transaction Hash
            "version" => (!empty($data->version)) ? $data->version : '',
            "email" => $data->customer->email, // Transacting customer email
            "phone" => $data->customer->phone, // customer phone details
            "customerAddress" => $data->customer->address, // customer phone details
            "customerState" => $data->customer->state, // customer phone details
            "customerZip" => $data->customer->zipCode, // customer phone details
            "customerLocation" => $data->customer->country, // customer phone details
            "total" => $data->transaction->amount, // Transaction amount, round to 2 digits 10.00
            "merchant_ref" => $data->transaction->reference, // Unique transaction reference
            "currency" => $data->transaction->currency, // Trasnaction currency - {Optional}
            "memo" => $data->transaction->description, // Transaction description
            "response_url" => $data->notification->callbackUrl, // Callback Url - transaction response is sent here
            "redirect_url" => $data->notification->redirectUrl, // Redirection URL - Customer is redirected here when a transaction is completed
            "company" => (!empty($data->descriptor->companyName)) ? $data->descriptor->companyName : '', // Company name - Max allowed 100 {Optional}
            "country" => (!empty($data->descriptor->countryIso)) ? $data->descriptor->countryIso : '', // Company operational country - 3 letter ISO {Optional}
            "params" => Connection::encrypt(json_encode($cardDetails), $data->merchant->publicKey), // encrypted card data
            "riskAssessment" => json_encode($_SERVER), // Risk assesment
            "demo" => ($data->demo === true) ? true : false, // Set to true to do a testing transaction
        ];
        //initiate connection to VoguePay
        $receivedResponse = Connection::connect($payLoad);
        // validate the response received
        return Responses::getResponse($receivedResponse, $data->merchant);
    }
    public static function getResponse($data){
        $reference = time().mt_rand(0,9999999);
        $data = json_decode(json_encode($data));
        //generate hash
        $hash = hash('sha512',$data->merchant->apiToken.MerchantConfiguration::getResponse().$data->merchant->merchantEmail.$reference);
        if (empty($data->demo)) $data->demo = false;
        //process details needed for the hashing
        $payload = (object) [
            "merchant" => $data->merchant->merchantID,
            "merchant_email" => $data->merchant->merchantEmail,
            "hash" => $hash,
            "transaction_id" => $data->transactionID,
            "task" => MerchantConfiguration::getResponse(),
            "ref" => $reference,
            "demo" => ($data->demo === true) ? true : false, // Set to true to do a testing transaction
        ];

        $receivedResponse = Connection::connect($payload);
        return Responses::getResponse($receivedResponse, $data->merchant);
    }
    public static function chargeToken ($data) {
        $data = json_decode(json_encode($data));
        $reference = time().mt_rand(0,9999999);
        //generate confirmation hash
        $hash = hash('sha512', $data->merchant->apiToken.MerchantConfiguration::card().$data->merchant->merchantEmail.$reference);
        $card_details = (object) [
            "card" => [
                "cvv" => preg_replace('/\s+/', '', $data->card->cvv) // card cvv details
            ]
        ];
        //expected data request
        $payLoad = (object) [
            "version" => (!empty($data->version)) ? $data->version : '',
            "merchant" => $data->merchant->merchantID, // merchant ID
            "task" => MerchantConfiguration::card(), //Operation to be performed
            "ref" => $reference, // Random Reference
            "hash" => $hash, // Transaction Hash
            "total" => $data->transaction->amount, // Transaction amount, round to 2 digits 10.00
            "email" => $data->customer->email, // Transacting customer email
            "merchant_ref" => $data->transaction->reference, // Unique transaction reference
            "currency" => $data->transaction->currency, // Trasnaction currency - {Optional}
            "memo" => $data->transaction->description, // Transaction description
            "company" => (!empty($data->descriptor->companyName)) ? $data->descriptor->companyName : '', // Company name - Max allowed 100 {Optional}
            "country" => (!empty($data->descriptor->countryIso)) ? $data->descriptor->countryIso : '', // Company operational country - 3 letter ISO {Optional}
            "params" => Connection::encrypt(json_encode($card_details), $data->merchant->publicKey), // encrypted card data
            "riskAssessment" => json_encode($_SERVER), // risk assessment evaluation
            "token" => $data->card->token
        ];
        unset ($data->version);
        unset ($data->merchant->publicKey);
        unset ($data->card);
        unset ($data->customer);
        unset ($data->transaction);
        unset ($data->descriptor);
        $receivedResponse = (object) Connection::connect($payLoad);
        $data->transactionID = $receivedResponse->reference;
        if (!empty($receivedResponse->reference)) return self::getResponse($data);
        else return Responses::getResponse($receivedResponse, $data->merchant);
    }
}