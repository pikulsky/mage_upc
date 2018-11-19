<?php

class Bavers_UpcPayment_PaymentController extends Mage_Core_Controller_Front_Action
{
	/**
	 * Redirect to UPC
	 * TODO rename action to index?
	 */
	public function redirectAction()
	{
		$session = Mage::getSingleton('checkout/session');

		// save QuoteId in session
		$session->setUpcQuoteId($session->getQuoteId());

		$order = Mage::getModel('sales/order');
		$order->loadByIncrementId($session->getLastRealOrderId());
		if (!$order->getId()) {
			Mage::throwException('No order for processing found');
		}

		$order->setState(
			Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
			true,
			Mage::helper('upcpayment')->__('The customer was redirected to UPC.'),
			false
		);
		$order->save();

		$this->getResponse()->setBody($this->getLayout()->createBlock('upcpayment/redirect')->toHtml());
		$session->unsQuoteId();
		$session->unsRedirectUrl();
	}

	/**
	 * When a customer cancel payment from UPC.
	 */
	public function failureAction()
	{
		$paymentMethod = Mage::getModel('upcpayment/paymentmethod');
		$paymentMethod->processFailurePayment();

		// Выводим пользователю ошибку
		$session = Mage::getSingleton('checkout/session');
		// TODO 
		$session->addError(Mage::helper('upcpayment')->__('Payment failed. Please try again later.'));

		// order was cancelled, so redirect to cart
		// so customer will be able to create the order again
		$this->_redirect('checkout/cart');
	}

	/**
	 * Customer return processing
	 */
	public function successAction()
	{
		try {
			$paymentMethod = Mage::getModel('upcpayment/paymentmethod');
			$paymentMethod->processSuccessPayment();
			$this->_redirect('checkout/onepage/success');
			return;
		}
		catch (Mage_Core_Exception $e) {
			$session = Mage::getSingleton('checkout/session');
			$session->addError($e->getMessage());
		}
		catch (Exception $e) {
			//$this->_debug('UPC error: ' . $e->getMessage());
			Mage::logException($e);
		}
		$this->_redirect('checkout/cart');
	}

	/**
	 * Background notifications
	 */
	public function notifyAction()
	{
		$paymentMethod = Mage::getModel('upcpayment/paymentmethod');
		$paymentMethod->processNotifyCallback();
	}
}
