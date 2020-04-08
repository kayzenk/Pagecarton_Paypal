<?php

/**
 * PageCarton
 *
 * LICENSE
 *
 * @category   PageCarton
 * @package    Paypal_Pay
 * @copyright  Copyright (c) 2020 PageCarton (http://www.pagecarton.org)
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @version    $Id: Pay.php Monday 6th of April 2020 05:24PM kayzenk@gmail.com $
 */

/**
 * @see PageCarton_Widget
 */

class Paypal_Pay extends Paypal_Paypal
{


	/**
	 * Form Action
	 *
	 * @var string
	 */
	protected static $_formAction = '';

	/**
	 * The method does the whole Class Process
	 *
	 */
	protected function init()
	{
		//var_export( $this->getParameter() );

		self::$_apiName = $this->getParameter( 'checkoutoption_name' ) ? : array_pop( explode( '_', get_class( $this ) ) );
		if( ! $cart = self::getStorage()->retrieve() ){ return; }
		$values = $cart['cart'];

		$parameters = static::getDefaultParameters();
		//		$parameters['unotify'] = $parameters['notify_url'];
		//		$parameters['ureturn'] = $parameters['success_url'];
		//		$parameters['ucancel'] = $parameters['fail_url'];

		var_export( $parameters );
		$parameters['email'] = Ayoola_Form::getGlobalValue( 'email' ) ? : ( Ayoola_Form::getGlobalValue( 'email_address' ) ? : Ayoola_Application::getUserInfo( 'email' ) );
		$parameters['reference'] = $this->getParameter( 'reference' ) ? : $parameters['order_number'];
		$parameters['client_id'] = Paypal_Settings::retrieve( 'client_id' ) ? : "";
		$parameters['currency'] =  Paypal_Settings::retrieve( 'currency' ) ? : "USD";

		var_export( $parameters );

		$counter = 1;
		$parameters['price'] = 0.00;
		foreach( $values as $name => $value )
		{
			if( ! isset( $value['price'] ) )
			{
				$value = array_merge( self::getPriceInfo( $value['price_id'] ), $value );
			}
			@@$parameters['prod'] .= ' ' . $value['multiple'] . ' x ' . $value['subscription_label'];
			@$parameters['price'] += floatval( $value['price'] * $value['multiple'] );
			var_export( $value );
			$counter++;
		}
		$parameters['amount'] = ( $this->getParameter( 'amount' ) ? : $parameters['price'] ) ;
		// var_export( $parameters );

		$this->setViewContent( '
								<div id="paypal-button-container"></div>
								<script src="https://www.paypal.com/sdk/js?client-id='.$parameters['client_id'].'&currency='.$parameters['currency'].'"></script>
								<script>
								  paypal.Buttons({
								    createOrder: function(data, actions) {
								      // This function sets up the details of the transaction, including the amount and line item details.
								      return actions.order.create({
								        purchase_units: [{
								          amount: {
								            value: "'.$parameters['amount'].'",
								          }
								        }]
								      });
								    },
								    onApprove: function(data, actions) {
								      // This function captures the funds from the transaction.
											console.log( data );

											location.href = "' . $parameters['success_url'] . '?ref=" + data.orderID;
											alert( data.orderID );
								      return actions.order.capture().then(function(details) {

								      });
								    }
								  }).render("#paypal-button-container");
								  //This function displays Smart Payment Buttons on your web page.
								</script>
		' );
	}

// END OF CLASS
}
