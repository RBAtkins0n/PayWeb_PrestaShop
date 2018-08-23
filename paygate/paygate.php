<?php
/*
 * Copyright (c) 2018 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 *
 * Released under the GNU General Public License
 */

include dirname( __FILE__ ) . '/CountriesArray.php';

if ( !defined( '_PS_VERSION_' ) ) {
    exit;
}

class Paygate extends PaymentModule
{
    const LEFT_COLUMN  = 0;
    const RIGHT_COLUMN = 1;
    const FOOTER       = 2;
    const DISABLE      = -1;

    public function __construct()
    {
        $this->name            = 'paygate';
        $this->tab             = 'payments_gateways';
        $this->version         = '1.0.1';
        $this->currencies      = true;
        $this->currencies_mode = 'radio';

        parent::__construct();

        $this->author = 'PayGate';
        $this->page   = basename( __FILE__, '.php' );

        $this->displayName      = $this->l( 'PayGate' );
        $this->description      = $this->l( 'Accept payments via PayGate.' );
        $this->confirmUninstall = $this->l( 'Are you sure you want to delete your details ?' );

        /* For 1.4.3 and less compatibility */
        $updateConfig = array(
            'PS_OS_CHEQUE' => 1, 'PS_OS_PAYMENT' => 2, 'PS_OS_PREPARATION' => 3, 'PS_OS_SHIPPING' => 4, 'PS_OS_DELIVERED' => 5, 'PS_OS_CANCELED'    => 6,
            'PS_OS_REFUND' => 7, 'PS_OS_ERROR'   => 8, 'PS_OS_OUTOFSTOCK'  => 9, 'PS_OS_BANKWIRE' => 10, 'PS_OS_PAYPAL'   => 11, 'PS_OS_WS_PAYMENT' => 12,
        );
        foreach ( $updateConfig as $u => $v ) {
            if ( !Configuration::get( $u ) || (int) Configuration::get( $u ) < 1 ) {
                if ( defined( '_' . $u . '_' ) && (int) constant( '_' . $u . '_' ) > 0 ) {
                    Configuration::updateValue( $u, constant( '_' . $u . '_' ) );
                } else {
                    Configuration::updateValue( $u, $v );
                }

            }
        }

    }

    public function install()
    {
        unlink( dirname( __FILE__ ) . '/../../cache/class_index.php' );
        if ( !parent::install()
            or !$this->registerHook( 'payment' )
            or !$this->registerHook( 'paymentReturn' )
            or !Configuration::updateValue( 'PAYGATE_ID', '' )
            or !Configuration::updateValue( 'ENCRYPTION_KEY', '' )
            or !Configuration::updateValue( 'PAYGATE_LOGS', '1' )
        ) {
            return false;
        }
        return true;
    }

    public function uninstall()
    {
        unlink( dirname( __FILE__ ) . '/../../cache/class_index.php' );
        return ( parent::uninstall()
            and Configuration::deleteByName( 'PAYGATE_ID' )
            and Configuration::deleteByName( 'ENCRYPTION_KEY' )
            and Configuration::deleteByName( 'PAYGATE_LOGS' )
        );
    }

    public function logData( $post_data )
    {
        if ( Tools::getValue( 'paygate_logs', Configuration::get( 'PAYGATE_LOGS' ) ) == 1 ) {
            $logFile = fopen( __DIR__ . '/paygate_prestashop_logs.txt', 'a+' ) or die( 'fopen failed' );
            fwrite( $logFile, $post_data ) or die( 'fwrite failed' );
            fclose( $logFile );
        }
    }

