/*
 * Communications logic for the development module, including MQTT setup, 
 * message handling, and periodic updates.
 * 
 * NOTE: Avoid modifying the core routines unless required for custom network protocols.
 */

#include <WiFi.h>
#include <PubSubClient.h>
#include "sensitiveInformation.h" // Ensure network credentials are set correctly

// MQTT client setup
WiFiClient espClient;
PubSubClient client(espClient);
String topicBuffer;

// MQTT Broker configuration (Default MQTT port is 1883)
const char* mqttServer = "192.168.68.104";  
const int mqttPort = 1883;

unsigned long lastUpdate = 0;
const unsigned long updateInterval = 5000; // Interval between periodic updates (5000 ms)

void performActionBasedOnPayload(String payload);

void wifiSetup()
{
    WiFi.begin(ssid, password);

    while (WiFi.status() != WL_CONNECTED)
    {
        delay(1000);
        Serial.println("Connecting to Wi-Fi...");
    }
    Serial.println();
    Serial.print("Connected to Wi-Fi. Local IP address: ");
    Serial.println(WiFi.localIP());
}

/*
 * Helper function to publish data back to the MQTT broker.
 * Example: sendDataToServer("challenges/Status", "Task Completed");
 */
void sendDataToServer(String topic, String message)
{
    if (client.connected())
    {
        Serial.print("Sending message to topic [");
        Serial.print(topic);
        Serial.print("]: ");
        Serial.println(message);

        // Convert String to char array for the PubSubClient library
        client.publish(topic.c_str(), message.c_str());
    }
    else
    {
        Serial.println("Send failed: MQTT not connected.");
    }
}

void sendPeriodicUpdate(String topic, String dataToSend)
{
    // Timer check: verify if the interval has elapsed
    unsigned long now = millis();
    if (now - lastUpdate > updateInterval)
    {
        lastUpdate = now; // Reset timer

        // Construct unique topic: "updateChallenges/<CLIENT_NAME>"
        String updateTopic = topic + "/" + String(mqttClient);

        // Transmit payload
        sendDataToServer(updateTopic, dataToSend);
    }
}

void callback(char *topic, byte *payload, unsigned int length)
{
    String message = "";
    for (int i = 0; i < length; i++)
    {
        message += (char)payload[i];
    }

    String internalPrefix = "__INTERNAL__";
    if (message.startsWith(internalPrefix))
    {
        message = message.substring(internalPrefix.length());
    }

    Serial.print("Message arrived [");
    Serial.print(topic);
    Serial.print("] ");
    Serial.println(message);

    performActionBasedOnPayload(message);
}

void mqttConnect()
{
    while (!client.connected())
    {
        Serial.println("Connecting to MQTT...");
        if (client.connect(mqttClient))
        {
            Serial.println("Connected to MQTT broker.");
            client.subscribe(mqttTopic);
            sendDataToServer("EventLog", String(mqttClient) + " is online.");
        }
        else
        {
            Serial.print("Failed, rc=");
            Serial.print(client.state());
            Serial.println(" - retrying in 2 seconds...");
            delay(2000);
        }
    }
}

void mqttSetup()
{
    // Construct topic name dynamically
    topicBuffer = "challenges/" + String(mqttClient);
    mqttTopic = topicBuffer.c_str();

    client.setServer(mqttServer, mqttPort);
    client.setCallback(callback);
    mqttConnect();
}