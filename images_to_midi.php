<?php

include("midi-converter-php/midi.class.php");

$midi = new Midi();

$midi->open();


$image_folder = 'piano_out_imgs/';

$scanned_directory = array_diff(scandir($image_folder), array('..', '.', '.DS_Store'));

//var_dump($scanned_directory);

$width = null;
$keylocations = null;
$min_black_key_width = null;
$white_key_width = null;


$DEBUG_read_points = true;

$piano_keys = array(
1,0,1,1,0,1,0,1,1,0,1,0,
1,0,1,1,0,1,0,1,1,0,1,0,
1,0,1,1,0,1,0,1,1,0,1,0,
1,0,1,1,0,1,0,1,1,0,1,0,
1,0,1,1,0,1,0,1,1,0,1,0,
1,0,1,1,0,1,0,1,1,0,1,0,
1,0,1,1,0,1,0,1,1,0,1,0,
1,0,1,1
);

//var_dump($piano_keys);
//exit;
//$piano_keys = array(a,b,c,d,e,f,g,h,i,0,1,0,1);

$location_precent = array(
0.5,//A
0.05,
0.7,//B
0.2,//C
0,//borde flytta minus ca 0.15
0.45,//D
0,
0.7,//E
0.2,//F
0.02,//Borde flytta minus ca 0.15
0.4,//G
0
);


$specified_instrumet_colors = array(
	5700886 => 'green',
	4521729=> 'green',
	3669504 => 'green',
	1630974 => 'blue',
	1732280 => 'blue'
	//1402032 => 'blue',//darkblue edge

	//539152 => darkgreen text
	//3272389 => //green blue edge
	
);

$midi_instruments = array();

// we are expecting 52 white keys and 36 black

$instrumet_colors = array();
$video_colors = array();

$marking = true;
if($marking){
	echo("Creating markings\n");
	@mkdir('markings');
	passthru('rm -r markings');
	mkdir('markings');
}
$keys = array();
$ready = false;
$default_colors = array();
$ending_frame_nr = count($scanned_directory)-1;
$frame_nr = 0;
foreach($scanned_directory AS $frame_key => $frame_image_file){
	if(strpos($frame_image_file, 'mark.png') !== false){
		continue;
	}

	$im = imagecreatefrompng($image_folder.$frame_image_file);

	if(is_null($width)){
		$width = imagesx($im);
		$height = imagesy($im);
		$white_key_width = $width / 52;
		$half_a_white_key = $white_key_width/2;
		$min_black_key_width = intval($white_key_width/4);
		//echo "min_black_key_width $min_black_key_width\n";
		//exit;
		echo("Width found: $width\n");
	}

	
	$all_black = true;
	$x=0;
	$colors = array();
	$light = array();
	$last_keys = $keys;
	$keys = array();
	$last = null;
	$samecount = 0;
	$white_keys=0;
	//while($width>$x){
	$white_keys = 0;
	$i=0;
	$colors = array();
	$color_distance = array();
	
	//foreach key
	while(88>$i){

		$pressed = "_";

		$x = $white_keys*$white_key_width;

		
		$y = intval($height/2);
		$precent = $location_precent[$i%12];
	
		//if white key
		if($piano_keys[$i] == 1){
			$precent = 0.5;
			$white_keys++;
		}else{
		}

		$x += intval($white_key_width*$precent);
		



		$rgb = imagecolorat($im, $x, $y);
		$inst = get_instrument($rgb);
		$analyse_pixel = false;
		if(isset($specified_instrumet_colors[$rgb])){
			$analyse_pixel = true;
		}
		if($analyse_pixel){
			$inst1 = get_instrument(imagecolorat($im, $x, $y-1));
			$inst2 = get_instrument(imagecolorat($im, $x, $y+1));
			$inst3 = get_instrument(imagecolorat($im, $x, $y+2));

			//we only care if there are 4 pixel in a row with the color
			if($inst == $inst1 && $inst == $inst2 && $inst == $inst3){
				
				$instx1 = get_instrument(imagecolorat($im, $x-1, $y));
				$instx2 = get_instrument(imagecolorat($im, $x+1, $y));
				//we also only care if there are 3 pixel side to side
				if($inst == $instx1 && $inst == $instx2){
					if(!in_array($rgb, $video_colors)){
						$video_colors[] = $rgb;
						echo("New color: ".$rgb."\n");
						if($marking){
							$im_write = imagecreatefrompng($image_folder.$frame_image_file);
							$red_col = imagecolorallocate($im_write, 255,0,0);

							//Right corner color indicator
							imagefilledrectangle($im_write, 0, 0, 15, 15, $rgb);

							//where is the color
							imagefilledrectangle($im_write, intval($x)-1, $y-1, intval($x)+1, $y+1, $red_col);
							imagepng($im_write, 'markings/'.$rgb.'.png');
							imagedestroy($im_write);
						}
					}
					//Do we know what instrument this color is
					if(isset($specified_instrumet_colors[$rgb])){
						$red = imagecolorallocate($im, 255, 0, 0);
						imagefilledrectangle($im, intval($x)-1, $y-1, intval($x)+1, $y+1, $red);
						$own_instrument_id = $specified_instrumet_colors[$rgb];
						if(!isset($midi_instruments[$own_instrument_id])){
							$track_id = $midi->newTrack();
							$midi_instruments[$own_instrument_id] = $track_id;
							echo "new instrument: $track_id key($i)\n";
							//exit;
							//$midi->addMsg($instruemnt_nr,"1 TimeSig 3/4 24 8");
							$midi->addMsg($track_id,"1 Tempo 750002");
							$midi->addMsg($track_id,"1 Meta 0x21 00");
							//$midi->addMsg($instruemnt_nr,"1 KeySig 3 major");
						}
						$pressed = $midi_instruments[$own_instrument_id];
					}
				}
			}
		}
		$keys[]= $pressed;
		$i++;
	}

	//if this is the last frame we turn of all the keys
	if($ending_frame_nr == $frame_nr){
		foreach($last_keys AS $keyid => $last_key){
			$keys[$keyid] = "_";
		}
	}

	echo implode("", $keys)." ".$frame_image_file."\n";

	$save = false;

	//Add keys to midi file
	foreach($last_keys AS $keyid => $last_key){
		if($last_key !== $keys[$keyid]){
			$tr  = $keys[$keyid];
			$com = "On";
			$v=49;
			$timestamp = intval(($frame_nr*21));
			//XXXXXXX Fix one note after another is an issue
			if($keys[$keyid] === "_"){// if no key is pressed Turn off key
				$tr  = $last_key;
				$com = "On";
				$v=0;
			}else if($last_key !== "_"){
				//if we just switched instrument we turn off the old one  first
				$midi->addMsg($last_key,($timestamp)." $com ch=1 n=".($keyid+21)." v=0");
				
			}
			//echo $tr.", ".($timestamp)." $com ch=1 n=".($keyid+21)." v=".$v."\n";
			$midi->addMsg($tr,($timestamp)." $com ch=1 n=".($keyid+21)." v=".$v);
			$save = true;
		}
		
	}
	if($save){
		imagepng($im, $image_folder.$frame_image_file.'.mark.png');
	}
	if(!$ready){
		$default_colors = $colors;
		echo "we are ready";
		//echo implode(",", $default_colors);
		echo "\n";
		$ready = true;
	}

	if(!$all_black){
		//if(count($keys) == 88){
		//echo "image: $frame_image_file keys: ".count($light)."\n";
		if($ready){
			//echo implode(",", $color_distance);
			echo implode("", $keys);
			//var_dump($default_colors);
			//var_dump($colors);
			echo "\n";
			//exit;
		}
		//echo implode("", $light);
		if($piano_keys == $light){
				//echo "arrays are equal\n";
		}
		//echo "\n\n";
//}
	}
	if(false && $frame_nr == 1147){//890 is also intressing 877 = normal
		if($DEBUG_read_points){
			imagepng($im, 'imagefilledrectangle.png');
		}
		exit;
	}

	imagedestroy($im);
	$frame_nr += 1;
}
//Close remaning keys


