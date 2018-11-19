<?php
/**
 * This block shows short description of UPC payment option
 * in the list of available payment options.
 * 
 * This block is displayed under radio with UPC payment option
 * in the list of available payment options.
 */
class Bavers_UpcPayment_Block_Message extends Mage_Payment_Block_Form
{
	/**
	 * True to always display the message,
	 * False to hide the message when another payment option is selected by user
	 * 
	 * @access public in order to be access in message.phtml file
	 * @var boolean 
	 */
	public $isAlwaysShowMessage = true;

	/**
	 * Set template with message
	 */
	protected function _construct()
	{
		$this->setTemplate('upcpayment/message.phtml');
		parent::_construct();
	}
}
