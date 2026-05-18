#include "include/CAirQuality.h"
#include "include/CLuminosite.h"
#include "include/CAffichage.h" // Ajout de la classe d'affichage

CAirQuality SCD_30(1, "I2C");
CLuminosite LightSensor(2, "A0");
CAffichage ecran; // Instanciation de l'écran

void setup() {
  Serial.begin(9600);
  delay(1000);
  while(!Serial);

  SCD_30.initialiser();
  LightSensor.initialiser();
  ecran.initialiser(); // Initialisation de l'écran

  Serial.println("Systeme de monitoring pret.");
}

void loop() {
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