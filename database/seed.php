<?php

declare(strict_types=1);

const DB_PATH = __DIR__ . '/app.sqlite';

if (!file_exists(DB_PATH)) {
    exit("Database ontbreekt. Run eerst php database/migrate.php\n");
}

$pdo = new PDO('sqlite:' . DB_PATH);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec('DELETE FROM users');
$pdo->exec('DELETE FROM students');
$pdo->exec('DELETE FROM events');

$users = [
    ['name' => 'Alex Admin', 'email' => 'admin@example.com', 'role' => 'admin'],
    ['name' => 'Iris Instructeur', 'email' => 'iris@example.com', 'role' => 'instructeur'],
    ['name' => 'Bram Begeleider', 'email' => 'bram@example.com', 'role' => 'instructeur'],
];

$insertUser = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)');
foreach ($users as $user) {
    $insertUser->execute([
        'name' => $user['name'],
        'email' => strtolower($user['email']),
        'password' => password_hash('secret123', PASSWORD_BCRYPT),
        'role' => $user['role'],
    ]);
}

$students = [
    ['name' => 'Lisa Janssen', 'email' => 'lisa.janssen@example.com', 'phone' => '+31 6 1234 5678', 'vehicle' => 'Automaat', 'package' => 'Spoed', 'location' => 'Rotterdam', 'description' => 'Wil focussen op parkeren.'],
    ['name' => 'Noah Visser', 'email' => 'noah.visser@example.com', 'phone' => '+31 6 8765 4321', 'vehicle' => 'Handgeschakeld', 'package' => 'Basis', 'location' => 'Den Haag', 'description' => 'Voorbereiding tussentijdse toets.'],
    ['name' => 'Sara Willems', 'email' => 'sara.willems@example.com', 'phone' => '+31 6 2468 1357', 'vehicle' => 'Automaat', 'package' => 'Compleet', 'location' => 'Delft', 'description' => 'Heeft moeite met snelwegen.'],
    ['name' => 'Daan Groen', 'email' => 'daan.groen@example.com', 'phone' => '+31 6 1357 2468', 'vehicle' => 'Handgeschakeld', 'package' => 'Intensief', 'location' => 'Schiedam', 'description' => 'Extra sessies voor bijzondere verrichtingen.'],
];

$insertStudent = $pdo->prepare('INSERT INTO students (name, email, phone, vehicle, package, location, description) VALUES (:name, :email, :phone, :vehicle, :package, :location, :description)');
foreach ($students as $student) {
    $insertStudent->execute($student);
}

// Create a couple of sample events for immediate visualization
$instructors = $pdo->query('SELECT id, name FROM users WHERE role = "instructeur" ORDER BY id')->fetchAll();
$studentsRows = $pdo->query('SELECT id FROM students')->fetchAll(PDO::FETCH_COLUMN);

$now = new DateTimeImmutable('monday this week');
$eventStatement = $pdo->prepare('INSERT INTO events (instructor_id, student_id, start_time, end_time, vehicle, package, location, notes) VALUES (:instructor_id, :student_id, :start_time, :end_time, :vehicle, :package, :location, :notes)');

$sampleEvents = [
    ['dayOffset' => 1, 'hour' => 9, 'duration' => 90, 'student' => $studentsRows[0], 'instructor' => $instructors[0]['id'], 'vehicle' => 'Automaat', 'package' => 'Spoed', 'location' => 'Rotterdam Centrum', 'notes' => 'Volledig verkeersinzicht.'],
    ['dayOffset' => 2, 'hour' => 13, 'duration' => 120, 'student' => $studentsRows[1], 'instructor' => $instructors[1]['id'], 'vehicle' => 'Handgeschakeld', 'package' => 'Basis', 'location' => 'Den Haag Zuid', 'notes' => 'Snelwegtraining.'],
    ['dayOffset' => 4, 'hour' => 10, 'duration' => 90, 'student' => $studentsRows[2], 'instructor' => $instructors[0]['id'], 'vehicle' => 'Automaat', 'package' => 'Compleet', 'location' => 'Delft Noord', 'notes' => 'Bijzondere verrichtingen.'],
];

foreach ($sampleEvents as $event) {
    $start = $now->modify("+{$event['dayOffset']} day")->setTime($event['hour'], 0);
    $end = $start->modify("+{$event['duration']} minutes");
    $eventStatement->execute([
        'instructor_id' => $event['instructor'],
        'student_id' => $event['student'],
        'start_time' => $start->format('Y-m-d\TH:i'),
        'end_time' => $end->format('Y-m-d\TH:i'),
        'vehicle' => $event['vehicle'],
        'package' => $event['package'],
        'location' => $event['location'],
        'notes' => $event['notes'],
    ]);
}

echo "Database seeded met gebruikers en leerlingen." . PHP_EOL;
