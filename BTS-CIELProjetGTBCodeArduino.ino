#include "include/CAirQuality.h"
#include "include/CLuminosite.h"

CAirQuality SCD_30(1, "I2C");
CLuminosite LightSensor(2, "A0");

void setup() {
  Serial.begin(9600);
  delay(1000);
  while(!Serial);

  Serial.println("Initialisation du SCD30...");
  SCD_30.initialiser();

  delay(1000);

  Serial.print("Capteur ID: ");
  Serial.println(SCD_30.getId());
  Serial.print("Capteur Pin: ");
  Serial.println(SCD_30.getPin().c_str());  

  delay(2000);

  Serial.println("Initialisation du Light Senror...");
  LightSensor.initialiser();  

  delay(1000);

  Serial.print("Capteur ID: ");
  Serial.println(LightSensor.getId());
  Serial.print("Capteur Pin: ");
  Serial.println(LightSensor.getPin().c_str());

  delay(1000);
 
}

void loop() {
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

  delay(3000);
}