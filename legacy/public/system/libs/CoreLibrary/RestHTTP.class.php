<?php
/**
 * This is a bridge class to use PEAR::MDB2 
 *
 */
//require_once "HTTP/Request2.php";

class RestHTTP{
	public $curr_url = "";
	public $user_name = "";
	public $password = "";
	public $content_type = "";
	public $accept = "";
	public $xVtexApiAppKey = "";
	public $xVtexApiAppToken = "";
	public $response = "";
	public $responseBody = "";
	public $responseCode = "";
	public $request = null;
	
	public function __construct($user_name="", $password="", $instancia = "paylessus") {		
		$this->user_name = $user_name;
		$this->password = $password;				
		if($instancia == "paylessqa"){
    		$this->curr_url = "http://paylessqa.vtexcommercestable.com.br/";
    		$this->content_type = "application/json";
    		$this->accept = "application/json";
    		$this->xVtexApiAppKey = 'vtexappkey-paylessqa-IOTNCE';
    		$this->xVtexApiAppToken = 'OGYOMHGXGZUWTUIQKKOWTZPMSBJGGHHTMKTGZVNGYANVMHRQKXIHJCPPQMVLYAQYRAJJBBQPWHXSMUZLJSWJYAKJUEPNACIYJODMXIPIGTKBEWDWNKBFEHZRQAOWLDPT';
		}elseif($instancia == 'paylessus'){
		    $this->curr_url = "http://paylessus.vtexcommercestable.com.br/";
		    $this->content_type = "application/json";
		    $this->accept = "application/json";
		    $this->xVtexApiAppKey = 'vtexappkey-paylessus-BUBUBY';
		    $this->xVtexApiAppToken = 'GKPGPAJPBKPZEMZNLEITBNTHPFIAMEVOABAWOLASQVIMNBHAHZULSXMSQHRJSJDXOAYDMDYZDQCFFGUNYHSMJJWDOHFGUQZGSKYMDEOEAUIBUHNKNELLRRNEEUOEYRFN';
		}
		
		return true;
	}
	
	public function createRequest($url, $method, $arr = null, $curl_url = "http://paylessus.vtexcommercestable.com.br/", $contentType = "", $accept = "" , $funcion = "", $cabecera="") {	    
		$this->curr_url = !empty($curl_url)?$curl_url.$url:$this->curr_url.$url;		
		$this->request = new HTTP_Request2($this->curr_url);		
		if ($this->user_name != "" && $this->password != "") {
			$this->request->setAuth($this->user_name, $this->password);
		}			
		if ($this->content_type != "" && $this->accept != "" ) {
			$headers = self::getHeaderStructure($contentType, $accept, $cabecera, $funcion);			
			$this->request->setHeader($headers);
		}		
				
		switch($method) {
			case "GET":			    
				$this->request->setMethod(HTTP_Request2::METHOD_GET);						
				self::sendRequest($funcion);
				$fullResponse = self::getResponse();				
				$response = self::getBodyContent($fullResponse[1]);				
				break;
			case "POST":			    
				$this->request->setMethod(HTTP_Request2::METHOD_POST);				
				$this->addPostData($arr);
				self::sendRequest();
				$fullResponse = self::getResponse();				
				$response = self::getBodyContent($fullResponse[1]);	
				break; 
			case "PUT":				
				$this->request->setMethod(HTTP_Request2::METHOD_PUT);					
				$this->addPostData($arr);	
				self::sendRequest();
				$fullResponse = self::getResponse();				
				$response = self::getBodyContent($fullResponse[1]);				
				break;
			case "DELETE":
				$this->request->setMethod(HTTP_Request2::METHOD_DELETE);								
				break;
			case "PATCH":
			    $this->request->setMethod(HTTP_Request2::METHOD_PATCH);
			    $this->addPostData($arr);			    
			    self::sendRequest();
			    $fullResponse = self::getResponse();
			    $response = self::getBodyContent($fullResponse[1]);
			    break;
		}
		//var_dump($fullResponse); die;
		$this->request->setConfig(array('ssl_verify_peer'=>false, 'ssl_verify_host'=>false, 'follow_redirects'=>true));
		// if set to curl, then "getStatus" will break
		// bug for HTTP_Request2
		// at: http://pear.php.net/bugs/bug.php?id=18329
		//$this->req->setAdapter('curl');
		return $response;
	}
	
	public function getBodyContent($content){
		return json_decode($content, true);
	}
	public function getHeaderStructure($contentType = "", $accept = "", $cabecera="", $funcion=""){	  		    
		if ($cabecera && $funcion == ''){			
			$cabecera = "Bearer ".$cabecera;			
			return array(					
					'Authorization'=>$cabecera
					);
		}elseif(!empty($contentType) && !empty($accept)){			
			return array(
					'Content-Type' => $contentType,
					'Accept'=>$accept,
					'x-vtex-api-appKey' => $this->xVtexApiAppKey,
					'x-vtex-api-appToken' => $this->xVtexApiAppToken,
					'Access-Control-Allow-Origin' => "*"
			);
		}elseif($funcion == 'mms' && $cabecera == ''){
			return array(			    
					'Content-Type' => $this->content_type					
			);				
		}elseif($funcion == 'mms' && $cabecera != ''){		    
		    return array(
		        'Authorization'=>"Bearer ".$cabecera		        
		    );
		}else{
		    if(!empty($_REQUEST['range'])){
    		    return array(
    		        'Content-Type' => $this->content_type,
    		        'Accept'=>$this->accept,
    		        'x-vtex-api-appKey' => $this->xVtexApiAppKey,
    		        'x-vtex-api-appToken' => $this->xVtexApiAppToken,
    		        'REST-Range'=> "resources=0-1"
    		    );
		    }else{
		        return array(
		            'Content-Type' => $this->content_type,
		            'Accept'=>$this->accept,
		            'x-vtex-api-appKey' => $this->xVtexApiAppKey,
		            'x-vtex-api-appToken' => $this->xVtexApiAppToken		            
		        );
		    }
		}
	}
	

	private function addPostData($arr) {		
		if ($arr != null) {
			if (gettype($arr) == 'string') {
				$this->request->setBody($arr);
			} else {				
				$this->request->addPostParameter($arr);
			}
		}
	}
	
	public function sendRequest($funcion = "") {
		
		if(!empty($funcion)){
			switch($funcion){
				case 'facturacion':   $this->request->setUrl($this->curr_url);
					break;
				default:
					break;
			}
		}
		
		//try{
			$this->response = $this->request->send();
		/*}catch (Exception $e) {
			//debug($e);
        	echo "error de conexi&oacute;n, mandar correo<br />"; 	
        }*/
		if ($this->response->getStatus() != 200) {
			//debug($this->response);
			//$this->response->getReasonPhrase();
			$this->responseCode = $this->response->getStatus();
			$this->responseBody = $this->response->getBody();
			
		} else {
			//debug($this->response);
			$this->responseCode = $this->response->getStatus();
			$this->responseBody = $this->response->getBody();
		}
	}
	
	public function getResponse() {
		return array($this->responseCode, $this->responseBody);
	}
}
?>