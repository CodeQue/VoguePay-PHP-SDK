<?php
namespace Voguepay;
class Responses {
    private static $currency = [
        "$" => "USD",
        "£" => "GBP",
        "€" => "EUR",
        "₦" => "NGN"
    ];
    private static $riskCodes =  [
        "FC00S0" => 'Failed to pass security checks',
        "FC00S1" => 'Suspected transaction pattern. Transaction Blocked.',
        "FC00S2" => 'Multiple transactions exceeding risk assessment threshhold',
        "FC00S3" => 'Multiple transactions exceeding risk assessment threshhold',
        "FC00S4" => 'Multiple transactions exceeding risk assessment threshhold',
        "FC00S5" => 'Multiple transactions exceeding risk assessment threshhold',
        "FC00S6" => 'Multiple fingerprints detected for a single user',
        "FC00S7" => 'Multiple transactions on same card exceeding risk assessment threshhold',
        "FC04S8" => 'Multiple insufficient balance on same card',
        "FC00S9" => 'Transaction from blocked location',
        "FC00S10" => 'Card used has been registered to another account',
        "FC00S11" => 'Max allowed card usage exceeded',
        "FC00S12" => 'Exceeds permitted daily transaction treshold',
        "FC00S13" => 'Card Bin not allowed',
        "FC00S41" => "Referral URL not provided",
        "FC00S42" => "Server Ip could not be resolved. This is caused if a transaction details was sent from a new server IP. Kinldy request that the ip of your new server be enabled for use through support@voguepay.com",
        "FC00S43" => "Customer IP could not be resolved or blacklisted",
        "FC00S44" => "Server SSL certificate could not be validated. Ensure your server SSL is active.",
        "FC00S45" => "Transaction token provided could not be identified."
    ];
    private static $codes = [
        "X001" => "Merchant ID provided is Invalid. You can check your VoguePay merchant ID by login into your account at https://voguepay.com/login",
        "X002" => "A Random reference (ref) not provided.",
        "X003" => "Unable to generate a matching hash. This is caused if the details of the account you provided is Invalid. Login and check your settings page at https://voguepay.com/account_settings for the correct integration details.",
        "X004" => "The task specified is not supported. Please check https://voguepay.com/documentation for the supported tasks.",
        "X005" => "Merchant ID provided is Invalid. You can check your VoguePay merchant ID by login into your account at https://voguepay.com/login",
        "X006" => "Unable to generate a matching hash. This is caused if the details of the account you provided is Invalid. Login and check your settings page at https://voguepay.com/account_settings for the correct integration details.",
        "WL001" => "The details provided in {field} is invalid.",
        "WL002" => "Incorect / Insufficient details provided {message} - {field}",
        "WL003" => "Transaction failed to pass risk checks",
        "WL004" => "The provided currency ISO is not supported. Supported currency ISO are - GBP, USD, EURO, NGN, ZAR",
        "WL3D" => "Redirection Required - 3D Authentication required.",
        "Q001" => "No transactionID provided. Ensure that a valid transactionID id is provided.",
        "Q002" => "Records of the provided transactionID could not be found.",
        "OK" => "API query sucessful"
    ];
    public static function getResponse ($responseCode = [], $merchantDetails = []) {
        $responseCode = (object) $responseCode;
        $merchantDetails = (object) $merchantDetails;
        $merchantUsername = (!empty($responseCode->username)) ? $responseCode->username : '';
        if ($merchantUsername != $merchantDetails->merchantUsername) return (object) ["status" => "ERROR", "response" => "WL000", "message" => "No matching merchant details found. Process terminated."];
        else if ($responseCode->hash != hash('sha512',$merchantDetails->apiToken.$merchantDetails->merchantEmail.$responseCode->salt)) return (object) ["status" => "ERROR", "response" => "WL000", "message" => "Data integrity failure. Unable to generate matching hash details. Ensure connection details are correct. Visit https://voguepay.com/account_settings to get connection details. Process terminated"];
        else{

            if (empty($responseCode->response)) $responseCode->response = "WL003";
            if (empty($responseCode->status)) $responseCode->response = "ERROR";
            if (!empty($responseCode->field) AND !empty($responseCode->message) ){
                $search = [
                    "{field}",
                    "{message}"
                ];
                $replace = [
                    $responseCode->field,
                    $responseCode->message
                ];
                $responseCode->description = str_replace($search, $replace, self::$codes[$responseCode->response]);
            } else $responseCode->description = strtr($responseCode->response, self::$codes);
            // formating the response
            if ($responseCode->response == "WL003") $responseCode->description = $responseCode->message;
            if (!empty($responseCode->reference)) $responseCode->transactionID = $responseCode->reference;
            if (!empty($responseCode->merchant_ref)) $responseCode->reference = $responseCode->merchant_ref;
            if (!empty($responseCode->redirect_url)) $responseCode->redirectUrl = $responseCode->redirect_url;
            if (!empty($responseCode->riskCode)) $responseCode->riskDescription = strtr($responseCode->riskCode, self::$riskCodes);
            $responseCode->status = (property_exists($responseCode, 'status') && $responseCode->status == 'OK') ? 'OK' : 'ERROR';
            //formating the transaction response
            if (!empty($responseCode->transaction)) {
                $responseCode->transaction = (object) $responseCode->transaction;
                $responseCode->buyerDetails = (object) [];
                if(!empty(trim($responseCode->transaction->masked_pan))) {
                    $maskedPanItems = explode("-", trim($responseCode->transaction->masked_pan));
                    $maskedPan = $maskedPanItems[0] . "******" . $maskedPanItems[1];
                    $cardType = ucwords($maskedPanItems[2]);
                } else {
                    $maskedPan = "******";
                    $cardType = "";
                }
                $responseCode->transaction->currencySymbol = $responseCode->transaction->cur;
                $responseCode->transaction->currency = strtr($responseCode->transaction->cur, self::$currency);
                $responseCode->transaction->merchantID = $responseCode->transaction->merchant_id;
                $responseCode->transaction->transactionID = $responseCode->transaction->transaction_id;
                list($responseCode->transaction->transactionDate, $responseCode->transaction->transactionTime) = explode(" ", $responseCode->transaction->date, 2);
                $responseCode->transaction->reference = (!empty($responseCode->transaction->merchant_ref)) ? $responseCode->transaction->merchant_ref : '';
                $responseCode->transaction->description = $responseCode->transaction->memo;
                $responseCode->transaction->total = number_format($responseCode->transaction->total, 2);
                $responseCode->transaction->totalPaidByCustomer = ($responseCode->transaction->status == 'Approved') ? number_format($responseCode->transaction->total_paid_by_buyer, 2) : 0.00;
                $responseCode->transaction->creditedToMerchant = ($responseCode->transaction->status == 'Approved') ? number_format($responseCode->transaction->total_credited_to_merchant, 2) : 0.00;
                $responseCode->transaction->chargesPaid = ($responseCode->transaction->status == 'Approved') ? number_format($responseCode->transaction->charges_paid_by_merchant, 2) : 0.00;
                $responseCode->transaction->extraConfiguredCharges = ($responseCode->transaction->status == 'Approved') ? number_format($responseCode->transaction->extra_charges_by_merchant, 2) : 0.00;
                $responseCode->transaction->fundsMaturity = $responseCode->transaction->fund_maturity;
                $responseCode->buyerDetails->name = (!empty($responseCode->transaction->name)) ? $responseCode->transaction->name : '';
                $responseCode->buyerDetails->email = $responseCode->transaction->email;
                $responseCode->buyerDetails->phone = (!empty($responseCode->transaction->buyer_phone)) ? $responseCode->transaction->buyer_phone : '';
                $responseCode->buyerDetails->maskedPan = $maskedPan;
                $responseCode->buyerDetails->cardType = $cardType;
                $responseCode->apiProcessTime = $responseCode->transaction->process_duration;
                $responseCode->transaction->responseCode = $responseCode->transaction->response_code;
                $responseCode->transaction->responseDescription = $responseCode->transaction->response_message;
                //unset unused transaction objects
                unset ($responseCode->transaction->total_amount);
                unset ($responseCode->transaction->merchant_ref);
                unset ($responseCode->transaction->memo);
                unset ($responseCode->transaction->method);
                unset ($responseCode->transaction->referrer);
                unset ($responseCode->transaction->total_credited_to_merchant);
                unset ($responseCode->transaction->charges_paid_by_merchant);
                unset ($responseCode->transaction->email);
                unset ($responseCode->transaction->buyer_phone);
                unset ($responseCode->transaction->masked_pan);
                unset ($responseCode->transaction->merchant_id);
                unset ($responseCode->transaction->process_duration);
                unset ($responseCode->transaction->fund_maturity);
                unset ($responseCode->transaction->extra_charges_by_merchant);
                unset ($responseCode->transaction->total_paid_by_buyer);
                unset ($responseCode->transaction->date);
                unset ($responseCode->transaction->transaction_id);
                unset ($responseCode->transaction->cur);
                unset ($responseCode->transaction->response_code);
                unset ($responseCode->transaction->response_message);
                unset ($responseCode->transaction->name);
                if (empty($responseCode->buyerDetails->name)) unset ($responseCode->buyerDetails->name);
            }

            unset ($responseCode->message);
            unset ($responseCode->hash);
            unset ($responseCode->salt);
            unset ($responseCode->username);
            unset ($responseCode->merchant_ref);
            unset ($responseCode->redirect_url);
            $array =  (array) $responseCode;
            ksort($array);
            $responseCode = (object) $array;
            return $responseCode;
        }
    }
}