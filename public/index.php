<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';

switch ($path) {
    case '/':
        if (!current_user()) {
            header('Location: /login');
            exit;
        }
        require __DIR__ . '/../app/views/calendar.php';
        break;

    case '/login':
        if ($method === 'POST') {
            handle_login();
        } else {
            require __DIR__ . '/../app/views/login.php';
        }
        break;

    case '/logout':
        session_destroy();
        header('Location: /login');
        break;

    case '/calendar':
        require_auth();
        require __DIR__ . '/../app/views/calendar.php';
        break;

    case '/events':
        require_auth();
        if ($method === 'GET') {
            handle_events_index();
        } elseif ($method === 'POST') {
            handle_events_store();
        } else {
            http_response_code(405);
        }
        break;

    case '/students':
        require_auth();
        handle_student_search();
        break;

    default:
        http_response_code(404);
        echo '404 Not Found';
        break;
}

function handle_login(): void
{
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $_SESSION['flash'] = 'Ongeldig e-mailadres of wachtwoord.';
        header('Location: /login');
        return;
    }

    $stmt = db()->prepare('SELECT id, name, email, role, password FROM users WHERE email = :email');
    $stmt->execute(['email' => strtolower($email)]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        $_SESSION['flash'] = 'Ongeldig e-mailadres of wachtwoord.';
        header('Location: /login');
        return;
    }

    unset($user['password']);
    $_SESSION['user'] = $user;

    header('Location: /calendar');
}

function handle_events_index(): void
{
    if (($_GET['meta'] ?? '') === 'instructors') {
        $user = current_user();
        if (is_admin($user)) {
            $stmt = db()->prepare("SELECT id, name FROM users WHERE role = 'instructeur' ORDER BY name");
            $stmt->execute();
            $instructors = $stmt->fetchAll();
        } else {
            $instructors = [['id' => $user['id'], 'name' => $user['name']]];
        }

        json_response(['instructors' => $instructors]);
        return;
    }

    $start = $_GET['start'] ?? null;
    $end = $_GET['end'] ?? null;

    $conditions = [];
    $params = [];

    if ($start) {
        $conditions[] = 'start_time >= :start';
        $params['start'] = $start;
    }

    if ($end) {
        $conditions[] = 'end_time <= :end';
        $params['end'] = $end;
    }

    $user = current_user();
    if (is_instructor($user)) {
        $conditions[] = 'instructor_id = :instructor';
        $params['instructor'] = $user['id'];
    }

    $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
    $sql = "SELECT events.*, students.name AS student_name, students.email AS student_email, students.phone AS student_phone, \
                   students.vehicle AS student_vehicle, students.package AS student_package, instructors.name AS instructor_name\
            FROM events\
            JOIN students ON students.id = events.student_id\
            JOIN users AS instructors ON instructors.id = events.instructor_id\
            $where\
            ORDER BY start_time";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    $events = [];
    while ($row = $stmt->fetch()) {
        $events[] = [
            'id' => $row['id'],
            'title' => $row['student_name'],
            'start' => $row['start_time'],
            'end' => $row['end_time'],
            'location' => $row['location'],
            'description' => $row['notes'],
            'vehicle' => $row['vehicle'],
            'package' => $row['package'],
            'instructor' => $row['instructor_name'],
            'student' => [
                'id' => $row['student_id'],
                'name' => $row['student_name'],
                'email' => $row['student_email'],
                'phone' => $row['student_phone'],
                'vehicle' => $row['student_vehicle'],
                'package' => $row['student_package'],
            ],
        ];
    }

    json_response(['events' => $events]);
}

function handle_events_store(): void
{
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);

    if (!is_array($payload)) {
        json_response(['message' => 'Ongeldig verzoek.'], 422);
    }

    $start = $payload['start'] ?? null;
    $end = $payload['end'] ?? null;
    $studentId = (int)($payload['student_id'] ?? 0);
    $vehicle = sanitize_string($payload['vehicle'] ?? '');
    $package = sanitize_string($payload['package'] ?? '');
    $location = sanitize_string($payload['location'] ?? '');
    $notes = sanitize_string($payload['description'] ?? '');

    if (!$start || !$end || !$studentId) {
        json_response(['message' => 'Start, eindtijd en leerling zijn verplicht.'], 422);
    }

    if (strtotime($start) >= strtotime($end)) {
        json_response(['message' => 'Eindtijd moet na starttijd liggen.'], 422);
    }

    $instructorId = null;
    $user = current_user();

    if (is_admin($user)) {
        $instructorId = (int)($payload['instructor_id'] ?? 0);
        if (!$instructorId) {
            json_response(['message' => 'Kies een instructeur.'], 422);
        }
    } else {
        $instructorId = $user['id'];
    }

    $stmt = db()->prepare('SELECT id FROM students WHERE id = :id');
    $stmt->execute(['id' => $studentId]);
    if (!$stmt->fetchColumn()) {
        json_response(['message' => 'Leerling niet gevonden.'], 404);
    }

    $stmt = db()->prepare('INSERT INTO events (instructor_id, student_id, start_time, end_time, vehicle, package, location, notes)
                           VALUES (:instructor_id, :student_id, :start_time, :end_time, :vehicle, :package, :location, :notes)');

    $stmt->execute([
        'instructor_id' => $instructorId,
        'student_id' => $studentId,
        'start_time' => $start,
        'end_time' => $end,
        'vehicle' => $vehicle,
        'package' => $package,
        'location' => $location,
        'notes' => $notes,
    ]);

    json_response(['message' => 'Ingepland!']);
}

function handle_student_search(): void
{
    $query = sanitize_string($_GET['q'] ?? '');
    $stmt = db()->prepare('SELECT id, name, email, phone, vehicle, package FROM students WHERE name LIKE :query ORDER BY name LIMIT 15');
    $stmt->execute(['query' => "%$query%"]);

    json_response(['students' => $stmt->fetchAll()]);
}
