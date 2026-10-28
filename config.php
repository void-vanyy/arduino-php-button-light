<?php

define('ARDUINO_PORT', 'COM3');
define('ARDUINO_BAUD', 9600);
define('STATE_FILE', __DIR__ . '/state.json');

function defaultState(): array
{
    return ['light' => 'OFF', 'events' => []];
}

function loadState(): array
{
    if (!is_file(STATE_FILE)) {
        return defaultState();
    }

    $data = json_decode((string) file_get_contents(STATE_FILE), true);

    if (!is_array($data)) {
        return defaultState();
    }

    return array_merge(defaultState(), $data);
}

function saveState(array $state): void
{
    file_put_contents(
        STATE_FILE,
        json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        LOCK_EX
    );
}

function recordEvent(array &$state, string $source, string $message, bool $ok): void
{
    array_unshift($state['events'], [
        'time'    => date('H:i:s'),
        'source'  => $source,
        'message' => $message,
        'ok'      => $ok,
    ]);

    $state['events'] = array_slice($state['events'], 0, 30);
}

function configureArduinoSerial(): array
{
    $port = strtoupper(trim(ARDUINO_PORT));

    if (!preg_match('/^COM([1-9][0-9]*)$/', $port)) {
        return ['success' => false, 'message' => "Invalid COM port: $port"];
    }

    // Windows MODE command. Do not use escapeshellarg() here.
    $command = 'mode ' . $port .
               ' BAUD=' . (int) ARDUINO_BAUD .
               ' PARITY=N DATA=8 STOP=1 XON=OFF';

    $output = [];
    $exitCode = 0;

    exec($command . ' 2>&1', $output, $exitCode);

    if ($exitCode !== 0) {
        $details = trim(implode(' ', $output));

        return [
            'success' => false,
            'message' => "Windows could not configure $port." .
                         ($details !== '' ? " Windows says: $details" : ''),
        ];
    }

    return ['success' => true, 'message' => 'Serial port configured.'];
}

function sendArduinoCommand(string $command): array
{
    if (!in_array($command, ['0', '1'], true)) {
        return ['success' => false, 'message' => 'Invalid Arduino command.'];
    }

    $configured = configureArduinoSerial();

    if (!$configured['success']) {
        return $configured;
    }

    $port = strtoupper(trim(ARDUINO_PORT));

    // Single-quoted \\ = one backslash => Windows device path \\.\COM3
    $device = '\\\\.\\' . $port;

    $handle = @fopen($device, 'w+b');

    if ($handle === false) {
        return [
            'success' => false,
            'message' => "Could not open $port. Close the Serial Monitor and other serial programs.",
        ];
    }

    // Opening the port resets an Arduino Uno: wait for the sketch to boot.
    usleep(50000);

    $written = @fwrite($handle, $command);
    fflush($handle);
    usleep(10000);
    fclose($handle);

    if ($written === false || $written < 1) {
        return [
            'success' => false,
            'message' => "Opened $port but the write failed.",
        ];
    }

    return [
        'success' => true,
        'message' => $command === '1' ? 'Byte 0x31 sent -> LIGHT ON' : 'Byte 0x30 sent -> LIGHT OFF',
    ];
}
