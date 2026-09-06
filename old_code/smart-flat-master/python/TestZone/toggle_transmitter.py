#!/usr/bin/python3

import RPi.GPIO as GPIO
import sys, traceback

#from time import sleep
#from re import findall
#from subprocess import check_output

GPIO.setwarnings(False)

def main(pin, state):
    print(pin)
    print(state)

    if state == 1:
        state = True
    else :
        state = False

    GPIO.setmode(GPIO.BCM)
    GPIO.setup(pin, GPIO.OUT, initial=0)
    GPIO.output(pin, state)

if __name__ == "__main__":
    main(int(sys.argv[1]), int(sys.argv[2]))
