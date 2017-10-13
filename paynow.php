<?php
/**
 * Paynow Zimbabwe whmcs gateway module
 * Author: Tererai Mugova
 * website:www.webstudio.co.zw
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Define module related meta data.
 *
 * Values returned here are used to determine module related capabilities and
 * settings.
 *
 * @see http://docs.whmcs.com/Gateway_Module_Meta_Data_Parameters
 *
 * @return array
 */
function paynow_MetaData()
{
    return array(
        'DisplayName' => 'Pay Now ',
        'APIVersion' => '1.1', // Use API Version 1.1
        'DisableLocalCredtCardInput' => true,
        'TokenisedStorage' => false,
    );
}

/**
 * Define gateway configuration options.
 *
 * The fields you define here determine the configuration options that are
 * presented to administrator users when activating and configuring your
 * payment gateway module for use.
 *
 * Supported field types include:
 * * text
 * * password
 * * yesno
 * * dropdown
 * * radio
 * * textarea
 *
 * Examples of each field type and their possible configuration parameters are
 * provided in the sample function below.
 *
 * @return array
 */
function paynow_config()
{
    return array(
        // the friendly display name for a payment gateway should be
        // defined here for backwards compatibility
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Paynow',
        ),
        // a text field type allows for single line text input
        'accountID' => array(
            'FriendlyName' => 'Account ID',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Enter your account ID here',
        ),
        // a password field type allows for masked text input
        'secretKey' => array(
            'FriendlyName' => 'Secret Key',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Enter secret key here',
        ),
    );
}

/**
 * Payment link.
 *
 * Required by third party payment gateway modules only.
 *
 * Defines the HTML output displayed on an invoice. Typically consists of an
 * HTML form that will take the user to the payment gateway endpoint.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see http://docs.whmcs.com/Payment_Gateway_Module_Parameters
 *
 * @return string
 */
function paynow_link($params)
{
    // Gateway Configuration Parameters
    $accountId = $params['accountID'];
    $secretKey = $params['secretKey'];
    // Invoice Parameters
    $invoiceId = $params['invoiceid'];
    $description = $params["description"];
    $amount = $params['amount'];
    // Client Parameters
    $email = $params['clientdetails']['email'];
    // System Parameters
    $systemUrl = $params['systemurl'];
    $returnUrl = $params['returnurl'];
    $langPayNow = $params['langpaynow'];
    $moduleDisplayName = $params['name'];
    $moduleName = $params['paymentmethod'];
    //  return $hash;
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'pay') {
        $values = array(
            "id" => $accountId,
            "reference" => $invoiceId,
            "amount" => $amount,
            "returnurl" => $systemUrl . '/modules/gateways/callback/' . $moduleName . '.php',
            "resulturl" => $systemUrl . '/modules/gateways/callback/' . $moduleName . '.php',
            "status" => "Message");
        //generate hash
        $string = "";
        foreach ($values as $key => $value) {
            $string .= $value;
        }

        $integrationkey = $secretKey;
        $string .= $integrationkey;
        $hash = hash("sha512", $string);
        $values['hash'] = strtoupper($hash);
        $ch = curl_init();
        $url = "https://www.paynow.co.zw/Interface/InitiateTransaction";
        // 2. set the options, including the url
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $values);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        // 3. execute and fetch the resulting HTML output
        $output = curl_exec($ch);
        $parts = explode("&", $output);
        $result = array();
        foreach ($parts as $i => $value) {
            $bits = explode("=", $value, 2);
            $result[$bits[0]] = urldecode($bits[1]);
        }
        curl_close($ch);
        //print_r($result);
        if ($result['status'] == 'Ok') {
            echo '<div class="alert alert-success">Redirecting you to your payment gateway<br/><img src="images/loading.gif" /> </div>';
            echo '<meta http-equiv="refresh" content="0;URL=' . $result['browserurl'] . '" />  ';
        }
    }
    $htmlOutput = '<form method="post" action="' . $_SERVER['PHP_SELF'] . '?id=' . $invoiceId . '&action=pay">';
    /*foreach ($values as $k => $v) {
        $htmlOutput .= '<input type="hidden" name="' . $k . '" value="' . $v . '" />';
    }*/
    $htmlOutput .= '<input type="submit" value="Pay online with Paynow" />';
    $htmlOutput .= '</form>';
    return $htmlOutput;
}
