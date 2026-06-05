#include "../include/CHTTP.h"
#include <ArduinoHttpClient.h>

#include "../include/CESP32.h"

#include <iostream>


using namespace std;
CHTTP::CHTTP(String serverHost, String serverPath) : _serverHost(serverHost), _serverPath(serverPath) {}// Constructeur qui initialise l'URL du serveur à laquelle les données seront envoyées

bool CHTTP::envoyerDonnees(float temperature, float humidite, int co2, int luminosite) const// Envoie les données au serveur via HTTP POST en format JSON
{
    if (WiFi.status() == WL_CONNECTED) {
        WiFiClient wifiClient;
        HttpClient http(wifiClient, _serverHost, 80);

        String trame = "{\"id_arduino\":\"ARD-001\","
                       "\"temperature\":" + String(temperature, 1) +
                       ",\"humidite\":" + String(humidite, 0) +
                       ",\"co2\":" + String(co2) +
                       ",\"luminosite\":" + String(luminosite) + "}";

        http.beginRequest();
        http.post(_serverPath);
        http.sendHeader("Content-Type", "application/json");
        http.sendHeader("X-GTB-Key", "d671f753fd4f0349fdf4f4daa2268a3e2ab4dd9506fd1ffaea5d62e4732f9a20");
        http.sendHeader("Content-Length", trame.length());
        http.endRequest();
        http.beginBody(); // On indique que les entêtes sont finis
        http.print(trame); // On envoie le JSON

        // --- NOUVEAU CODE DE DÉBOGAGE ---
        int httpResponseCode = http.responseStatusCode();
        String responseBody = http.responseBody(); // On lit le message de retour du PHP

        Serial.print("--- DEBUG HTTP --- \nCode HTTP : ");
        Serial.println(httpResponseCode);
        Serial.print("Reponse PHP : ");
        Serial.println(responseBody);
        Serial.println("------------------");
        // --------------------------------

        http.stop();

        return (httpResponseCode >= 200 && httpResponseCode < 300);
    }
    return false;
}