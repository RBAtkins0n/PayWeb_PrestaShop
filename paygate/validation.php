<?php
/*
 * Copyright (c) 2018 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 *
 * Released under the GNU General Public License
 */

include dirname( __FILE__ ) . '/../../config/config.inc.php';
include dirname( __FILE__ ) . '/paygate.php';

ini_set( 'display_errors', 0 );

if ( isset( $_GET['paygate'] ) ) {

    $error_msg = '';
    $errors    = false;
    if ( isset( $ERROR ) ) {
        $errors    = true;
        $error_msg = $ERROR;
    }

    $PAY_METHOD_DETAIL = '';
    $notify_data       = array();

    $paygate        = new Paygate();
    $post_data      = '';
    $checkSumParams = '';

    //Notify PayGate that information has been received
    echo 'OK';
    $paygate->logData( "=========Notify Response: " . date( 'Y-m-d H:i:s' ) . "============\n\n" );
    if ( !$errors ) {

        foreach ( $_POST as $key => $val ) {
            $post_data .= $key . '=' . $val . "\n";
            $notify_data[$key] = stripslashes( $val );

            if ( $key == 'PAYGATE_ID' ) {
                $checkSumParams .= Configuration::get( 'PAYGATE_ID' );
            }
            if ( $key != 'CHECKSUM' && $key != 'PAYGATE_ID' ) {
                $checkSumParams .= $val;
            }
            if ( sizeof( $notify_data ) == 0 ) {
                $error_msg = 'Notify post response is empty';
                $errors    = true;
            }
        }
        $checkSumParams .= Configuration::get( 'ENCRYPTION_KEY' );
    }
    $paygate->logData( $post_data );
    $paygate->logData( "\n" );

    if ( empty( Context::getContext()->link ) ) {
        Context::getContext()->link = new Link();
    }

    // Verify security signature
    if ( !$errors ) {
        $checkSumParams = md5( $checkSumParams );
        if ( $checkSumParams != $notify_data['CHECKSUM'] ) {
            !$errors   = true;
            $error_msg = 'Invalid checksum, checksum: ' . $checkSumParams;
        }
    }

    // Check status and update order
    if ( !$errors ) {
        $transaction_id = $notify_data['TRANSACTION_ID'];
        $method_name    = $paygate->displayName;

        if ( $notify_data['PAY_METHOD_DETAIL'] != '' ) {
            $method_name = $notify_data['PAY_METHOD_DETAIL'] . ' via PayGate';
        }

        switch ( $notify_data['TRANSACTION_STATUS'] ) {
            case '1':
                // Update the purchase status
                $paygate->validateOrder( (int) $notify_data['USER1'], _PS_OS_PAYMENT_, ( (int) $notify_data['AMOUNT'] ) / 100,
                    $method_name, null, array( 'transaction_id' => $transaction_id ), null, false, $notify_data['USER2'] );
                $paygate->logData( "Done updating order status\n\n" );
                break;

            case '2':
                // Update the purchase status - uncomment to create order on declined transaction
                /*$paygate->validateOrder((int)$notify_data['USER1'], _PS_OS_ERROR_, ((int)$notify_data['AMOUNT']) / 100,
                $method_name, NULL, array('transaction_id' => $transaction_id), NULL, false, $notify_data['USER2']);
                $paygate->logData("Done updating order status\n\n");*/
                break;

            case '4':
                // Update the purchase status - uncomment to create order on cancelled transaction
                /*$paygate->validateOrder((int)$notify_data['USER1'], _PS_OS_CANCELED_, ((int)$notify_data['AMOUNT']) / 100,
                $method_name, NULL, array('transaction_id' => $transaction_id), NULL, false, $notify_data['USER2']);
                $paygate->logData("Done updating order status\n\n");*/
                break;

            default:
                // If unknown status, do nothing (safest course of action)
                break;
        }
    }

    if ( $errors ) {
        $paygate->logData( $error_msg . "\n" );
    }
    exit();
}

if ( isset( $_GET['returnurl'] ) ) {
    $status = isset( $_POST['TRANSACTION_STATUS'] ) ? $_POST['TRANSACTION_STATUS'] : "";
    switch ( $status ) {
        case '1':
            Tools::redirect( rawurldecode( $_GET['returnurl'] ) );
            break;
        case '2':
            // Declined
            Tools::redirect( Context::getContext()->link->getModuleLink( 'paygate', 'error', array( 'status' => 2 ) ) );
            break;
        case '4':
            // Cancelled
            Tools::redirect( Context::getContext()->link->getModuleLink( 'paygate', 'error', array( 'status' => 4 ) ) );
            break;
        default:
            Tools::redirect( rawurldecode( $_GET['returnurl'] ) );
            break;
    }
}
