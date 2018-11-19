<?php

class Bavers_UpcPayment_Model_Source_Interfacelanguages
{
	public function toOptionArray()
	{
		return array(
			array('value' => 'RU', 'label' => Mage::helper('upcpayment')->__('Russian')),
			array('value' => 'UK', 'label' => Mage::helper('upcpayment')->__('Ukrainian')),
			array('value' => 'EN', 'label' => Mage::helper('upcpayment')->__('English')),
		);
	}
}