    public function getContent()
    {
        global $cookie;
        $errors = array();
        $html
        = '<div style="width:550px">
        <p style="text-align:center;"><a href="https://www.paygate.co.za" target="_blank"><img src="' . __PS_BASE_URI__ . 'modules/paygate/paylogo.png" alt="PayGate" boreder="0" /></a></p><br />';

        /* Update configuration variables */
        if ( Tools::isSubmit( 'submitPaygate' ) ) {
            Configuration::updateValue( 'PAYGATE_MODE', 'live' );

            if ( preg_match( '/[0-9]/', Tools::getValue( 'paygate_id' ) ) ) {
                Configuration::updateValue( 'PAYGATE_ID', Tools::getValue( 'paygate_id' ) );
            } else {
                $errors[] = '<div class="warning warn"><h3>' . $this->l( 'Merchant ID seems to be wrong' ) . '</h3></div>';
            }

            Configuration::updateValue( 'ENCRYPTION_KEY', Tools::getValue( 'encryption_key' ) );

            foreach ( array( 'displayLeftColumn', 'displayRightColumn', 'displayFooter' ) as $hookName ) {
                if ( $this->isRegisteredInHook( $hookName ) ) {
                    $this->unregisterHook( $hookName );
                }
            }

            if ( Tools::getValue( 'logo_position' ) == self::LEFT_COLUMN ) {
                $this->registerHook( 'displayLeftColumn' );
            } else if ( Tools::getValue( 'logo_position' ) == self::RIGHT_COLUMN ) {
                $this->registerHook( 'displayRightColumn' );
            } else if ( Tools::getValue( 'logo_position' ) == self::FOOTER ) {
                $this->registerHook( 'displayFooter' );
            }

            if ( method_exists( 'Tools', 'clearSmartyCache' ) ) {
                Tools::clearSmartyCache();
            }
        }

        /* Display errors */
        if ( sizeof( $errors ) ) {
            $html .= '<ul style="color: red; font-weight: bold; margin-bottom: 30px; width: 506px; background: #FFDFDF; border: 1px dashed #BBB; padding: 10px;">';
            foreach ( $errors as $error ) {
                $html .= '<li>' . $error . '</li>';
            }

            $html .= '</ul>';
        }

        $blockPositionList = array(
            self::DISABLE      => $this->l( 'Disable' ),
            self::LEFT_COLUMN  => $this->l( 'Left Column' ),
            self::RIGHT_COLUMN => $this->l( 'Right Column' ),
            self::FOOTER       => $this->l( 'Footer' ),
        );

        if ( Tools::getValue( 'paygate_logs' ) ) {
            Configuration::updateValue( 'PAYGATE_LOGS', 1 );
        } else {
            Configuration::updateValue( 'PAYGATE_LOGS', 0 );
        }

        if ( $this->isRegisteredInHook( 'displayLeftColumn' ) ) {
            $currentLogoBlockPosition = self::LEFT_COLUMN;
        } elseif ( $this->isRegisteredInHook( 'displayRightColumn' ) ) {
            $currentLogoBlockPosition = self::RIGHT_COLUMN;
        } elseif ( $this->isRegisteredInHook( 'displayFooter' ) ) {
            $currentLogoBlockPosition = self::FOOTER;
        } else {
            $currentLogoBlockPosition = -1;
        }

        /* Display settings form */
        $html .= '
	<form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
		<fieldset>
			<label>' . $this->l( 'PayGate ID' ) . '</label>
			<div class="margin-form">
				<input type="text" name="paygate_id" value="' . Tools::getValue( 'paygate_id', Configuration::get( 'PAYGATE_ID' ) ) . '" />
			</div>
			<label>' . $this->l( 'Encryption Key' ) . '</label>
			<div class="margin-form">
				<input type="text" name="encryption_key" value="' . trim( Tools::getValue( 'encryption_key', Configuration::get( 'ENCRYPTION_KEY' ) ) ) . '" />
			</div>
			<label>' . $this->l( 'Debug' ) . '</label>
			<div class="margin-form" style="margin-top:5px">
				<input type="checkbox" name="paygate_logs"' . ( Tools::getValue( 'paygate_logs', Configuration::get( 'PAYGATE_LOGS' ) ) ? ' checked="checked"' : '' ) . ' />
			</div>
			<p>' . $this->l( 'You can log notify responses data from PayGate. The log file for debugging can be found at ' ) . ' ' . __PS_BASE_URI__ . 'modules/paygate/paygate_prestashop_logs.txt. ' . $this->l( 'If activated, be sure to protect it by putting a .htaccess file in the same directory. If not, the file will be readable by everyone.' ) . '</p>
            <div style="float:right;"><input type="submit" name="submitPaygate" class="button" value="' . $this->l( '   Save   ' ) . '" /></div>
            <div class="clear"></div>
        </fieldset>
    </form>
</div>';

        return $html;
    }

    private function _displayLogoBlock( $position )
    {
        return '<div style="text-align:center;"><a href="https://www.paygate.co.za1" target="_blank" title="Secure Payments With PayGate"><img src="' . __PS_BASE_URI__ . 'modules/paygate/paylogo.png" width="150" /></a></div>';
    }

    public function hookDisplayRightColumn( $params )
    {
        return $this->_displayLogoBlock( self::RIGHT_COLUMN );
    }

    public function hookDisplayLeftColumn( $params )
    {
        return $this->_displayLogoBlock( self::LEFT_COLUMN );
    }

