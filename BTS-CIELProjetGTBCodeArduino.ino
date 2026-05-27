#include "include/CAirQuality.h"
#include "include/CLuminosite.h"
#include "include/CESP32.h"
#include "include/CAffichage.h"

CAirQuality SCD_30(1, "I2C");
CLuminosite LightSensor(2, "A0");
CESP32 Wifi_PGTB("WIFI-PGTB_2.4Ghz", "BtsCielGTB@2026");
CAffichage ecran; 

void setup() {
  Serial.begin(9600);
  while(!Serial);
  
  delay(1000);

  Serial.println("Tentative de connexion WiFi...");
  Wifi_PGTB.initialiser();

  delay (1000);

  Serial.println("Initialisation du SCD30...");
  SCD_30.initialiser();
  
  Serial.print("Capteur ID: ");
  Serial.println(SCD_30.getId());
  Serial.print("Capteur Pin: ");
  Serial.println(SCD_30.getPin().c_str());
  
  delay (1000);
  
  Serial.println("Initialisation du Light Sensor");
  LightSensor.initialiser();

  Serial.print("Capteur ID: ");
  Serial.println(LightSensor.getId());
  Serial.print("Capteur Pin: ");
  Serial.println(LightSensor.getPin().c_str());
  
  delay(1000);
  
  Serial.println("Initialisation de l'écran");
  ecran.initialiser();   

  delay(1000);
 
  Serial.println("Systeme de monitoring pret.");
}

void loop() {
  if (!Wifi_PGTB.verifierConnexion()) {
    static unsigned long lastWifiCheck = 0;
    if (millis() - lastWifiCheck > 15000) {
      Serial.println("WiFi déconnecté, tentative de reconnexion...");
      Wifi_PGTB.connecter();
      lastWifiCheck = millis();
    }
  }


  if (SCD_30.getValues() && LightSensor.getValue()) 
  {
    float t = SCD_30.lireTemperature();
    float h = SCD_30.lireHumidity();
    float c = SCD_30.lireCO2();
    float l = LightSensor.lireLuminosite();

    // Affichage sur le moniteur série
    Serial.print("Temp: "); 
    Serial.print(t);
    Serial.print(" °C, Hum:"); 
    Serial.print(h); 
    Serial.print("CO2: "); 
    Serial.print(c);
    Serial.print(" ppm, Lux: "); 
    Serial.println(l);

    // Mise à jour de l'écran LCD
    ecran.afficherDonnees(t, h, c, l);
    ecran.alerteCO2(c);
  }

  delay(20000);
}