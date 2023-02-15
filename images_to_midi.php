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
0.5,
0.2,
0.7,
0.2,
-0.15,//borde flytta minus ca 0.15
0.45,
0,
0.7,
0.2,
-0.15,//Borde flytta minus ca 0.15
0.4,
0
);

// we are expecting 52 white keys and 36 black

$instrumet_colors = array();

$keys = array();
$ready = false;
$default_colors = array();
$ending_frame_nr = count($scanned_directory)-1;
$frame_nr = 0;
foreach($scanned_directory AS $frame_key => $frame_image_file){

	$im = imagecreatefrompng($image_folder.$frame_image_file);
	$red = imagecolorallocate($im, 255, 0, 0);

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
	while(88>$i){

		$x = $white_keys*$white_key_width;


		//echo "key $i remanider:".($i%12)."\n";
		
		$y = 0;
		$precent = $location_precent[$i%12];
		if($piano_keys[$i] == 1){
			$precent = 0.5;
			$white_keys++;
			$y = $height-1;
		}

		$x += $white_key_width*$precent;
		
		//echo "x $x\n";


		$rgb = imagecolorat($im, intval($x), $y);
		//imagefilledrectangle($im, intval($x), $y-1, intval($x)+1, $y, $red);
		$r = ($rgb >> 16) & 0xFF;
		$g = ($rgb >> 8) & 0xFF;
		$b = $rgb & 0xFF;
		
		$col = array($r,$g,$b);

		//echo("key: $i, ($x,$y) ($r,$g,$b)\n");
		
		$squared_contrast = (
			$r * $r * .299 +
			$g * $g * .587 +
			$b * $b * .114
		    );

			//$light[] = intval($squared_contrast);


			//if(is_null($last)){
				
			//}

		$pixel = 0;	
		if($squared_contrast > pow(130, 2)){
			$pixel = 1;	//light key
		}

		$colors[] = $col;


		if($ready){
			//we have the data we need we watch out for key presses we know at what pixels to check

			$pressed = "_";

			$diff = colorDiff($col, $default_colors[$i]);
			$color_distance[] = $diff;
			if($r == 0 && $g == 0 && $b == 0){
				
			}else{
				if($diff > 80){
					//the key is beeing pressed figure out what instrument
					$instruemnt_nr = null;
					foreach($instrumet_colors AS $instrument => $ins_col){
						$ins_diff = colorDiff_Instrument($col, $ins_col);
						if($ins_diff < 50){
						//if($ins_diff < 250){
							//samecolor
							$instruemnt_nr = $instrument;
							break;
						}
					}
					if(is_null($instruemnt_nr)){
						echo "new instrument:  diff($ins_diff) key($i)";
						$midi->newTrack();
						var_dump($col);
						echo "\n";
						//exit;
						$instrumet_colors[] = $col;
						$instruemnt_nr = count($instrumet_colors)-1;
						//$midi->addMsg($instruemnt_nr,"1 TimeSig 3/4 24 8");
						$midi->addMsg($instruemnt_nr,"1 Tempo 750002");
						$midi->addMsg($instruemnt_nr,"1 Meta 0x21 00");
						//$midi->addMsg($instruemnt_nr,"1 KeySig 3 major");

						//$midi->addMsg($instruemnt_nr,"1 On ch=1 n=21 v=49");
						//$midi->addMsg($instruemnt_nr,"1 On ch=1 n=1 v=49");
						
					}
					$pressed = $instruemnt_nr;
					echo "key $i heled intrument: $pressed\n";
				}
			}

			$keys[]= $pressed;
			//$color_distance[] = colorDiff($col, $col);
		}else{
			//$default_colors = array();
		}


		if($piano_keys[$i] != $pixel){
			//echo "pixel at $x is: $pixel it shoulde be: ".$piano_keys[$i]."\n";
			//exit;
		}

/*
			if($pixel == $last){
				$samecount++;
			}else{
				if($samecount > $min_black_key_width){
					// this was a key saving it
				}
				$samecount = 0;
			}
*/

		$last = $pixel;
		$light[] = $pixel;
		if($r == 0 && $g == 0 && $b == 0){
			
		}else{
			$all_black = false;
	//		echo "$r, $g, $b\n";
		}
		$x++;
		$i++;
	}

	//if this is the last frame we turn of all the keys
	if($ending_frame_nr == $frame_nr){
		foreach($last_keys AS $keyid => $last_key){
			$keys[$keyid] = "_";
		}
	}

	//Add keys to midi file
	foreach($last_keys AS $keyid => $last_key){
		if($last_key !== $keys[$keyid]){
			$tr  = $keys[$keyid];
			$com = "On";
			$v=49;
			$timestamp = ($frame_nr*20);
			//XXXXXXX Fix one note after another is an issue
			if($keys[$keyid] === "_"){// if no key is pressed Turn off key
				$tr  = $last_key;
				$com = "On";
				$v=0;
			}else if($last_key !== "_"){
				//if we just switched instrument we turn off the old one  first
				$midi->addMsg($last_key,($timestamp)." $com ch=1 n=".($keyid+21)." v=0");
				
			}
			echo $tr.", ".($timestamp)." $com ch=1 n=".($keyid+21)." v=".$v."\n";
			$midi->addMsg($tr,($timestamp)." $com ch=1 n=".($keyid+21)." v=".$v);
		}
		
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
			if(!$ready){
				$default_colors = $colors;
				echo "we are ready";
				//echo implode(",", $default_colors);
				echo "\n";
				$ready = true;
			}
			//echo "arrays are equal\n";
		}
		echo "\n\n";
//}
	}
	if($frame_nr == 218){
		//imagepng($im, 'imagefilledrectangle.png');
		//exit;
	}

	imagedestroy($im);
	$frame_nr += 1;
}
//Close remaning keys


$midi->saveMidFile('song.mid');

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
