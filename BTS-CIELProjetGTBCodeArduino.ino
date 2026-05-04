#include "include/CAirQuality.h"

CAirQuality SCD_30(1, 2);

void setup() {
  Serial.begin(9600);
  delay(3000);
  while(!Serial);
  SCD_30.initialiser();
  Serial.println("Initialisation du SCD30...");
  delay(3000);
  Serial.print("Capteur ID: ");
  Serial.println(SCD_30.getId());
  Serial.print("Capteur Pin: ");
  Serial.println(SCD_30.getPin());
}

void loop() {
  SCD_30.getValues();
  Serial.print("Temperature: "); 
  Serial.print(SCD_30.lireTemperature());
  Serial.print(" °C, Humidity: ");
  Serial.print(SCD_30.lireHumidity());
  Serial.print(" %, CO2: ");
  Serial.print(SCD_30.lireCO2());
  Serial.println(" ppm");
  delay(2000);
}