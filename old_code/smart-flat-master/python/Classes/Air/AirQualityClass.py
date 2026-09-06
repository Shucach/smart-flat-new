from Classes.Air.mq import *
import sys, time

# ppm (parts per million)
class AirQuality():
    def getData():
        dataReturn = {
            'lpg': 0.00,
            'co': 0.00,
            'smoke': 0.00,
        }

        mq = MQ()
        tLpg = 0.00
        tCo = 0.00
        tSmoke = 0.00
        getIteratorDate = 3
        while getIteratorDate > 0:
            perc = mq.MQPercentage()
            tLpg = perc["GAS_LPG"]
            tCo = perc["CO"]
            tSmoke = perc["SMOKE"]
            getIteratorDate = getIteratorDate - 1
            time.sleep(3)

        dataReturn['lpg'] = tLpg
        dataReturn['co'] = tCo
        dataReturn['smoke'] = tSmoke

        return dataReturn
