# save this as app.py
from flask import Flask
from Classes import WeatherClass
from Classes.Air import AirQualityClass
import subprocess

app = Flask(__name__)

#
# Weather
#
@app.route("/weather", methods=['GET'])
def getWeather():
    return WeatherClass.Weather.getData()

#
# Flat watch
#
@app.route("/flat-watch", methods=['GET'])
def startFlatWatch():
    subprocess.call("/home/pi/server/live/status_room/run_watcher.sh", shell=True)
    return 'true'

#
# Air quality
#
@app.route("/air-quality", methods=['GET'])
def getAir():
   return AirQualityClass.AirQuality.getData()

if __name__ == '__main__':
    from waitress import serve
    serve(app, host="0.0.0.0", port=5000)
    #app.run(host='0.0.0.0', port=5000, debug=False)
