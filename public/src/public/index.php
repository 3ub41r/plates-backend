<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';
spl_autoload_register(function ($classname) {
    require ("../classes/$classname.php");
});

$config['displayErrorDetails'] = true;

$app = new \Slim\App(['settings' => $config]);

// Get IOC Container
$container = $app->getContainer();

// Inject logger
$container['logger'] = function($c) {
    $logger = new \Monolog\Logger('my_logger');
    $file_handler = new \Monolog\Handler\StreamHandler('../../logs/app.log', Logger::DEBUG);
    $logger->pushHandler($file_handler);
    return $logger;
};

// Inject Database
$container['db'] = function ($c) {
    $db_path = '../../sql';
    $db_file = 'students.sqlite';

    if (!file_exists($db_path)) {
        mkdir($db_path, 0777, true);
    }

    $pdo = new PDO("sqlite:$db_path/$db_file");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $pdo->exec('DROP TABLE IF EXISTS student');
    $pdo->exec('DROP TABLE IF EXISTS vehicle');

    // Create tables
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS student (
        id INTEGER PRIMARY KEY,
        name TEXT,
        matric_no TEXT,
        ic_number TEXT,
        address TEXT,
        phone_num INTEGER)
        ');

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS vehicle (
        id INTEGER PRIMARY KEY,
        plate_number TEXT,
        type TEXT,
        color TEXT,
        chasis_num TEXT,
        model TEXT,
        student_id INTEGER)
        ');

    // Insert some data
    $sql = '
    INSERT INTO student
    (name, matric_no, ic_number, address, phone_num)
    VALUES
    (?, ?, ?, ?, ?)
    ';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['Ahmad', 'SX312123123', '900401715132', 'Taman Desa', '0104149423']);
    $stmt->execute(['Abu', 'Ai130080', '940211015246', 'Taman Intan', '0127414535']);

    $student_id = $pdo->lastInsertId();

    $sql = '
    INSERT INTO vehicle
    (plate_number, type, color, chasis_num, model, student_id)
    VALUES
    (?, ?, ?, ?, ?, ?)
    ';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['BJY6688', 'Car', 'Black', 'NF5123125666', 'FN2345', $student_id]);
    $stmt->execute(['WAJ4', 'Car', 'Black', 'NF512546226', 'VB2522', $student_id]);

    return $pdo;
};

// End-point for plate recognition
$app->post('/recognize', function (Request $request, Response $response) {
    $this->logger->addInfo('Getting plate number from API.');
    $plate = new PlateRecognition($_FILES, 'image');
    $plate_number = $plate->get_plate_number();

    if ($plate_number) {
        $this->logger->addInfo("Looking up plate number ($plate_number) in database.");
        // Look up student's vehicle
        $sql = '
        SELECT * FROM vehicle a
        JOIN student b on b.id = a.student_id
        WHERE plate_number = ?
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$plate_number]);
        $student = $stmt->fetch();

        if ($student) {
            $this->logger->addInfo("Plate number found in database.", $student);
            return $response->withJson($student);
        }
    }

    return $response->withJson([
        'message' => 'Vehicle with that plate was not found.',
        'plate_number' => $plate_number
        ], 404);
});
$app->run();
