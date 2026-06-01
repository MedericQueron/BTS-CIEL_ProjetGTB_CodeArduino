#include "../include/CHTTP.h"
#include <iostream>


using namespace std;
CHTTP::CHTTP(String url) : _serverUrl(url) {}// Constructeur qui initialise l'URL du serveur à laquelle les données seront envoyées

bool CHTTP::envoyerDonnees(float temperature, float humidite, int co2, int luminosite)  // Envoie les données au serveur via HTTP POST en format JSON
{
    if (WiFi.status() == WL_CONNECTED) {
        HTTPClient http;
        http.begin(_serverUrl);
        http.addHeader("Content-Type", "application/json");

        String trame =  "{\"temperature\":" + String(temperature, 1) +
                        ",\"humidite\":" + String(humidite, 0) +
                        ",\"co2\":" + String(co2) +
                        ",\"luminosite\":" + String(luminosite) + 
                        "}";

        int httpResponseCode = http.POST(trame);
        http.end();

        return (httpResponseCode > 0 && httpResponseCode < 300);
    }
    return false;
}