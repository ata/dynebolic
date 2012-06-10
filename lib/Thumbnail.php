<?php
class Thumbnail{
	var $image;
	var $quality = 100;
	var $mimetype;
	var $imageproperties = array();
	var $initialfilesize;
	function Thumbnail($file, $thumbsize = 100){
		is_file($file) or die('File:'.$file.' tidak ada');
		$this->initialfilesize = filesize($file);
		$this->imageproperties = getimagesize($file) or die('tipe file salah');
		$this->mimetype = image_type_to_mime_type($this->imageproperties[2]);
		switch($this->imageproperties[2]){
			case IMAGETYPE_JPEG:
				$this->image = imagecreatefromjpeg($file);
				break;
			case IMAGETYPE_GIF:
				$this->image = imagecreatefromgif($file);
				break;
			case IMAGETYPE_PNG:
				$this->image = imagecreatefrompng($file);
				break;
			default:
				die('tidak bisa membuat image');
		}
		$this->createThumb($thumbsize);
	}
	function createThumb($thumbsize){
		$srcW = $this->imageproperties[0];
		$srcH = $this->imageproperties[1];
		if($srcW > $thumbsize || $srcH > $thumbsize){
			$reduction = $this->calculateReduction($thumbsize);
			$desW = $srcW / $reduction;
			$desH = $srcH / $reduction;
			$copy = imagecreatetruecolor($desW,$desH);
			imagecopyresampled($copy,$this->image,0,0,0,0,$desW,$desH,$srcW,$srcH) or die('gagal copy image');
			imagedestroy($this->image);
			$this->image = $copy; 
		}
	}
	function calculateReduction($thumbsize){
		$srcW = $this->imageproperties[0];
		$srcH = $this->imageproperties[1];
		if($srcW < $srcH){
			$reduction = round($srcH/$thumbsize);
		}
		else{
			$reduction = round($srcW/$thumbsize);
		}
		return $reduction;
	}
	function getImage($output = ''){
		header('Content-type:'.$this->mimetype);
		switch($this->imageproperties[2]){
			case IMAGETYPE_JPEG:
				imagejpeg($this->image,$output,$this->quality);
				break;
			case IMAGETYPE_GIF:
				imagegif($this->image,$output);
				break;
			case IMAGETYPE_PNG:
				imagepng($this->image,$output,$this->quality);
				break;
			default:
				die('tidak bisa membuat image');
		}
	}
	function setQuality($quality){
		if($quality > 100 || $quality < 1){
			$quality = 75;
		}
		if($this->imageproperties == IMAGETYPE_JPEG || $this->imageproperties == IMAGETYPE_PNG){
			$this->quality = $quality;
		}
	}
	function getQuality(){
		$quality = null;
		if($this->imageproperties == IMAGETYPE_JPEG || $this->imageproperties == IMAGETYPE_PNG){
			$quality = $this->quality;
		}
		return $quality;
	}
	function getMimeType(){
		return $this->mimetype;
	}
}
