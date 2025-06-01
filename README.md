# sportify#include <Arduino.h>
#include <Wire.h>
#include <math.h>
#include <Adafruit_LIS3MDL.h>
#include <Adafruit_Sensor.h>

Adafruit_LIS3MDL lis3mdl;

// === Définition des broches ===

#define IN_1_D 19
#define IN_2_D 18
#define IN_1_G 17
#define IN_2_G 16

// === Initialisation du magnétomètre ===
void initialiserMagnetometre() {
  if (!lis3mdl.begin_I2C(0x1E, &Wire)) {
    Serial.println("Erreur: capteur non détecté !");
    while (1);
  }
  lis3mdl.setPerformanceMode(LIS3MDL_MEDIUMMODE);
  lis3mdl.setOperationMode(LIS3MDL_CONTINUOUSMODE);
  lis3mdl.setDataRate(LIS3MDL_DATARATE_155_HZ);
  lis3mdl.setRange(LIS3MDL_RANGE_4_GAUSS);
  Serial.println("Capteur initialisé avec succès !");
}

// === Moyenne glissante de la direction ===
float moyenne_heading() {
  float somme = 0;
  for (int i = 0; i < 10; i++) {
    sensors_event_t event;
    lis3mdl.getEvent(&event);
    float x = event.magnetic.x;
    float y = event.magnetic.y;
    float angle = atan2(y, x) * 180.0 / PI;
    if (angle < 0) angle += 360;
    somme += angle;
    delay(5);
  }
  return somme / 10.0;
}

// === Contrôle moteur ===
void stop_motors() {
  analogWrite(IN_1_D, 0);
  analogWrite(IN_2_D, 0);
  analogWrite(IN_1_G, 0);
  analogWrite(IN_2_G, 0);
  Serial.println("Moteurs arrêtés");
}

void move_circle_left(int pwm) {
  digitalWrite(EN_D, HIGH);
  digitalWrite(EN_G, HIGH);
  analogWrite(IN_1_D, pwm);
  analogWrite(IN_2_D, 0);
  analogWrite(IN_1_G, pwm);
  analogWrite(IN_2_G, 0);
}

void move_circle_right(int pwm) {
  digitalWrite(EN_D, HIGH);
  digitalWrite(EN_G, HIGH);
  analogWrite(IN_1_D, 0);
  analogWrite(IN_2_D, pwm);
  analogWrite(IN_1_G, 0);
  analogWrite(IN_2_G, pwm);
}

// === Séquence : Orientation vers le Nord uniquement ===
void fonction_north() {
  Serial.println("Début de l'orientation vers le Nord...");

  float erreur = moyenne_heading();
  float kP = 2.0;
  unsigned long start_time = millis();

  while ((erreur > 5 && erreur < 355) && (millis() - start_time < 8000)) {
    erreur = moyenne_heading();
    float pwm = constrain(abs(kP * erreur), 80, 200);

    if (erreur < 180) {
      move_circle_left(pwm);
    } else {
      move_circle_right(pwm);
    }

    delay(20);
  }

  stop_motors();
  Serial.println("Orientation terminée.");
}

// === Setup général ===
void setup() {
  Serial.begin(115200);
  Wire.begin();

  pinMode(EN_D, OUTPUT);
  pinMode(EN_G, OUTPUT);
  pinMode(IN_1_D, OUTPUT);
  pinMode(IN_2_D, OUTPUT);
  pinMode(IN_1_G, OUTPUT);
  pinMode(IN_2_G, OUTPUT);

  initialiserMagnetometre();
  delay(1000);

  fonction_north(); // Lancement
}

// === Boucle vide ===
void loop() {
}
