#include "../include/CHTTP.h"
#include <ArduinoHttpClient.h>

#include "../include/CESP32.h"

#include <iostream>


using namespace std;
CHTTP::CHTTP(String url) : _serverUrl(url) {}// Constructeur qui initialise l'URL du serveur à laquelle les données seront envoyées

bool CHTTP::envoyerDonnees(float temperature, float humidite, int co2, int luminosite) const// Envoie les données au serveur via HTTP POST en format JSON
{
    if (WiFi.status() == WL_CONNECTED) {
        WiFiClient wifiClient;
        HttpClient http(wifiClient, _serverUrl, 80);

        String trame = "{\"temperature\":" + String(temperature, 1) +
                       ",\"humidite\":" + String(humidite, 0) +
                       ",\"co2\":" + String(co2) +
                       ",\"luminosite\":" + String(luminosite) +
                       "}";

        http.post("/", "application/json", trame);
        int httpResponseCode = http.responseStatusCode();
        http.stop();

        return (httpResponseCode >= 200 && httpResponseCode < 300);
    }
    return false;
}