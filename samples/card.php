<?php
include realpath(__DIR__ . '/..').'/src/voguepay.php'; // location of the voguepay.php class
use VoguePay\voguepay;
$data = [];
$data = [
    "version" => "2", // version of the API to be called 
    "merchant" => [
        "merchantUsername" => "***", // merchant username on VoguePay
        "merchantID" => "***-***", // merchant merchantID on VoguePay
        "merchantEmail" => "***@yahoo.com", // registered email address on VoguePay
        "apiToken" => "wakXpBajDrX5mzxdyUCX7bgH3UY5CA", // Command API token
        "publicKey" => file_get_contents('key.crt') // location of the stored public key
    ],
    "card" => [
        "name" => "Test Name", //Card holder name
        "pan" => "5123 4500 0000 0008", //Card pan number
        "month" => "05", //Card expiry month e.g 06
        "year" => "21", //Card expiry year e.g 21
        "cvv" => "100" //Card CVV number
    ],
    "customer" => [
        "email" => "test@gmail.com", // Email of country
        "phone" => "1234567890", // phone number of country
        "address" => "Customer address goes here", // address of customer
        "state" => "State Goes here", // state or province of customer
        "zipCode" => "100005", // zip code of customer
        "country" => "Canada" // country of country - Valid country or valid 3 letter ISO
    ],
    "transaction" => [
        "amount" => 100, //amount to be charged
        "description" => "Payment Description Goes Here", //Description of payment
        "reference" => "1x2345vbn", // Unique transaction reference, this is returned with the transaction details
        "currency" => "USD", //Supported currency USD, GBP, EUR, NGN
    ],
    "notification" => [
        "callbackUrl" => "https://triggerme.com/transaction_notification", // Url where a transaction details will be sent on transaction completion
        "redirectUrl" => "https://redirecttome.com/transaction_notification" // Url where the customer is redirected on transaction completion
    ],
    "descriptor" => [
        "companyName" => "*******", // {Optional} - Company name
        "countryIso" => "***" //3 letter country ISO
    ],
    "demo" => true, // boolean (true / false) , set to true to imitate a demo transaction and false for live transaction
];
print_r(voguepay::card($data)); 
/*
stdClass Object
(
    [description] => Redirection Required - 3D Authentication required. // Response code description
    [redirectUrl] => https://voguepay.com/?p=vpgate&ref=czoxMzoiNWNiZjQ2OTBlNDFkMCI7 // 3D redirection URL
    [reference] => 1x2345vbn // Transaction reference
    [response] => WL3D // Transaction response
    [status] => OK // API query status
    [transactionID] => 5cbf4690e41d0 // Generated VoguePay transaction ID
)
*/