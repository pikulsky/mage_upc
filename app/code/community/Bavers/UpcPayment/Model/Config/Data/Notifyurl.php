<?php

class Bavers_UpcPayment_Model_Config_Data_Notifyurl
	extends Mage_Core_Model_Config_Data
{
	public function getValue()
	{
		return Mage::helper('upcpayment')->getNotifyUrl();
	}
}
