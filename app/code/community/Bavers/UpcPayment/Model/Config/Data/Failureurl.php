<?php

class Bavers_UpcPayment_Model_Config_Data_Failureurl
	extends Mage_Core_Model_Config_Data
{
	public function getValue()
	{
		return Mage::helper('upcpayment')->getFailureUrl();
	}
}
