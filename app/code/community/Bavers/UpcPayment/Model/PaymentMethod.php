<?php

$upcLibraryPath = Mage::getModuleDir('', 'Bavers_UpcPayment').DS.'lib'.DS.'Upc.php';
require_once ($upcLibraryPath);

/**
* UPC Payment module adapter
*/
class Bavers_UpcPayment_Model_PaymentMethod extends Mage_Payment_Model_Method_Abstract
{
	/**
	 * unique internal payment method identifier
	 *
	 * @var string [a-z0-9_]
	 */
	protected $_code = 'upcpayment';

	protected $_formBlockType = 'upcpayment/message';

	/**
	 * Flags that determine functionality availability
	 * of this module to be used by frontend and backend.
	 *
	 * @see all flags and their defaults in Mage_Payment_Model_Method_Abstract
	 *
	 * It is possible to have a custom dynamic logic by overloading
	 * public function can* for each flag respectively
	 */

	/**
	 * Is this payment method a gateway (online auth/charge) ?
	 */
	protected $_isGateway				= true;

	/**
	 * Can authorize online?
	 * TODO why not true?
	 */
	protected $_canAuthorize			= false;

	/**
	 * Can capture funds online?
	 */
	protected $_canCapture				= false;

	/**
	 * Can capture partial amounts online?
	 */
	protected $_canCapturePartial		= false;

	/**
	 * Can refund online?
	 */
	protected $_canRefund				= false;

	/**
	 * Can void transactions online?
	 */
	protected $_canVoid					= false;

	/**
	 * Can use this payment method in administration panel?
	 * http://tweetorials.tumblr.com/post/10801322037/magento-payment-model-wrapup
	 * If your payment method redirects the customer
	 * set $canUseInternal = false
	 * to disable the payment method in the admin.
	 * So, admin can't pay, since only customer can pay by this payment method.
	 */
	protected $_canUseInternal          = false;

	/**
	 * Can show this payment method as an option on checkout payment page?
	 */
	protected $_canUseCheckout          = true;

	/**
	 * Is this payment method suitable for multi-shipping checkout?
	 */
	protected $_canUseForMultishipping  = false;

	/**
	 * Can save credit card information for future processing?
	 */
	protected $_canSaveCc               = false;


	// TODO consider to remove (or set it false)
	protected $_isInitializeNeeded      = true;

	/**
	 * Do not remove it
	 * @param string $paymentAction
	 * @param Varien_Object $stateObject
	 */
	public function initialize($paymentAction, $stateObject)
	{
	}

	private function _create_upc_module()
	{
		$uploads_dir = Mage::getBaseDir('var').DS.'uploads';
		$private_key = $this->getConfigData('private_key');
		$public_key = $this->getConfigData('public_key');

		$mode = $this->getConfigData('test') ? 'test' : 'production';
		$mode = 'local'; // TODO remove this

		$upc_config = array(													// config settings for Upc module
			// logging
			'log_class_method'			=> array('Mage', 'Log'),				// log using Magento logging engine
			'log_categories'			=> array('all'),						// log all messages

			'merchant_private_key_path'	=> $uploads_dir.DS.$private_key,		// path to private key
			'upc_public_key_path'		=> $uploads_dir.DS.$public_key,			// path to public key

			'merchant_id'				=> $this->getConfigData('merchant_id'),	// merchant id
			'terminal_id'				=> $this->getConfigData('terminal_id'),	// terminal id
			'mode'						=> $mode,
		);
		$upc = new Upc($upc_config);										// create Upc object
		return $upc;
	}

	/**
	 * Start point of the UPC payment module
	 * Returns Order place redirect URL
	 *
	 * @return string
	 */
	public function getOrderPlaceRedirectUrl()
	{
		return Mage::getUrl('upcpayment/payment/redirect', array('_secure' => true));
	}

	public function getFormHtml()
	{
		$session = Mage::getSingleton('checkout/session');
		$order = Mage::getModel('sales/order');

		$order->loadByIncrementId($session->getLastRealOrderId());

		if (!$order->getId()) {
			return 'Order not found.';
		}

		$orderId = $order->getIncrementId();

		// get order total amount
		$total_amount = $order->getGrandTotal();
		// get order currency
		$currency_code = $order->getOrderCurrencyCode();

		$upc_currency_code = 'UAH';
		// convert amount from order currency to UPC currency
		if ($currency_code != $upc_currency_code) {
			$orderCurrency = Mage::getSingleton('directory/currency')->load($currency_code);
			$total_amount = $orderCurrency->convert($total_amount, $upc_currency_code);
		}
		// amount should be in cents
		$total_amount = $total_amount * 100;

		$order_data = array(
			'total_amount'	=> $total_amount,
			'currency_id'	=> self::_get_currency_id($upc_currency_code),
			'locale'		=> $this->getConfigData('interface_language'),
			'order_id'		=> $orderId,
			'description'	=> Mage::helper('upcpayment')->__('Payment for order #%s',$orderId),
		);

		// "upcform" id is used by JavaScript code
		// to submit form as soon as it is loaded
		$render_data = array(
			'form_attr'	=> 'id="upcform" name="upcform"',
		);

		$upc = $this->_create_upc_module();

		$result = $upc->process_request($order_data, $render_data);

		return $result;
	}

