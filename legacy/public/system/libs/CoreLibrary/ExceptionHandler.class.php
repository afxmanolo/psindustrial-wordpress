<?php
abstract class ExceptionHandler extends Objeto {

	/**
	 * This is the function that is called whenever an exception is raised
	 *
	 * @param Exception $e
	 * @return void
	 */
	public static function catchException( \Throwable $e ){		
		$html = self::displayHtmlException( $e, true );
		//$printToScreen = SERVER_DEVELOPMENT;
		$printToScreen = true;
		if ( $printToScreen ){
			echo $html;	
		}
		else {						
			if ( !headers_sent() ){
				header("HTTP/1.1 500 Internal Server Error");
			}
			else {
				
			}
		}
		
	}

	/**
	 * This function displays a friendly HTML Page with the Exception description
	 * as well as many other usefull information for debuggin purposes
	 *
	 * @param Exception $e
	 * @return void
	 */
	public static function displayHtmlException( \Throwable $e, $return = false ){
		$html = '<style>';
		$html .= '    body {';
		$html .= '    font-size: 12px;';
		$html .= '    margin: 20px;';
		$html .= '    background-color: #FFFFFF;';
		$html .= '  }';
		$html .= '';
		$html .= '  table {';
		$html .= '    width: 100%;';
		$html .= '    font-size: 12px;';
		$html .= '    border: 1px solid #CCCCCC;';
		$html .= '    border-collapse: collapse;';
		$html .= '    padding: 0px;';
		$html .= '    margin: 0px;';
		$html .= '  }';
		$html .= '';
		$html .= '  th {';
		$html .= '    font-size: 14px;';
		$html .= '    padding-left: 5px;';
		$html .= '    padding-right: 5px;';
		$html .= '    padding-top: 2px;';
		$html .= '    padding-bottom: 2px;';
		$html .= '    background-color: #EEEEEE;';
		$html .= '    color: #666666;';
		$html .= '    border: 1px solid #CCCCCC;';
		$html .= '    text-align: left;';
		$html .= '  }';
		$html .= '';
		$html .= '  td {';
		$html .= '    font-family: Arial;';
		$html .= '    font-size: 1em;';
		$html .= '    padding-left: 10px;';
		$html .= '    padding-right: 10px;';
		$html .= '    padding-top: 3px;';
		$html .= '    padding-bottom: 3px;';
		$html .= '    border-bottom: 1px solid #CCCCCC;';
		$html .= '  }';
		$html .= '';
		$html .= '  span {';
		$html .= '    font-family: Arial;';
		$html .= '    color: #AA0000;';
		$html .= '  }';
		$html .= '';
		$html .= '  h1 {';
		$html .= '    font-family: Arial;';
		$html .= '    font-size: 24px;';
		$html .= '    font-weight: bold;';
		$html .= '    margin: 0px;';
		$html .= '  }';
		$html .= '';
		$html .= '  div {';
		$html .= '    font-family: Arial;';
		$html .= '    font-size: 12px;';
		$html .= '    margin: 0px;';
		$html .= '    padding: 10px;';
		$html .= '    border: 1px solid #000000;';
		$html .= '  }';
		$html .= '</style>';
		$html .= '<h1 width="100%">';
		$html .= get_class( $e );
		$html .= ':	<span>' . nl2br( $e->getMessage() ) . '</span>';
		$html .= '</h1>';
		$html .= '<br>';
		$html .= '<div><strong>File:</strong> <span>' . $e->getFile() . '</span><br>';
		$html .= '<strong>Line:</strong> <span>' . $e->getLine() . '</span><br>';
		if ( is_subclass_of( $e, 'NestedException' ) || get_class( $e ) == 'NestedException' ) {
			$class = $e->getNestedException();
			$levels = 0;
			while( $class != null ){
				$levels++;
				$html .= '<b>Caused by:</b><br><ul>';
				$html .= '<li><strong>' . get_class( $class ) . '</strong> <span>' . $class->getMessage() . '</span><br>';
				$html .= '<strong>File:</strong> <span>' . $class->getFile() . '</span><br>';
				$html .= '<strong>Line:</strong> <span>' . $class->getLine() . '</span><br>';
				if ( method_exists( $class, 'getNestedException' ) ){
					$class = $class->getNestedException();
				}
				else {
					$class = null;
				}
			}
			$html .= str_repeat( '</ul>', $levels );
		}		
		
		$html .= '<em>';
		$html .= '<pre>' . $e->getTraceAsString(); //join( '<br>', var_export( $e->getTrace(), true ) ); //
		$html .= '</pre></em></div>';
		$html .= nl2br( str_replace( " ", " &nbsp;", print_r( $e, true ) ) );
		$html .= '<br><table cellspacing="0" width="100%" >';
		$vars = array ( 'POST' => $_POST , 'GET' => $_GET , 'SESSION' => isset( $_SESSION ) ? $_SESSION : 0 , 'GLOBALS' => $GLOBALS , 'COOKIES' => $_COOKIE , 'SERVER' => $_SERVER , 'ENV' => $_ENV );
		foreach ( $vars as $var => $val ){
			$html .= '<tr><th colspan="2">' . $var . '</th></tr>';
			if ( is_array( $val ) ){
				foreach ( $val as $key => $value ){
					if ( $var == 'GLOBALS' ){
						$txt = '';
						if( !isset( $value ) || !is_array( $value )){ continue; }
						foreach ( $value as $a => $b ){
							if ( $a == 'GLOBALS' || strpos( $a, '_' ) === 0 || strpos( $a, 'HTTP_' ) === 0 ){
								continue;
							} else{
								$b = print_r( $b, true );
								$b = str_replace( " ", " &nbsp;", $b );
								$b = nl2br( $b );
								$html .= "<tr><td><span>" . $a . "</span></td><td>" . $b . "</td></tr>";
							}
						}
					} else{
						$txt = print_r( $value, true );
						$txt = str_replace( " ", " &nbsp;", $txt );
						$txt = nl2br( $txt );
						$html .= "<tr><td><span>" . $key . "</span></td><td>" . $txt . "</td></tr>";
					}
				
				}
			}
		}
		
		$html .= '</table>';
		
		if ( $return ){
			return $html;
		} 
		else {
			echo $html;
		}
	}

