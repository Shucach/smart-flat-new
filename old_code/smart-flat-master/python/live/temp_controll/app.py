#!/usr/bin/python
# -*- coding: utf-8 -*-

import RPi.GPIO as GPIO
import sys, traceback

from time import sleep
from re import findall
from subprocess import check_output

def get_temp():
    temp = check_output(["vcgencmd","measure_temp"]).decode()
    temp = float(findall('\d+\.\d+', temp)[0])
    return(temp)

try:
    tempOn = 45
    controlPin = 21
    pinState = False


    GPIO.setmode(GPIO.BCM)
    GPIO.setup(controlPin, GPIO.OUT, initial=0)

    while True:
        temp = get_temp()

        if temp > tempOn and not pinState or temp < tempOn - 10 and pinState:
            pinState = not pinState
            GPIO.output(controlPin, pinState)

        print(str(temp) + "  " + str(pinState))
        sleep(1)

except KeyboardInterrupt:
    # ...
    print("Exit pressed Ctrl+C")
except:
    traceback.print_exc(limit=2, file=sys.stdout)
finally:
    GPIO.cleanup()
