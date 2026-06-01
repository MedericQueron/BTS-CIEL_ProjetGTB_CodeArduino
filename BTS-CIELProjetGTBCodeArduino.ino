#include "include/CArduino.h"

CAirQuality SCD_30(1, "I2C");
CLuminosite LightSensor(2, "A0");
CESP32 Wifi_PGTB("WIFI-PGTB_2.4Ghz", "BtsCielGTB@2026");
CAffichage ecran;
CHTTP httpClient("http://"); 

CArduino arduino(1, SCD_30, LightSensor, Wifi_PGTB, ecran, httpClient);

void setup() {
  Serial.begin(9600);
  while(!Serial);
  arduino.initialiser();
  
}

void loop() {
  arduino.lireCapteurs();
  arduino.afficherDonnees();
  
  while (arduino.getIsConnected() == false)
  {
    arduino.connexion();
  }  
  arduino.envoyerDonnees();
  delay (600000); 
}
