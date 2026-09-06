#!/usr/bin/python
from time import sleep
import RPi.GPIO as GPIO
GPIO.setmode(GPIO.BCM)
GPIO.setwarnings(False)

SERVO_H = 16
SERVO_V = 20

GPIO.setup(SERVO_V, GPIO.OUT)
GPIO.setup(SERVO_H, GPIO.OUT)

def setServoAngle(servo, angle):
	pwm = GPIO.PWM(servo, 50)
	pwm.start(8)
	dutyCycle = angle / 18. + 2.
	pwm.ChangeDutyCycle(dutyCycle)
	sleep(0.3)
	pwm.stop()

def loopWatcher(maxVal, vAngle):
    stepVal = maxVal / 4
    angle = 0
    while angle <= maxVal:
        setServoAngle(SERVO_V, vAngle)
        setServoAngle(SERVO_H, angle)
        angle += stepVal
        sleep(1)

# Down
#h = 0  - 150
#v = 130
loopWatcher(150, 130)

# Middle
# h = 0 - 175
# v = 98
loopWatcher(175, 98)

# Top
# h = 0 - 160
# v = 50
#loopWatcher(160, 50)

# Default
setServoAngle(SERVO_V, 130)
setServoAngle(SERVO_H, 0)

GPIO.cleanup()
