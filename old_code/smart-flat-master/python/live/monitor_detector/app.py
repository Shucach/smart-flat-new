#!/usr/bin/python3
import os, stat
import RPi.GPIO as GPIO
import time
import urllib3
import picamera
import board
import neopixel
import json
from datetime import datetime

http = urllib3.PoolManager()

GPIO.setmode(GPIO.BCM)
PIR_PIN = 24
GPIO.setup(PIR_PIN, GPIO.IN)

videoPath = '/home/pi/server/videos/'

# TODO: to class
def createVideo():
	data = datetime.strftime(datetime.now(), "%d.%m.%Y-%H:%M:%S")
	videoName = 'video-'+ data

	try:
		camera = picamera.PiCamera()
		camera.start_recording(videoPath + videoName +'.h264')
		camera.wait_recording(5)
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

def toggleLight(status = 0, color = {'r': 0, 'g': 0, 'b': 0}):
    pixels = neopixel.NeoPixel(board.D12, 2)
    if status == 1:
        pixels.fill((color['r'], color['g'], color['b']))
    else:
        pixels.fill((color['r'], color['g'], color['b']))
    return

# Run loop for watch any action in room
time.sleep(2)
timeLightOff = 0
while True:
    try :
        if GPIO.input(PIR_PIN):
            # [status, color]
            response = http.request('GET', "https://sf.vanzzo.net/security/options", fields={}, headers={
                'Auth': 'b7568d073e50e2dfdf87b8db9c812611'
            })
            response = json.loads(response.data.decode('utf-8'))

            # light ON
            timeLightOff = 20
            toggleLight(1, response['color']['rgb'])

            # If alarm create video & send all to server
            if (response['status'] == '1'):
                videoName = createVideo()
                with open(videoPath + videoName, 'rb') as fp:
                    file_data = fp.read()

                http.request('POST', "https://sf.vanzzo.net/send/all/info", fields={
                    'status': '1',
                    'video': (videoName, file_data),
                }, headers={
                    'Auth': 'b7568d073e50e2dfdf87b8db9c812611'
                })

        # light OFF
        if timeLightOff == 0:
            toggleLight(0)
        else:
            timeLightOff = timeLightOff - 1

        time.sleep(0.5)
    except Exception as e :
        print("error data")
        print(e)