	public function processSuccessPayment()
	{
		// create UPC object
		$upc = $this->_create_upc_module();

		$data = $upc->process_response();

		$order_id = self::_get_array_value($data, 'order_id');

		$session = Mage::getSingleton('checkout/session');

		$quote_id = $session->getUpcQuoteId();

		$session->setQuoteId($quote_id);
		$session->setLastSuccessQuoteId($quote_id);

		// deactivate customer's cart
		$session->getQuote()->setIsActive(false)->save();

		// send email to customer
		$order = Mage::getModel('sales/order');

		$order->load($session->getLastOrderId());
		if ($order->getId()) {

			// set "processing" state and default status for "processing" state
			// note: status could be taken from config: $this->getConfigData('processing_order_status')
			//
			$order->setState(
				Mage_Sales_Model_Order::STATE_PROCESSING,	// "processing" state
				true,										// default status for "procesding" state
				Mage::helper('upcpayment')->__('Payment was successful. Approval code: %s', $data['approval_code']),
				true										// send email to customer about changed status
			);

			$order->sendNewOrderEmail();
			$order->save();
		}
	}

	public function processFailurePayment()
	{
		// create UPC object
		$upc = $this->_create_upc_module();

		// process data from UPC
		$data = $upc->process_response();

		$is_signature_valid = self::_get_array_value($data, 'is_signature_valid', false);

		$session = Mage::getSingleton('checkout/session');
		$quote = Mage::getModel('sales/quote');
		$order = Mage::getModel('sales/order');
		
		$quote_id = $session->getUpcQuoteId();
		$session->setQuoteId($quote_id);
		
		$last_real_order_id = $session->getLastRealOrderId();
		
		if ($last_real_order_id) {
			$order->loadByIncrementId($last_real_order_id);
			if ($order->getId()) {
				// check signature
				if (!$is_signature_valid && false) { // TODO

					$order->addStatusHistoryComment(
						Mage::helper('upcpayment')->__('Hash did not match, check UPC certificate'));
				}

				$order->setState(
					Mage_Sales_Model_Order::STATE_CANCELED,
					true,
					Mage::helper('upcpayment')->__('Payment failed: %s. Approval code: %s', $data['error_text'], $data['approval_code']),
					true
				);

				// cancel order
				$order->cancel()->save();
			}
		}

		// since the order was cancelled, so the cart should be active
		// to allow customer create a new order again
		$quote->load($quote_id);
		if ($quote->getId()) {
			// activate customer's cart
			$quote->setIsActive(true);
			$quote->save();
		}
	}

	public function processNotifyCallback()
	{
		// create UPC object
		$upc = $this->_create_upc_module();

		// process POST data from UPC
		$data = $upc->get_callback_data();

		$is_signature_valid = self::_get_array_value($data, 'is_signature_valid', false);

		// If signature is not valid, then show "failure" page.
		if (!$is_signature_valid && false) { // TODO remove false
			$data['forward_url'] = Mage::helper('upcpayment')->getFailureUrl();

			$error_text = Mage::helper('upcpayment')->__('Hash did not match, check UPC certificate');

			// notify UPC about reject and exit
			$upc->reject_payment($data, $error_text);
		}

		$transaction_code = self::_get_array_value($data, 'transaction_code', '');
		if ('000' === $transaction_code) {
			$data['forward_url'] = Mage::helper('upcpayment')->getSuccessUrl();

			// notify UPC about approve and exit
			$upc->approve_payment($data);
		}
		else {
			$data['forward_url'] = Mage::helper('upcpayment')->getFailureUrl();			

			$error_text = self::_get_array_value($data, 'error_text', $transaction_code);

			// notify UPC about reject and exit
			$upc->reject_payment($data, $error_text);
		}
	}

	/**
	 * Converts currency name (USD, UAH, etc.) to ISO numeric code
	 *
	 * @param string $currency currency name
	 * @return int currency id (ISO numeric code)
	 */
	private static function _get_currency_id($currency)
	{
		$currency_ids = array(
			'USD'	=> 840,
			'EUR'	=> 978,
			'RUB'	=> 643,
			'UAH'	=> 980,
		);

		$currency_id = self::_get_array_value($currency_ids, $currency, '');

// TODO
//		if (empty($currency_id)) {
//			self::log_debug('[_get_currency_id] Unknown currency: "'.$currency .'"');
//		}
		return $currency_id;
	}

	//----------------------------------------------------------
	//--
	//-- HELPERS
	//--
	//----------------------------------------------------------

	private static function _get_array_value($data, $name, $default)
	{
		return isset($data[$name]) ? $data[$name] : $default;
	}
	
}
