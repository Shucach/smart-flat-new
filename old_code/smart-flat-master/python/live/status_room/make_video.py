#!/usr/bin/python
import os, stat
import RPi.GPIO as GPIO
import time
from bs4 import BeautifulSoup
import urllib3
import picamera
from datetime import datetime

http = urllib3.PoolManager()
videoPath = '/home/pi/server/videos/'

def create_video():
    data = datetime.strftime(datetime.now(), "%d.%m.%Y-%H:%M:%S")
    videoName = 'video-'+ data

    try:
        camera = picamera.PiCamera()
        camera.start_recording(videoPath + videoName +'.h264')
        camera.wait_recording(20)
        camera.stop_recording()

        #remove
        converter = 'avconv -i '+ videoPath + videoName +'.h264 -r 30 -vcodec copy '+ videoPath + videoName +'.mkv'
        remove = 'rm '+ videoPath + videoName +'.h264'
        os.system(converter)
        os.system(remove)

        os.chmod(videoPath + videoName +'.mkv', 0o777)
    finally:
        camera.close()

    return videoName + '.mkv'

try:
    videoName = create_video()

    with open(videoPath + videoName, 'rb') as fp:
        file_data = fp.read()

    http.request('POST', "https://sf.vanzzo.net/send/all/info", fields = {
        'status': '4',
        'video': (videoName, file_data),
    }, headers={
        'Auth': 'b7568d073e50e2dfdf87b8db9c812611'
    })

    os.system('rm '+ videoPath + videoName)
except KeyboardInterrupt:
	GPIO.cleanup()
