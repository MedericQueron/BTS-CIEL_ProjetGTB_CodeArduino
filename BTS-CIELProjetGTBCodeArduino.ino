#include "include/CAirQuality.h"
#include "include/CLuminosite.h"
#include "include/CESP32.h"

CAirQuality SCD_30(1, "I2C");
CLuminosite LightSensor(2, "A0");
CESP32 Wifi_PGTB("WIFI-PGTB_2.4Ghz", "BtsCielGTB@2026");

void setup() {
  Serial.begin(9600);
  delay(1000);
  while(!Serial);

  Serial.println("Tentative de connexion WiFi...");
  Wifi_PGTB.initialiser();

  delay (1000);

  Serial.println("Initialisation du SCD30...");
  SCD_30.initialiser();

  delay(1000);

  Serial.print("Capteur ID: ");
  Serial.println(SCD_30.getId());
  Serial.print("Capteur Pin: ");
  Serial.println(SCD_30.getPin().c_str());  

  delay(2000);

  Serial.println("Initialisation du Light Sensor...");
  LightSensor.initialiser();  

  delay(1000);

  Serial.print("Capteur ID: ");
  Serial.println(LightSensor.getId());
  Serial.print("Capteur Pin: ");
  Serial.println(LightSensor.getPin().c_str());

  delay(1000);
 
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
    Serial.print("Temperature: "); 
    Serial.print(SCD_30.lireTemperature());
    Serial.print(" °C, Humidity: ");
    Serial.print(SCD_30.lireHumidity());
    Serial.print(" %, CO2: ");
    Serial.print(SCD_30.lireCO2());
    Serial.print(" ppm, Luminositee: ");
    Serial.print(LightSensor.lireLuminosite());
    Serial.println(" lux");  
  }

  delay(1000);
}