    public function hookDisplayFooter( $params )
    {
        $html = '<section id="PAYGATE_footer_link" class="footer-block col-xs-12 col-sm-2">
    <div style="text-align:center;"><a href="https://www.paygate.co.za" target="_blank" title="Secure Payments With PayGate"><img src="' . __PS_BASE_URI__ . 'modules/paygate/paylogo.png"  /></a></div>
</section>';
        return $html;
    }

    public function hookPayment( $params )
    {

        global $cookie, $cart;
        if ( !$this->active ) {
            return;
        }

        $iso_code = $this->context->language->iso_code;

        // Buyer details
        $customer     = new Customer( (int) ( $cart->id_customer ) );
        $user_address = new Address( intval( $params['cart']->id_address_invoice ) );

        //retrieve country code2
        $country       = new Country();
        $country_code2 = $country->getIsoById( $user_address->id_country );
        $countries     = new CountriesArray();

        //retrieve country code3
        $country_code3 = $countries->getCountryDetails( $country_code2 );

        $total    = $cart->getOrderTotal();
        $data     = array();
        $currency = $this->getCurrency( (int) $cart->id_currency );

        if ( $cart->id_currency != $currency->id ) {
            //If paygate currency differs from local currency
            $cart->id_currency   = (int) $currency->id;
            $cookie->id_currency = (int) $cart->id_currency;
            $cart->update();
        }

        $dateTime           = new DateTime();
        $time               = $dateTime->format( 'YmdHis' );
        $cookie->order_time = $time;
        $cookie->cart_id    = $cart->id;
        $paygateID          = filter_var( Configuration::get( 'PAYGATE_ID' ), FILTER_SANITIZE_STRING );
        $reference          = filter_var( $cart->id . $time, FILTER_SANITIZE_STRING );
        $amount             = filter_var( $total * 100, FILTER_SANITIZE_NUMBER_INT );
        $currency           = filter_var( $currency->iso_code, FILTER_SANITIZE_STRING );
        $returnUrlBase      = filter_var( $this->context->link->getPageLink( 'order-confirmation', null, null, 'key=' . $cart->secure_key . '&id_cart=' . (int) ( $cart->id ) . '&id_module=' . (int) ( $this->id ) ), FILTER_SANITIZE_URL );
        $returnUrl          = filter_var( Tools::getHttpHost( true ) . __PS_BASE_URI__ . 'modules/paygate/validation.php?' . 'returnurl=' . rawurlencode( $returnUrlBase ), FILTER_SANITIZE_STRING );
        $transDate          = filter_var( date( 'Y-m-d H:i:s' ), FILTER_SANITIZE_STRING );
        $locale             = filter_var( $iso_code, FILTER_SANITIZE_STRING );
        $country            = filter_var( $country_code3, FILTER_SANITIZE_STRING );
        $email              = filter_var( $customer->email, FILTER_SANITIZE_EMAIL );
        $payMethod          = '';
        $payMethodDetail    = '';
        $notifyUrl          = filter_var( Tools::getHttpHost( true ) . __PS_BASE_URI__ . 'modules/paygate/validation.php?' . 'paygate=paygate', FILTER_SANITIZE_STRING );
        $userField1         = $cart->id;
        $userField2         = $cart->secure_key;
        $userField3         = $this->id;
        $doVault            = '';
        $vaultID            = '';
        $encryption_key     = Configuration::get( 'ENCRYPTION_KEY' );

        $checksum_source = $paygateID . $reference . $amount . $currency . $returnUrl . $transDate;

        if ( $locale ) {
            $checksum_source .= $locale;
        }

        if ( $country ) {
            $checksum_source .= $country;
        }

        if ( $email ) {
            $checksum_source .= $email;
        }

        if ( $payMethod ) {
            $checksum_source .= $payMethod;
        }

        if ( $payMethodDetail ) {
            $checksum_source .= $payMethodDetail;
        }

        if ( $notifyUrl ) {
            $checksum_source .= $notifyUrl;
        }

        if ( $userField1 ) {
            $checksum_source .= $userField1;
        }

        if ( $userField2 ) {
            $checksum_source .= $userField2;
        }

        if ( $userField3 ) {
            $checksum_source .= $userField3;
        }

        if ( $doVault != '' ) {
            $checksum_source .= $doVault;
        }

        if ( $vaultID != '' ) {
            $checksum_source .= $vaultID;
        }

        $checksum_source .= $encryption_key;

        $checksum     = md5( $checksum_source );
        $returnUrl    = urlencode( $returnUrl );
        $initiateData = array(
            'PAYGATE_ID'        => $paygateID,
            'REFERENCE'         => $reference,
            'AMOUNT'            => $amount,
            'CURRENCY'          => $currency,
            'RETURN_URL'        => $returnUrl,
            'TRANSACTION_DATE'  => $transDate,
            'LOCALE'            => $locale,
            'COUNTRY'           => $country,
            'EMAIL'             => $email,
            'PAY_METHOD'        => $payMethod,
            'PAY_METHOD_DETAIL' => $payMethodDetail,
            'NOTIFY_URL'        => $notifyUrl,
            'USER1'             => $userField1,
            'USER2'             => $userField2,
            'USER3'             => $userField3,
            'VAULT'             => $doVault,
            'VAULT_ID'          => $vaultID,
            'CHECKSUM'          => $checksum,
        );

        $fields_string = '';
        //url-ify the data for the POST
        foreach ( $initiateData as $key => $value ) {
            $fields_string .= $key . '=' . $value . '&';
        }
        rtrim( $fields_string, '&' );

        //open connection
        $ch = curl_init();
        //set the url, number of POST vars, POST data
        curl_setopt( $ch, CURLOPT_POST, 1 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
        curl_setopt( $ch, CURLOPT_URL, 'https://secure.paygate.co.za/payweb3/initiate.trans' );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

        curl_setopt( $ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR'] );
        curl_setopt( $ch, CURLOPT_POST, count( $initiateData ) );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $fields_string );

        //execute post
        $result = curl_exec( $ch );

        //close connection
        curl_close( $ch );

        parse_str( $result );

        if ( isset( $ERROR ) ) {
            $data['errors'] = 'Error trying to initiate a transaction, paygate error code: ' . $ERROR . '. Log support ticket to shop owner or try to checkout again';
            $this->context->smarty->assign( 'data', $data );
            return $this->display( __FILE__, 'errors.tpl' );
        }

        $data['CHECKSUM']       = $CHECKSUM;
        $data['PAY_REQUEST_ID'] = $PAY_REQUEST_ID;
        $this->context->smarty->assign( 'data', $data );
        return $this->display( __FILE__, 'paygate.tpl' );
    }

    public function hookPaymentReturn( $params )
    {
        if ( !$this->active ) {
            return;
        }

        $paygateID      = filter_var( Configuration::get( 'PAYGATE_ID' ), FILTER_SANITIZE_STRING );
        $pay_request_id = filter_var( $_POST['PAY_REQUEST_ID'], FILTER_SANITIZE_STRING );
        $reference      = filter_var( $this->context->cookie->cart_id . $this->context->cookie->order_time, FILTER_SANITIZE_STRING );
        $encryption_key = Configuration::get( 'ENCRYPTION_KEY' );
        $checksum       = md5( $paygateID . $pay_request_id . $reference . $encryption_key );

        $queryData = array(
            'PAYGATE_ID'     => $paygateID,
            'PAY_REQUEST_ID' => $pay_request_id,
            'REFERENCE'      => $reference,
            'CHECKSUM'       => $checksum,
        );

        $TRANSACTION_STATUS = null;
        $fields_string      = null;

        foreach ( $queryData as $key => $value ) {
            $fields_string .= $key . '=' . $value . '&';
        }
        rtrim( $fields_string, '&' );

        //open connection
        $ch = curl_init();
        //set the url, number of POST vars, POST data
        curl_setopt( $ch, CURLOPT_POST, 1 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
        curl_setopt( $ch, CURLOPT_URL, 'https://secure.paygate.co.za/payweb3/query.trans' );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

        curl_setopt( $ch, CURLOPT_INTERFACE, $_SERVER['SERVER_ADDR'] );
        curl_setopt( $ch, CURLOPT_POST, count( $queryData ) );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $fields_string );
        unset( $_SESSION['REFERENCE'] );

        //execute post
        $result = curl_exec( $ch );

        //close connection
        curl_close( $ch );

        parse_str( $result );

        if ( !isset( $ERROR ) ) {

            switch ( $TRANSACTION_STATUS ) {
                case '1':
                    $this->context->smarty->assign( 'status', 'has been approved.' );
                    $this->context->smarty->assign( 'sent', 'Your order will be processed shortly.' );

                    break;

                case '2':
                    $this->context->smarty->assign( 'status', 'has been declined.' );
                    break;

                case '4':
                    $this->context->smarty->assign( 'status', 'has been cancelled.' );
                    break;

                default:
                    break;
            }
        }
        return $this->display( __FILE__, 'paygate_success.tpl' );
    }
}
