<?php


class Banamex extends Objeto {
	public $virtualPaymentClientURL		= 'https://banamex.dialectpayments.com/vpcpay';
	public $vpc_Version 				= '1';
	public $vpc_Command 				= 'pay';
	public $vpc_Currency				= 'MXN';
	public $vpc_Locale					= 'es_MX';
	public $vpc_SecureHashType			= 'SHA256';
	
	public $VPC_conn;
	public $vpc_AccessCode;
	public $vpc_Merchant;
	public $vpc_SecureHash;
	public $Title;
	public $SubButL;
	public $vpc_MerchTxnRef;
	public $vpc_OrderInfo;
	public $vpc_Amount;
	public $vpc_CustomPaymentPlanPlanId;
	//vpc_NumPayments
	public $againLink;
	public $vpc_ReturnURL;
	
	public function __construct(){
		$this->vpc_AccessCode	= Config::getGlobalValue('banamex_vpc_AccessCode');
		$this->vpc_Merchant		= Config::getGlobalValue('banamex_vpc_Merchant');
		$this->vpc_SecureHash	= Config::getGlobalValue('banamex_secureSecret');
		$this->vpc_ReturnURL	= ABS_HTTP_URL . 'bo/banamex_resp.php';
	}
	
	public function genericVpcUrl($order_id, $amount, $plan_id = NULL){
		$this->VPC_conn = new VPCPaymentConnection();
		$this->vpc_MerchTxnRef 				= $order_id;
		$this->vpc_OrderInfo				= $order_id;
		$this->vpc_Amount					= $amount;
		$this->vpc_CustomPaymentPlanPlanId	= $plan_id;
		
		$this->VPC_conn->setSecureSecret($this->vpc_SecureHash);
		
		$attributes = array('vpc_AccessCode','vpc_Amount','vpc_Command','vpc_Currency','vpc_CustomPaymentPlanPlanId','vpc_Locale','vpc_MerchTxnRef','vpc_Merchant','vpc_OrderInfo','vpc_ReturnURL','vpc_Version','Title');
		
		foreach ($attributes as $attribute)
			if (strlen($this->$attribute) > 0) 
				$this->VPC_conn->addDigitalOrderField($attribute, $this->$attribute);
		
		$this->VPC_conn->addDigitalOrderField("AgainLink", $this->againLink);
		$this->VPC_conn->addDigitalOrderField("Title", $this->Title);
		$this->VPC_conn->addDigitalOrderField("vpc_SecureHash", $this->VPC_conn->hashAllFields());
		$this->VPC_conn->addDigitalOrderField("vpc_SecureHashType", "SHA256");
		
		return $this->VPC_conn->getDigitalOrder($this->virtualPaymentClientURL);
	}
	
	public function errorsExist(array $params){
		$this->VPC_conn = new VPCPaymentConnection();
		$this->VPC_conn->setSecureSecret($this->vpc_SecureHash);
		foreach($params as $key => $value) {
			if ((strlen($value) > 0) && ($key!="vpc_SecureHash")) {
				$this->VPC_conn->addDigitalOrderField($key, $value);
			}
		}
		$secureHash = $this->VPC_conn->hashAllFields();
		return $secureHash==$params["vpc_SecureHash"];
	}
}