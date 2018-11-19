<?php
/**
 * This block displays form for submitting order to UPC.
 * Once form html is loaded, it redirects to UPC server using JavaScript.
 */
class Bavers_UpcPayment_Block_Redirect extends Mage_Core_Block_Template
{
	/**
	 * Set template with message
	 */
	protected function _construct()
	{
		$this->setTemplate('upcpayment/redirect.phtml');
		parent::_construct();
	}

	/**
	 * Returns form html to submit order to UPC
	 *
	 * @return string form html
	 */
	public function getFormHtml()
	{
		// load module model
		$paymentMethod = Mage::getModel('upcpayment/paymentmethod');

		// get form html
		$formHtml = $paymentMethod->getFormHtml();

		return $formHtml;
	}
}
