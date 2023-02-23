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


//crop out a part of the video
$key_y_cordinates = intval($vid_stream['height']*0.50);
$extracted_height = intval($vid_stream['height']*0.10);

//run('ffmpeg -i piano_video.mp4 -t '.$timecut.' -filter:v "crop='.$vid_stream['width'].':'.$extracted_height.':0:'.$key_y_cordinates.'" piano_out_imgs/output_%05d.png');

// if i want to do masking
echo("cropping video.\n");
run('ffmpeg -y -i piano_video.mp4 -filter:v "crop='.$vid_stream['width'].':'.$extracted_height.':0:'.$key_y_cordinates.'" -c:v libx264 -qp 0 piano_croped.mp4');

echo("Extracting and reducing colors\n");
run('ffmpeg -y -i piano_croped.mp4 -vf "palettegen=max_colors=10" palette.png');
run('ffmpeg -y -i piano_croped.mp4 -i palette.png -filter_complex "paletteuse=dither=none" -c:v libx264 -qp 0 reduced_colors.mp4');

echo("Generating images\n");
@mkdir('piano_out_imgs');
run('rm -r piano_out_imgs');
mkdir('piano_out_imgs');
run('ffmpeg -y -i reduced_colors.mp4 -t '.$timecut.' piano_out_imgs/output_%05d.png');

//
//echo("Extracting first frame.\n");
//run('ffmpeg -y -i piano_croped.mp4 -vf "select=eq(n\,0)" video_first_frame.png');

