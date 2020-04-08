<?php

/**
 * PageCarton
 *
 * LICENSE
 *
 * @category   PageCarton
 * @package    Paypal_Verify
 * @copyright  Copyright (c) 2020 PageCarton (http://www.pagecarton.org)
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @version    $Id: Verify.php Wednesday 8th of April 2020 06:57PM kayzenk@gmail.com $
 */

/**
 * @see PageCarton_Widget
 */

class Paypal_Verify extends Paypal_Pay
{
	
    /**
     * Access level for player. Defaults to everyone
     *
     * @var boolean
     */
	protected static $_accessLevel = array( 0 );
	
    /**
     * 
     * 
     * @var string 
     */
	protected static $_objectTitle = 'PayPal Payment Verification'; 

    /**
     * Performs the whole widget running process
     * 
     */
	public function init()
    {    
		try
		{ 
            $orderNumber  = addslashes( $_GET['order_number'] ) ? : null;
            if ( is_null( $orderNumber) ) { return; }
            
            $table = new Application_Subscription_Checkout_Order();
            if( ! $orderInfo = $table->selectOne( null, array( 'order_id' => $orderNumber ) ) )
            {
                return false;
            }
            //var_export( $orderInfo );
            if( ! is_array( $orderInfo['order'] ) )
            {
                //	compatibility
                $orderInfo['order'] = unserialize( $orderInfo['order'] );
            }
            //$orderInfo['order'] = unserialize( $orderInfo['order'] );
            $orderInfo['total'] = 0;

            foreach( $orderInfo['order']['cart'] as $name => $value )
            {
                if( ! isset( $value['price'] ) )
                {
                    $value = array_merge( self::getPriceInfo( $value['price_id'] ), $value );
                }
                $orderInfo['total'] += $value['price'] * $value['multiple'];
                //$counter++;
            }

            $secretKey = Paypal_Settings::retrieve( 'secret_key' );

            // var_Export( $secretKey );

            $result = array();

            //The parameter after verify/ is the transaction reference to be verified
            $url = 'https://api.sandbox.paypal.com/v2/checkout/orders/' . $_REQUEST['ref'];
            echo $url;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt(
                $ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']
            );
            curl_setopt(
                $ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $secretKey ]
            );
            $request = curl_exec($ch);
            curl_close($ch);

            if ( $request ) {
                $result = json_decode($request, true);
            }

            //Use the $result array
            var_export( $request );
        
            //	confirm status
            /* var_export( ! empty( $result['Error'] ) );
                var_export( @$result['StatusCode'] !== '00' );
                var_export( @$result['Amount'] != @$orderInfo['total'] );
                var_export( @$result['Amount'] );
                var_export( @$orderInfo['total'] );
                var_export( @$result['AmountIntegrityCode'] !== '00' );
            */		
            if( empty( $result['status'] ) )
            {
                //	Payment was not successful.
                $orderInfo['order_status'] = 'Payment Failed';
            }
            else
            {
                $orderInfo['order_status'] = 'Payment Successful';
            }

            //var_export( $orderInfo );
            $orderInfo['order_random_code'] = $_REQUEST['ref'];
            $orderInfo['gateway_response'] = $result;

            //var_export( $orderNumber );

            self::changeStatus( $orderInfo );
            //$table->update( $orderInfo, array( 'order_id' => $orderNumber ) );

            //$response = new SimpleXMLElement(file_get_contents($url));

            //var_export( $orderInfo );
            //var_export( $result );

            //	Code to change check status goes heres
            //	if( )
            return $orderInfo;
		}  
		catch( Exception $e )
        { 
            //  Alert! Clear the all other content and display whats below.
            //  $this->setViewContent( '<p class="badnews">' . $e->getMessage() . '</p>' ); 
            // $this->setViewContent( '<p class="badnews">Theres an error in the code</p>' ); 
            // return false; 
        }
	}
	// END OF CLASS
}
