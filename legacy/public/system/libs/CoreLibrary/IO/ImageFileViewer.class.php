<?php
class IO_ImageFileViewer extends IO_FileViewer {

	public function __construct( DBPEAR $db ){
		parent::__construct( $db );
	}

	public function view( $id ){
		$file = $this->dao->loadById( $id );
		$size = ( isset( $_REQUEST['img_size'] ) && !empty( $_REQUEST['img_size'] ) ) ? $_REQUEST['img_size'] : 'original';
		
		if ( empty( $file ) || !file_exists( $file['path'] . $file['filename' ] ) ){
			$file = array();
			$file['path'] = 'ima/';
			$file['filename'] = 'GoPuebla.gif';
			$file['type'] = 'image/gif';
			$file['name'] = 'Image not found';
			$file['date'] = date( 'Y-m-d' );
			//$this->_view( $file, 'inline', false );
			switch ( $size ){
				case 'predefined':
					$max_height = $_REQUEST['height'];
					$max_width = $_REQUEST['width'];
					break;
				case 'thumbnail':
					$max_width = 162;
					$max_height = 93;
					break;
				case 'small':
					$max_width = 250;
					$max_height = $max_width;
					break;
				case 'normal':
				default:
					$max_width = 500;
					$max_height = $max_width;
					break;
			}
			$this->resizeAndDisplay( $file, $max_width, $max_height );
			//echo "File does not exist in DB and/or in file system.";
		}
		else {
			// File DOES exist, what view is the user interested in getting?
			if ( $size == 'original' ){
				$this->_view( $file, 'inline', false );
			}
			else {
				switch ( $size){
					case 'predefined':
  					$max_height = $_REQUEST['height'];
  					$max_width = $_REQUEST['width'];
//  					$max_height = 50;
//  					$max_width = 50;
						
  					break;
					case 'thumbnail':
						$max_width = 162;
						$max_height = 93;
						break;
					case 'small':
						$max_width = 250;
						$max_height = $max_width;
						break;
					case 'normal':
					default:
						$max_width = 500;
						$max_height = $max_width;
						break;
				}
				$this->resizeAndDisplay( $file, $max_width, $max_height );
			}
		}
	}

	private function resizeAndDisplay( $file, $new_w, $new_h ){
		@set_time_limit( 0 );
		$src_img = $this->open_image( $file['path'] . $file['filename'] );
		
		$old_w=imageSX($src_img);
		$old_h=imageSY($src_img);
		
		$thumb_w = $old_w;
		$thumb_h = $old_h;
		
		$new_w = (empty($new_w) || $new_w <= 0) ? $thumb_w : $new_w;
		$new_h = (empty($new_h) || $new_h <= 0) ? $thumb_h : $new_h;

		
		
		if( $thumb_w > $new_w) {
			$thumb_h = round(($thumb_h*$new_w)/$thumb_w);
			$thumb_w = $new_w;
		}
		
		if( $thumb_h > $new_h ){
			$thumb_w = round(($thumb_w*$new_h)/$thumb_h);
			$thumb_h = $new_h;
		}
		
	
		$dst_img=ImageCreateTrueColor($thumb_w,$thumb_h);
		imagecopyresampled($dst_img,$src_img,0,0,0,0,$thumb_w, $thumb_h,$old_w,$old_h);

		header('Content-type: image/jpeg');
		imagejpeg($dst_img);
		imagedestroy( $dst_img );
		die();
	}

	private function open_image($file) {
//		ini_set( "memory_limit", "32M" );
		$im = @imagecreatefromjpeg($file);
		if ($im !== false) { return $im; }
		# GIF:
		$im = @imagecreatefromgif($file);
		if ($im !== false) { return $im; }
		# PNG:
		$im = @imagecreatefrompng($file);
		if ($im !== false) { return $im; }

		# GD File:
		$im = @imagecreatefromgd($file);
		if ($im !== false) { return $im; }

		# GD2 File:
		$im = @imagecreatefromgd2($file);
		if ($im !== false) { return $im; }

		# WBMP:
		$im = @imagecreatefromwbmp($file);
		if ($im !== false) { return $im; }

		# XBM:
		$im = @imagecreatefromxbm($file);
		if ($im !== false) { return $im; }

		# XPM:
		$im = @imagecreatefromxpm($file);
		if ($im !== false) { return $im; }

		# Try and load from string:
		$im = @imagecreatefromstring(file_get_contents($file));
		if ($im !== false) { return $im; }

		return false;
	}

	private function setMemoryForImage( $filename ){
		$imageInfo = getimagesize($filename);
		$MB = 1048576;  // number of bytes in 1M
		$K64 = 65536;    // number of bytes in 64K
		$TWEAKFACTOR = 1.5;  // Or whatever works for you
		$memoryNeeded = round( ( $imageInfo[0] * $imageInfo[1]
		* $imageInfo['bits']
		* $imageInfo['channels'] / 8
		+ $K64
		)
		* $TWEAKFACTOR
		);
		//ini_get('memory_limit') only works if compiled with "--enable-memory-limit" also
		//Default memory limit is 8MB so well stick with that.
		//To find out what yours is, view your php.ini file.
		$memoryLimit = 8 * $MB;
		if (function_exists('memory_get_usage') && memory_get_usage() + $memoryNeeded > $memoryLimit ){
			$newLimit = $memoryLimitMB + ceil( ( memory_get_usage() + $memoryNeeded - $memoryLimit ) / $MB );
//			ini_set( 'memory_limit', $newLimit . 'M' );
			return true;
		}
		else {
			return false;
		}
	}
}
?>