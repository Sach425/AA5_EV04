<?php

$host = "localhost";
$usuario = "root";
$password = "";
$basededatos = "api";

// Conexión a la base de datos
$conn = mysqli_connect($host, $usuario, $password, $basededatos);

// Chequear la conexión
if (!$conn) {
    die("Conexión fallida: " . mysqli_connect_error());
}

// Establecer el encabezado de respuesta como JSON
header("Content-Type: application/json");

// Obtener el método de la solicitud
$metodo = $_SERVER["REQUEST_METHOD"];

// Obtener el ID de la URL si está presente
$path = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '/';
$buscarID = explode('/', $path);
$id = isset($_GET['id']) && is_numeric($_GET['id']) ? intval($_GET['id']) : null;

// Manejar la solicitud según el método
switch ($metodo) {
    case 'GET':
        // Consultar usuarios
        consultar($conn, $id);
        break;
    case 'POST':
        // Insertar un nuevo usuario
        insertar($conn);
        break;
    case 'PUT':
        // Actualizar un usuario existente
        actualizar($conn, $id);
        break;
    case 'DELETE':
        // Borrar un usuario existente
        borrar($conn, $id);
        break;
    default:
        // Método no permitido
        echo json_encode(array("error" => "Método no permitido"));
        break;
}

// Función para consultar usuarios
function consultar($conn, $id)
{
    $sql = ($id !== null) ? "SELECT * FROM usuarios WHERE id = $id" : "SELECT * FROM usuarios";
    $resultado = $conn->query($sql);

    if ($resultado) {
        $datos = array();
        while ($fila = $resultado->fetch_assoc()) {
            $datos[] = $fila;
        }
        echo json_encode($datos);
    } else {
        echo json_encode(array("error" => "Error al consultar usuarios: " . mysqli_error($conn)));
    }
}

// Función para insertar un usuario
function insertar($conn)
{
    $dato = json_decode(file_get_contents("php://input"), true);
    $nombre = isset($dato["nombre"]) ? mysqli_real_escape_string($conn, $dato["nombre"]) : '';

    if ($nombre !== '') {
        $sql = "INSERT INTO usuarios (nombre) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $nombre);

        if ($stmt->execute()) {
            echo json_encode(array("id" => $conn->insert_id));
        } else {
            http_response_code(500); // Error interno del servidor
            echo json_encode(array("error" => "Error al insertar el usuario: " . $stmt->error));
        }

        $stmt->close();
    } else {
        http_response_code(400); // Solicitud incorrecta
        echo json_encode(array("error" => "Nombre de usuario no proporcionado"));
    }
}

// Función para actualizar un usuario
function actualizar($conn, $id)
{
    if ($id !== null && is_numeric($id)) {
        $data = json_decode(file_get_contents("php://input"), true);

        if (isset($data['nuevosDatos']) && is_array($data['nuevosDatos'])) {
            $nuevosDatos = $data['nuevosDatos'];
            $sets = [];
            foreach ($nuevosDatos as $campo => $valor) {
                $campo = mysqli_real_escape_string($conn, $campo);
                $valor = mysqli_real_escape_string($conn, $valor);
                $sets[] = "$campo = '$valor'";
            }
            $setString = implode(', ', $sets);
            $sql = "UPDATE usuarios SET $setString WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                echo json_encode(array("mensaje" => "Usuario actualizado correctamente"));
            } else {
                http_response_code(500); // Error interno del servidor
                echo json_encode(array("error" => "Error al actualizar el usuario: " . $stmt->error));
            }

            $stmt->close();
        } else {
            http_response_code(400); // Solicitud incorrecta
            echo json_encode(array("error" => "Nuevos datos no proporcionados"));
        }
    } else {
        http_response_code(400); // Solicitud incorrecta
        echo json_encode(array("error" => "ID no proporcionado o inválido"));
    }
}

function borrar($conn, $id)
{
    if ($id !== null && is_numeric($id)) {
        $sql = "DELETE FROM usuarios WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(array("mensaje" => "Usuario borrado correctamente"));
        } else {
            http_response_code(500); // Error interno del servidor
            echo json_encode(array("error" => "Error al borrar usuario: " . $stmt->error));
        }

        $stmt->close();
    } else {
        http_response_code(400); // Solicitud incorrecta
        echo json_encode(array("error" => "ID no proporcionado o inválido"));
    }
}

mysqli_close($conn);
