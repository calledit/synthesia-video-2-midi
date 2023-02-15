<?php

function run($command){
	exec( $command, $output, $return_var);
	
	return implode("\n", $output);
}
$url = $argv[1];
$timecut = intval($argv[2]);
echo("Downloading: $url\n");

//run('youtube-dl -f best -o piano_video.mp4 '.escapeshellarg($url));

if(!file_exists('piano_video.mp4')){
	throw new Exception("video not downloaded");
}
echo("File downloaded\n");

$file_dat = json_decode(run("ffprobe -v quiet -print_format json -show_format -show_streams piano_video.mp4"), true);
$vid_stream = $file_dat['streams'][0];
var_dump($vid_stream['width']);
var_dump($vid_stream['height']);

if($timecut == 0){
	$timecut = intval($vid_stream['duration']);
}

run('rm -r piano_out_imgs');
@mkdir('piano_out_imgs');

//we asume the keys are located 83% down in the image
$key_y_cordinates = intval($vid_stream['height']*0.83);
$key_y_bottom_cordinates = intval($vid_stream['height']*0.95);
$extracted_height = $vid_stream['height'] - $key_y_bottom_cordinates;

run('ffmpeg -i piano_video.mp4 -t '.$timecut.' -filter:v "crop='.$vid_stream['width'].':'.$extracted_height.':0:'.$key_y_cordinates.'" piano_out_imgs/output_%05d.png');

// if i want to do masking
echo("cropping video.\n");
//run('ffmpeg -y -i piano_video.mp4 -filter:v "crop='.$vid_stream['width'].':'.$extracted_height.':0:'.$key_y_cordinates.'" piano_croped.mp4');

//
//echo("Extracting first frame.\n");
//run('ffmpeg -y -i piano_croped.mp4 -vf "select=eq(n\,0)" video_first_frame.png');

