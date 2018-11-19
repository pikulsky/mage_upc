<?php

class Bavers_UpcPayment_Helper_Data extends Mage_Core_Helper_Abstract
{
	public function getSuccessUrl()
	{
		return Mage::getUrl('upcpayment/payment/success', array('_secure' => false));
	}

	public function getNotifyUrl()
	{
		return Mage::getUrl('upcpayment/payment/notify', array('_secure' => false));
	}

	public function getFailureUrl()
	{
		return Mage::getUrl('upcpayment/payment/failure', array('_secure' => false));
	}
}
