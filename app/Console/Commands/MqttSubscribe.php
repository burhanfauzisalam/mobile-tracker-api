<?php

namespace App\Console\Commands;

use App\Models\TrackerMessage;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\Exceptions\MqttClientException;
use PhpMqtt\Client\Facades\MQTT;
use Throwable;

class MqttSubscribe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mqtt:subscribe
                            {topic=tracking/android/+/location : MQTT topic filter to subscribe to}
                            {connection? : MQTT connection name defined in config/mqtt-client.php}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listen to MQTT tracking topics and persist incoming payloads to the database.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $topic = (string) $this->argument('topic');
        $connectionName = $this->argument('connection') ?? config('mqtt-client.default_connection', 'default');

        $this->components->info(sprintf('Connecting using "%s" connection...', $connectionName));

        try {
            $client = MQTT::connection($connectionName);
        } catch (Throwable $exception) {
            $this->components->error('Unable to connect to MQTT broker: ' . $exception->getMessage());
            Log::error('MQTT connection failed', [
                'connection' => $connectionName,
                'error' => $exception->getMessage(),
            ]);

            return self::FAILURE;
        }

        $client->subscribe($topic, function (string $topic, string $message) {
            $this->persistPayload($topic, $message);
        }, 0);

        $this->components->info(sprintf('Subscribed to "%s". Waiting for messages...', $topic));

        try {
            $client->loop(true);
        } catch (Throwable $exception) {
            $this->components->error('MQTT loop stopped: ' . $exception->getMessage());
            Log::error('MQTT loop stopped unexpectedly', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return self::FAILURE;
        } finally {
            try {
                $client->interrupt();
                $client->disconnect();
            } catch (MqttClientException $exception) {
                Log::warning('Failed to disconnect MQTT client cleanly', [
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return self::SUCCESS;
    }

    /**
     * Persist a single MQTT payload into the database.
     */
    protected function persistPayload(string $topic, string $rawPayload): void
    {
        $data = json_decode($rawPayload, true);

        if (!is_array($data)) {
            Log::warning('Received non-JSON MQTT payload', [
                'topic' => $topic,
                'payload' => $rawPayload,
            ]);
            return;
        }

        $record = [
            'topic' => $topic,
            'device_id' => $this->stringValue($data, ['device_id', 'device']),
            'user' => $this->stringValue($data, ['user', 'username']),
            'latitude' => $this->floatValue($data, ['latitude', 'lat']),
            'longitude' => $this->floatValue($data, ['longitude', 'lng']),
            'accuracy' => $this->floatValue($data, ['accuracy', 'acc']),
            'speed' => $this->floatValue($data, ['speed']),
            'bearing' => $this->floatValue($data, ['bearing', 'direction']),
            'battery' => $this->intValue($data, ['battery', 'batt', 'battery_level']),
            'tracked_at' => $this->timestampValue($data, ['timestamp', 'ts', 'tracked_at']),
            'payload' => $data,
        ];

        try {
            TrackerMessage::create($record);
            $this->components->twoColumnDetail(
                'Stored payload',
                sprintf('%s (%s)', $record['device_id'] ?? '-', $record['user'] ?? '-')
            );
        } catch (Throwable $exception) {
            Log::error('Failed to persist MQTT payload', [
                'topic' => $topic,
                'payload' => $data,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function stringValue(array $payload, array $keys): ?string
    {
        $value = $this->valueForKeys($payload, $keys);

        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function floatValue(array $payload, array $keys): ?float
    {
        $value = $this->valueForKeys($payload, $keys);

        if ($value === null || !is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    protected function intValue(array $payload, array $keys): ?int
    {
        $value = $this->valueForKeys($payload, $keys);

        if ($value === null || !is_numeric($value)) {
            return null;
        }

        return max(0, min(100, (int) round((float) $value)));
    }

    protected function timestampValue(array $payload, array $keys): ?Carbon
    {
        $value = $this->valueForKeys($payload, $keys);

        if ($value === null) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value;
        }

        if (is_numeric($value)) {
            $numeric = (float) $value;

            if ($numeric > 1_000_000_000_000) {
                return Carbon::createFromTimestampMs((int) round($numeric));
            }

            return Carbon::createFromTimestamp((int) round($numeric));
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    protected function valueForKeys(array $payload, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }

            $value = $payload[$key];

            if ($value === '' || $value === null) {
                continue;
            }

            return $value;
        }

        return null;
    }
}
