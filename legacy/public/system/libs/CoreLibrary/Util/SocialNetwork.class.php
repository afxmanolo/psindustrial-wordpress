<?
abstract class Util_SocialNetwork extends Objeto {

	public static function GetJavaScriptGooglePlus($location = 'header'){
		
		return $location == 'header' 
			? 	'<!-- A�ade esta etiqueta en la cabecera o delante de la etiqueta body. -->
				<script type="text/javascript" src="https://apis.google.com/js/plusone.js">
  					{lang: \'es-419\'}
				</script>'
			:	'<script type="text/javascript">function plusone_vote( obj ){ _gaq.push([\'_trackEvent\',\'plusone\',obj.state]); }</script>';
	}
	
	public static function CreateLinkToSocialNetwork($red,$titulo = '',$href = '',$tpl2=false)
	{
		$link = '';
		switch($red){
			case 'facebook':
				$link		= '<fb:like href="" send="false"  layout="button_count"  show_faces="false" width="50px"></fb:like>';
				$javascript = '<html xmlns:fb="http://www.facebook.com/2008/fbml">
								<div id="fb-root" name="fb-root" style="width:50px;"></div>
								<script type="text/javascript" src="'.ABS_HTTP_URL.'js/facebook.js"></script>';
				break;
			case 'twitter':
				$href 			= "http://twitter.com/share?".str_replace(array('http://','https://'), '', $href);
				$data_text		= "$titulo";
				$data_via		= "EcoBambu"; //nombre TW
				$data_related 	= "EcoBambu"; //nombre TW
				$data_count		= "horizontal";
				$title		= "Agregar a Twitter";
				$javascript	= '	<script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script>
								<script type="text/javascript" src="'.ABS_HTTP_URL.'js/twitter.js"></script>';
				$class		= 'twitter-share-button';
				break;
			case 'email':
				$title		= "Mandar por e-mail";
				$alt		= "E-Mail";
				$src		= ABS_HTTP_URL . "imas/restaurant/mail.gif";
				$rel		= 'nofollow';
				$href		= ABS_HTTP_URL . "email.php";
				break;
			case 'print':
				$title		= "Imprimir";
				$alt		= "Imprimir";
				$src		= ABS_HTTP_URL . "ima/interior/print.gif";
				$href		= 'javascript:void(window.open(\''.$href.'\',\'mywindow\',\'location=1,status=1,scrollbars=1, width=600,height=700\'))';
				$rel		= 'nofollow';
				break;
			case 'google+':
				$link = '<g:plusone size="medium"></g:plusone>'; 
				break;
		}
		
		$link	= empty($link)  ? 	'<a '.($href?"href=\"$href\"":'').' 
										'.($title?"title=\"$title\"":'').'
										'.($data_text?"data-text=\"$data_text\"":'').'
										'.($data_count?"data-count=\"$data_count\"":'').'
										'.($data_via?"data-via=\"$data_via\"":'').'
										'.($data_related?"data-related=\"$data_related\"":'').'
										'.($class?"class=\"$class\"":'').'
										'.($rel?"rel=\"$rel\"":'').' >
										'.($src ? '<img 
													src="'.$src.'" 
													border="0" 
													'.($alt?"alt=\"$alt\"":'').' />' :'').'</a>' 
								: $link;
		
		if(array_search(Util_Server::getScript(), array('tu-version-5-mayo.php','index.php','noticias.php')) !== false
			|| $tpl2){
			$html = $javascript.$link;
		}else{
			$html	= '<tr>
							<td align="left" valign="top" style="padding: 5px; border-bottom: dotted; border-bottom-color: #036; border-bottom-width: thin">
							<table width="100%" border="0" cellspacing="0" cellpadding="0">
								<tr>
									<td width="19%">
									'.$javascript.$link.'
									</td>
								</tr>
							</table>
							</td>
						</tr>';
		}
		return $html;
	}

	public function make_bitly_url($url,$format = 'xml',$version = '2.0.1')  
	{
		$login = "";
		$appkey = "R_48f10095ccae2c81119580f7e2e7af3c";  
		//create the URL  
		$bitly = 'http://api.bit.ly/shorten?version='.$version.'&longUrl='.urlencode($url).'&login='.$login.'&apiKey='.$appkey.'&format='.$format;  
		
		//get the url  
		//could also use cURL here  
		$response = file_get_contents($bitly);  
		
		//parse depending on desired format  
		if(strtolower($format) == 'json')  
		{  
			$json = @json_decode($response,true);  
			return $json['results'][$url]['shortUrl'];  
		}  
		else //xml  
		{  
			$xml = simplexml_load_string($response);  
			return 'http://bit.ly/'.$xml->results->nodeKeyVal->hash;  
		}  
	}
}
?>