<?php
// Vérification de la présence d'un chemin vers l'image    
if($_GET['img'] == '') {
	exit;
}

// Récupération de paramêtres
$_image_ = urldecode( $_GET['img'] );
$_dossier = dirname($_image_).'/cache/';
$_fichier = pathinfo($_image_);
$_fichier['filename'] = trim(basename($_image_, $_fichier['extension']), '.');
$_width_min_ = intval($_GET['width']);
$_height_min_ = intval($_GET['height']);
$_quality_ = intval($_GET['quality']);
$_centrage = false;
if (isset($_GET['centrage'])) {
	$_centrage = (bool)$_GET['centrage'];
}

// Création du dossier de cache
if (!is_dir($_dossier)) {
	mkdir($_dossier);
}
// Création du nom du fichier de cache
$fichier_cache = $_dossier.$_fichier['filename'].'_w'.$_width_min_.'_q'.$_quality_.'.'.$_fichier['extension'];
//echo $fichier_cache;
// Recherche de la présence d'une image en cache ou création de celle-ci
if (file_exists($fichier_cache)) {
	switch (strtolower($_fichier['extension'])) {
		case 'jpg' :
			header('Content-type: image/jpg');
			Imagejpeg(ImageCreateFromJpeg($fichier_cache));
			break;
		case 'gif' :
			header('Content-type: image/gif');
			Imagegif(ImageCreateFromGif($fichier_cache));
			break;
		case 'png' :
			header('Content-type: image/png');
			Imagepng(ImageCreateFromPng($fichier_cache));
			break;
	}
} else {
	// Calcul de la hauteur et de la largeur
	$info = getimagesize($_image_);
	if ($info[0] == '') {
		exit();	
	}
	$new_w = $_width_min_;
	$new_h = (int)($info[1]*($new_w/$info[0]));
	if(($_height_min_) AND ($new_h > $_height_min_)) {
		$new_h = $_height_min_;
		$new_w = (int)($info[0]*($new_h/$info[1]));
	}
	
	// Définition des points d'origine de destination
	$dst_x = 0;
	$dst_y = 0;
	$dst_l = $new_w;
	$dst_h = $new_h;
	if ($_centrage != false) {
		$dst_x = (int)(($_width_min_ - $new_w) / 2);
		$dst_y = (int)(($_height_min_ - $new_h) / 2);
		$dst_l = $_width_min_;
		$dst_h = $_height_min_;
	}
	
	// Création de l'image
	switch (strtolower($_fichier['extension'])) {
		case 'jpg' :
			header("Content-type: image/jpg");
			$dst_img = imagecreatetruecolor($dst_l, $dst_h);
			$c_fond = imagecolorallocate($dst_img, 255, 255, 255);
			imagefill($dst_img, 0, 0, $c_fond);
			$src_img = ImageCreateFromJpeg($_image_);
			imagecopyresampled($dst_img,$src_img,$dst_x,$dst_y,0,0,$new_w,$new_h,ImageSX($src_img),ImageSY($src_img));
			$img_cache = Imagejpeg($dst_img, $fichier_cache, $_quality_);
			$img = Imagejpeg($dst_img, '', $_quality_);
			break;
		case 'gif' :
			header("Content-type: image/gif");
			$dst_img=ImageCreate($new_w,$new_h);
			$src_img=ImageCreateFromGif($_image_);  
			ImagePaletteCopy($dst_img,$src_img);
			ImageCopyResized($dst_img,$src_img,$dst_x,$dst_y,0,0,$new_w,$new_h,ImageSX($src_img),ImageSY($src_img));
			$img_cache = Imagegif($dst_img, $fichier_cache, $_quality_);
			$img = Imagegif($dst_img,'', $_quality_);
			break;
		case 'png' :
			header("Content-type: image/png");
			$dst_img=ImageCreate($new_w,$new_h);
			$src_img=ImageCreateFromPng($_image_);  
			ImagePaletteCopy($dst_img,$src_img);
			ImageCopyResized($dst_img,$src_img,$dst_x,$dst_y,0,0,$new_w,$new_h,ImageSX($src_img),ImageSY($src_img));
			$img_cache = Imagepng($dst_img, $fichier_cache, $_quality_);
			$img = Imagepng($dst_img,'', $_quality_);
			break;
	}
}
?>