$midi->saveMidFile('song.mid');


function get_instrument($rgb){
	global $specified_instrumet_colors;
	if(isset($specified_instrumet_colors[$rgb])){
		return $specified_instrumet_colors[$rgb];
	}
	return 'none';
}

function same_col($rgb1, $rgb2){
	$r = ($rgb1 >> 16) & 0xFF;
	$g = ($rgb1 >> 8) & 0xFF;
	$b = $rgb1 & 0xFF;
		
	$col1 = array($r,$g,$b);

	$r = ($rgb2 >> 16) & 0xFF;
	$g = ($rgb2 >> 8) & 0xFF;
	$b = $rgb2 & 0xFF;
		
	$col2 = array($r,$g,$b);
	return colorDiff($col1,$col2);
}

function colorDiff_Instrument($rgb1,$rgb2)
{
    // do the math on each tuple
    // could use bitwise operates more efficiently but just do strings for now.
    $red1   = $rgb1[0];
    $green1 = $rgb1[1];
    $blue1  = $rgb1[2];

    $red2   = $rgb2[0];
    $green2 = $rgb2[1];
    $blue2  = $rgb2[2];

    //return abs($red1 - $red2) + abs($green1 - $green2) + abs($blue1 - $blue2) ;
	
	$gb1 = (255-$green1)+$blue1;
	$gb2 = (255-$green2)+$blue2;
    return abs($gb1-$gb2);
 //+ abs($green1 - $green2) + abs($blue1 - $blue2) ;

}

function colorDiff($rgb1,$rgb2)
{
    // do the math on each tuple
    // could use bitwise operates more efficiently but just do strings for now.
    $red1   = $rgb1[0];
    $green1 = $rgb1[1];
    $blue1  = $rgb1[2];

    $red2   = $rgb2[0];
    $green2 = $rgb2[1];
    $blue2  = $rgb2[2];

    return abs($red1 - $red2) + abs($green1 - $green2) + abs($blue1 - $blue2) ;

}
