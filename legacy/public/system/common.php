<?php
ini_set( 'date.timezone', 'America/Mexico_City' );
ini_set('memory_limit', '2048M');
set_time_limit(3600);
ini_set("display_errors",0);
error_reporting(0);

/**
 * This function loads ALL classes required by the application
 *
 * @param string $class_name
 * @return void
 */

function init_autoload( $class_name ){
    
    //$debug = defined('DEVELOPMENT_TEST') ? DEVELOPMENT_TEST : false;
    $debug = false;
    $path = array ( );
    
    $pathToLib = 'libs/';
    
    if ( strpos( $class_name, 'Zend_') === 0 ){
        $path[] = $pathToLib . str_replace( "_", "/", $class_name ) . ".php";
    }
    else {
        $path[] = $pathToLib . str_replace( "_", "/", $class_name ) . ".class.php";
        $path[] = $pathToLib . "CoreLibrary/" . str_replace( "_", "/", $class_name ) . ".class.php";
        $path[] = $pathToLib . "CoreLibrary/Exceptions/" . str_replace( "_", "/", $class_name ) . ".class.php";
        $path[] = $pathToLib . "PEAR/" . str_replace( "_", "/", $class_name ) . ".php";
        //$path[] = $pathToLib . "PhpMailer/class." . $class_name . ".php";
        //$path[] = $pathToLib . "PayPal/" . str_replace( "_", "/", $class_name ) . ".php";
        //$path[] = $pathToLib . "Nusoap/nusoap.php";
        //$path[] = $pathToLib . "PEAR/MDB2.php";
    }
    
    if ( $debug ){
        echo "Looking for <b>$class_name</b><br>";
    }
    
    $found = false;
    
    $include_paths = explode( PATH_SEPARATOR, get_include_path() );
    
    foreach ( $path as $filename ){        
        if ( $debug ){
            echo "Filename: " . $filename . ' ... ';
        }
        if(defined('DEVELOPMENT_TEST') && DEVELOPMENT_TEST){
            if ( include_once ( $filename ) ){
                if ( $debug ){
                    echo "<B>FOUND</B><br>";
                }
                return;
            } else{
                if ( $debug ){
                    echo "Not found <br>";
                }
            }
        }else{
            if ( @include_once ( $filename ) ){
                if ( $debug ){
                    echo "<B>FOUND</B><br>";
                }
                return;
            } else{
                if ( $debug ){
                    echo "Not found <br>";
                }
            }
        }
    }
    
    
    if ( $debug ){
        echo "<br>";
    }
    if ( $found ){
        return true;
    }
    //echo "Termino";
    // The following line is required so no Fatal error is returned and the exception is launched
    
    //eval( 'class ' . $class_name . ' extends ClassNotFoundException {}' );
    throw new \RuntimeException("Class {$class_name} not found");
    
    // Prepare the exception descriptive text.
    $txt = "The class <b>$class_name</b> could not be found under the following paths:<br>";
    $txt .= '<ul><li>' . join( '</li><li>', $path ) . '</li></ul>';
    throw new ClassNotFoundException( $txt );
}

// autoload init php 7.2
//init_autoload();
spl_autoload_register('init_autoload');
@session_start();


/**
 * Debug function that writes to the output buffer the var_dump() information
 * from the given $var using $msg as label. The var_dump information is
 * <pre>formatted.
 *
 * @param mixed $var The variable to debug
 * @param string $msg The label to prepend before the debug information
 */
function debug( $var, $msg = null ){
    echo '<pre>';
    echo $msg;
    ob_start();
    var_dump( $var );
    $debug = ob_get_contents();
    ob_end_clean();
    
    $debug = htmlentities( $debug );
    
    echo str_replace( "=>\n", "=>", $debug );
    
    echo '</pre>';
}

setlocale(LC_CTYPE, 'es_MX');

define( 'ABS_PATH', dirname( __FILE__ ) );
define( 'SYSTEM_DIRECTORY', 'system/');
define( 'BO_DIRECTORY', 'system/');

// Initialize default constants
Config::Initialize();

// Initialize the error handler
ExceptionHandler::initialize();
//define ("CHARSET_PROJECT", empty($CHARSET) ? "ISO-8859-1" : $CHARSET);
define ("CHARSET_PROJECT", empty($CHARSET) ? "UTF-8" : $CHARSET);
//define ("CHARSET_PROJECT_DB", "utf8");

if(isset($_SERVER[ 'HTTP_HOST']) && $_SERVER[ 'HTTP_HOST']!=null){
    header('Content-Type: text/html; charset=' . CHARSET_PROJECT);
}
if( defined('LANGUAGE') ){
    $lang = ProjectLibrary_SDO_Core_Application_LanguageManager::GetActualLanguage();
    if(LANGUAGE != $lang){
        ProjectLibrary_SDO_Core_Application_LanguageManager::SetActualLanguage( LANGUAGE );
    }
}
$pathToLib = 'libs/';
$include  = $pathToLib . "HTTP/Request2.php";
$include1 = $pathToLib . "HTTP/Net/URL2.php";
$include2 = $pathToLib . "HTTP/Request2/Adapter.php";
$include3 = $pathToLib . "HTTP/Request2/CookieJar.php";
//$include4 = $pathToLib . "HTTP/Request2/Exception.php";
$include5 = $pathToLib . "HTTP/Request2/MultipartBody.php";
$include6 = $pathToLib . "HTTP/Request2/Response.php";
$include7 = $pathToLib . "HTTP/Request2/SocketWrapper.php";
$include8 = $pathToLib . "HTTP/Request2/SOCKS5.php";
$include9 = $pathToLib . "HTTP/Request2/Adapter/Curl.php";
$include10 = $pathToLib . "HTTP/Request2/Adapter/Mock.php";
$include11 = $pathToLib . "HTTP/Request2/Adapter/Socket.php";
$include12 = $pathToLib . "HTTP/Request2/Observer/Log.php";

$includeDB  = $pathToLib . "PEAR/DB/common.php";
$includeDB1 = $pathToLib . "PEAR/DB.php";

include_once ( $includeDB ) ;
include_once ( $includeDB1);


include_once ( $include ) ;

include_once ( $include1 ) ;
include_once ( $include2 ) ;
include_once ( $include3 ) ;
//include_once ( $include4 ) ;
include_once ( $include5 ) ;
include_once ( $include6 ) ;
include_once ( $include7 ) ;
include_once ( $include8 ) ;
include_once ( $include9 ) ;
include_once ( $include10 ) ;
include_once ( $include11 ) ;
include_once ( $include12 ) ;
?>
