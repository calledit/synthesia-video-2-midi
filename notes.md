
# Genrrate first frame
ffmpeg -i piano_video.mp4 -vf "select=eq(n\,0)" video_first_frame.png

# subtract first frame
ffmpeg \
   -i video_first_frame.png \
   -i piano_video.mp4 \
   -filter_complex \
   "
      color=#000000:size=1280x720 [matte];
      [1:0] format=rgb24, split[mask][video];
      [0:0][mask] blend=all_mode=difference, 
         format=rgb24 [mask];
      [matte][video][mask] maskedmerge,format=rgb24
   " \
   -shortest \
   -pix_fmt yuv422p \
   result.mkv