	/**
	 * This function assigns this class as the default Exception Handler
	 *
	 */
	public static function initialize(){
		set_exception_handler( array( "ExceptionHandler", "catchException" ) );
//		set_error_handler( array( "ExceptionHandler", "catchError" ) );
	}
	
	public static function catchError( $errno, $errstr='', $errfile='', $errline='' ){
		
		if(!defined('E_STRICT'))            define('E_STRICT', 2048);
    if(!defined('E_RECOVERABLE_ERROR')) define('E_RECOVERABLE_ERROR', 4096);
    
		$errorType = array (	E_ERROR          	=> 'Error',
													E_WARNING        	=> 'Warning',
													E_PARSE          	=> 'ParsingError',
													E_NOTICE         	=> 'Notice',
													E_CORE_ERROR     	=> 'CoreError',
													E_CORE_WARNING  	=> 'CoreWarning',
													E_COMPILE_ERROR  	=> 'CompileError',
													E_COMPILE_WARNING => 'CompileWarning',
													E_USER_ERROR     	=> 'UserError',
													E_USER_WARNING   	=> 'UserWarning',
													E_USER_NOTICE    	=> 'UserNotice',
													E_STRICT         	=> 'StrictNotice', 
													E_RECOVERABLE_ERROR  => 'RecoverableError'
												);
		
		
		$errno = $errno & error_reporting();		
		if ( $errno == 0 ){		// Error was suppresed using @
			return;
		}
		else {
			// Create a new ErrorException so sub-classes are loaded into memory and
			// no ClassNotFoundException is triggered.
			$e = new ErrorException2( '' );
			unset( $e );
			
			$class = $errorType[ $errno ];
			$text = $errstr . ' [Code: ' . $errno . ']';
			throw new $class( $text, $errno, $errno, $errfile, $errline );
		}
	}
}
?>