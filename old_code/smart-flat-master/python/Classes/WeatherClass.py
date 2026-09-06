import Adafruit_DHT

DHT_SENSOR = Adafruit_DHT.DHT22

class Weather():
    def getData():

        tryGetLoop = True
        maxTry = 15
        while tryGetLoop :
            humidity, temperature = Adafruit_DHT.read_retry(DHT_SENSOR, 26)
            if(round(humidity) <= 100 or maxTry <= 0) :
                tryGetLoop = False
                maxTry = maxTry - 1

        return {
            'flat': {
                'temperature': round(temperature),
                'humidity': round(humidity),
            },
            'street': {
                'temperature': 0,
                'humidity': 0,
            }
        }
