#!/bin/bash

#download video #use bestvideo to get high res version
youtube-dl -f best https://www.youtube.com/watch?v=_wS-elf2Jeo

#extract dimensions of video
ffprobe "I See You - Leona Lewis (Piano Tutorial) by Aldy Santos-dy6tNlQi4Ek.mp4"

#Crop out the desired part The hight needs to be min 4 pixels or the crop will fail the location of the keys needs to be calculated but here we use the value 300
#Crop can be 2 if you export as image
ffmpeg -i "I See You - Leona Lewis (Piano Tutorial) by Aldy Santos-dy6tNlQi4Ek.mp4" -filter:v "crop=640:2:0:300" piano_out_imgs/output_%05d.png


#ffmpeg -i "I See You - Leona Lewis (Piano Tutorial) by Aldy Santos-dy6tNlQi4Ek.mp4" -filter:v "crop=640:4:0:300" -c:a copy out.mp4

#ffmpeg -i "I See You - Leona Lewis (Piano Tutorial) by Aldy Santos-dy6tNlQi4Ek.mp4" -filter:v "crop=1920:2:0:900" -c:a copy out.mp4
