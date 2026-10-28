/*
  Button -> Arduino Light Controller

  Toggle a light (relay module or LED) with a push button.

  Wiring:
    - Button: pin 2 to GND (uses internal pull-up, no external resistor needed)
    - Light:  pin 13 (built-in LED) or pin 7 for a relay module
              (relay IN -> pin 7, VCC -> 5V, GND -> GND)

  Serial commands (same style as the LED controller):
    1 = light ON
    0 = light OFF

  Serial output:
    "LIGHT ON" / "LIGHT OFF" whenever the state changes.
*/

const int BUTTON_PIN = 2;    // push button (to GND)
const int LIGHT_PIN  = 13;   // built-in LED, or change to 7 for a relay
const unsigned long DEBOUNCE_MS = 50;  // debounce time in ms

int lightState = LOW;              // current light state
int lastButtonReading = HIGH;      // last raw reading (pull-up => HIGH when open)
int lastStableButton = HIGH;       // last debounced value
unsigned long lastDebounceTime = 0; // last time the input pin changed

void setup()
{
  pinMode(LIGHT_PIN, OUTPUT);
  digitalWrite(LIGHT_PIN, LOW);

  pinMode(BUTTON_PIN, INPUT_PULLUP); // internal pull-up, button reads LOW when pressed

  Serial.begin(9600);
  delay(500);

  Serial.println("READY");
}

void loop()
{
  readButton();
  readSerial();
}

void readButton()
{
  int reading = digitalRead(BUTTON_PIN);

  // restart the debounce timer whenever the raw reading changes
  if (reading != lastButtonReading)
  {
    lastDebounceTime = millis();
  }

  if ((millis() - lastDebounceTime) > DEBOUNCE_MS && reading != lastStableButton)
  {
    lastStableButton = reading;

    // falling edge (HIGH -> LOW) = button pressed
    if (lastStableButton == LOW)
    {
      setLight(lightState == LOW ? HIGH : LOW);
    }
  }

  lastButtonReading = reading;
}

void readSerial()
{
  if (Serial.available() > 0)
  {
    char command = Serial.read();

    if (command == '1')
    {
      setLight(HIGH);
    }
    else if (command == '0')
    {
      setLight(LOW);
    }
  }
}

void setLight(int state)
{
  if (state == lightState)
  {
    return; // no change
  }

  lightState = state;
  digitalWrite(LIGHT_PIN, lightState);

  Serial.println(lightState == HIGH ? "LIGHT ON" : "LIGHT OFF");
}
