<?php
ini_set('display_errors', 0);
error_reporting(0);
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
startAppSession();
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST)) {
    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? $action;
} else {
    $input = $_GET;
}

$db = getDB();

function listarTiposDocumento(PDO $db): array
{
    $stmt = $db->query("
        SELECT id, codigo, descripcion, descripcion_documento
        FROM fe_tipos_documento_identidad
        WHERE estado = TRUE
        ORDER BY CASE codigo
            WHEN '1' THEN 0
            WHEN '6' THEN 1
            WHEN '4' THEN 2
            ELSE 99
        END, descripcion
    ");

    return $stmt->fetchAll();
}

function obtenerTipoDocumento(PDO $db, $id): ?array
{
    $stmt = $db->prepare("
        SELECT id, codigo, descripcion, descripcion_documento
        FROM fe_tipos_documento_identidad
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => (int)$id]);
    return $stmt->fetch() ?: null;
}

function validarDocumentoPorTipo(string $codigo, string $numero): ?string
{
    $numero = trim($numero);

    if ($codigo === '1' && !preg_match('/^\d{8}$/', $numero)) {
        return 'Para DNI debe ingresar exactamente 8 digitos.';
    }

    if ($codigo === '6' && !preg_match('/^\d{11}$/', $numero)) {
        return 'Para RUC debe ingresar exactamente 11 digitos.';
    }

    if ($codigo !== '1' && $codigo !== '6' && $numero === '') {
        return 'Debe ingresar el numero de documento.';
    }

    return null;
}

function consultarDocumentoExterno(string $numero): array
{
    $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiI1ODgiLCJuYW1lIjoiTXl0ZW1zIiwiZW1haWwiOiJteXRlbXNjb250YWN0b0BnbWFpbC5jb20iLCJodHRwOi8vc2NoZW1hcy5taWNyb3NvZnQuY29tL3dzLzIwMDgvMDYvaWRlbnRpdHkvY2xhaW1zL3JvbGUiOiJjb25zdWx0b3IifQ.CMerOf33h1rSeWSEtfPwOv_6_vLhC0ZyhseiQs5Ba6c';
    $numero = trim($numero);
    $url = strlen($numero) === 8
        ? 'https://api.factiliza.com/pe/v1/dni/info/' . $numero
        : 'https://api.factiliza.com/pe/v1/ruc/info/' . $numero;

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
        ],
    ]);

    $response = curl_exec($curl);
    $curlError = curl_error($curl);
    $httpCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($response === false || $curlError) {
        return [
            'ok' => false,
            'message' => 'No se pudo consultar el documento: ' . ($curlError ?: 'error de conexion'),
        ];
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        return [
            'ok' => false,
            'message' => 'La respuesta del servicio de documentos no fue valida.',
        ];
    }

    if ($httpCode >= 400 || (int)($data['status'] ?? 200) >= 400) {
        return [
            'ok' => false,
            'message' => $data['message'] ?? 'No se encontro informacion para el documento consultado.',
        ];
    }

    return [
        'ok' => true,
        'data' => $data['data'] ?? null,
    ];
}

function construirPayloadCliente(array $input, array $tipoDocumento): array
{
    $codigo = trim((string)($tipoDocumento['codigo'] ?? '0'));
    $numeroDocumento = trim((string)($input['numero_documento'] ?? ''));
    $razonSocial = trim((string)($input['razon_social'] ?? ''));
    $contacto = trim((string)($input['contacto_nombre'] ?? ''));

    if ($codigo === '6') {
        $nombreCompleto = $razonSocial;
    } else {
        $nombreCompleto = $razonSocial !== '' ? $razonSocial : $contacto;
    }

    return [
        'tipo' => ($input['tipo'] ?? 'persona') === 'empresa' ? 'empresa' : 'persona',
        'tipo_documento_id' => (int)$tipoDocumento['id'],
        'tipo_documento_codigo' => $codigo,
        'numero_documento' => $numeroDocumento,
        'razon_social' => $razonSocial,
        'nombre_completo' => $nombreCompleto,
        'contacto_nombre' => $contacto !== '' ? $contacto : null,
        'ruc_dni' => $numeroDocumento !== '' ? $numeroDocumento : null,
        'email' => ($input['email'] ?? '') !== '' ? trim((string)$input['email']) : null,
        'telefono' => ($input['telefono'] ?? '') !== '' ? trim((string)$input['telefono']) : null,
        'whatsapp' => ($input['whatsapp'] ?? '') !== '' ? trim((string)$input['whatsapp']) : null,
        'direccion' => ($input['direccion'] ?? '') !== '' ? trim((string)$input['direccion']) : null,
        'estado_crm' => trim((string)($input['estado_crm'] ?? 'prospecto')),
        'notas' => ($input['notas'] ?? '') !== '' ? trim((string)$input['notas']) : null,
        'codigo_pais' => 'PE',
        'ubigeo' => preg_match('/^\d{6}$/', (string)($input['ubigeo'] ?? '')) ? trim((string)$input['ubigeo']) : null,
    ];
}

try {
    switch ($action) {
        case 'document_types':
            jsonResponse(['ok' => true, 'items' => listarTiposDocumento($db)]);

        case 'lookup_document':
            $tipoDocumento = obtenerTipoDocumento($db, $input['tipo_documento_id'] ?? 0);
            if (!$tipoDocumento) {
                jsonResponse(['error' => 'Tipo de documento invalido'], 422);
            }

            $codigo = trim((string)$tipoDocumento['codigo']);
            $numero = trim((string)($input['numero_documento'] ?? ''));
            $validacion = validarDocumentoPorTipo($codigo, $numero);
            if ($validacion !== null) {
                jsonResponse(['error' => $validacion], 422);
            }

            if (!in_array($codigo, ['1', '6'], true)) {
                jsonResponse(['error' => 'La consulta automatica solo esta disponible para DNI y RUC'], 422);
            }

            $consulta = consultarDocumentoExterno($numero);
            if (!$consulta['ok']) {
                jsonResponse(['error' => $consulta['message']], 404);
            }

            $data = $consulta['data'];
            if (!$data) {
                jsonResponse(['error' => 'No se encontro informacion para el documento consultado'], 404);
            }

            if ($codigo === '1') {
                $payload = [
                    'razon_social' => trim((string)(($data['nombres'] ?? '') . ' ' . ($data['apellido_paterno'] ?? '') . ' ' . ($data['apellido_materno'] ?? ''))),
                    'contacto_nombre' => trim((string)($data['nombres'] ?? '')),
                    'direccion' => trim((string)($data['direccion'] ?? '')),
                    'ubigeo' => (($data['ubigeo_sunat'] ?? '') === '-' ? '' : trim((string)($data['ubigeo_sunat'] ?? ''))),
                ];
            } else {
                $payload = [
                    'razon_social' => trim((string)($data['nombre_o_razon_social'] ?? '')),
                    'contacto_nombre' => '',
                    'direccion' => trim((string)($data['direccion'] ?? '')),
                    'ubigeo' => (($data['ubigeo_sunat'] ?? '') === '-' ? '' : trim((string)($data['ubigeo_sunat'] ?? ''))),
                ];
            }

            jsonResponse(['ok' => true, 'cliente' => $payload]);

        case 'kpis':
            $row = $db->query("
                SELECT
                    COUNT(*) FILTER (WHERE activo) AS total,
                    COUNT(*) FILTER (WHERE activo AND estado_crm = 'activo') AS activos,
                    COUNT(*) FILTER (WHERE activo AND estado_crm = 'prospecto') AS prospectos,
                    COUNT(*) FILTER (WHERE activo AND estado_crm = 'inactivo') AS inactivos
                FROM clientes
            ")->fetch();
            jsonResponse(['ok' => true, 'kpis' => $row]);

        case 'listar':
            $where = ['c.activo = TRUE'];
            $params = [];

            if (!empty($_GET['buscar'])) {
                $where[] = "(
                    COALESCE(c.razon_social, '') ILIKE :b OR
                    COALESCE(c.nombre_completo, '') ILIKE :b OR
                    COALESCE(c.numero_documento, '') ILIKE :b OR
                    COALESCE(c.ruc_dni, '') ILIKE :b OR
                    COALESCE(c.contacto_nombre, '') ILIKE :b OR
                    COALESCE(c.email, '') ILIKE :b OR
                    COALESCE(c.telefono, '') ILIKE :b OR
                    COALESCE(c.whatsapp, '') ILIKE :b
                )";
                $params[':b'] = '%' . trim((string)$_GET['buscar']) . '%';
            }
            if (!empty($_GET['tipo'])) {
                $where[] = 'c.tipo = :tipo';
                $params[':tipo'] = $_GET['tipo'];
            }
            if (!empty($_GET['estado_crm'])) {
                $where[] = 'c.estado_crm = :estado';
                $params[':estado'] = $_GET['estado_crm'];
            }

            $stmt = $db->prepare("
                SELECT
                    c.*,
                    td.descripcion_documento,
                    COUNT(p.id) FILTER (WHERE p.activo AND p.estado NOT IN ('completado','cancelado')) AS proyectos_activos,
                    COUNT(p.id) FILTER (WHERE p.activo) AS proyectos_total
                FROM clientes c
                LEFT JOIN fe_tipos_documento_identidad td ON td.id = c.tipo_documento_id
                LEFT JOIN proyectos p ON p.cliente_id = c.id
                WHERE " . implode(' AND ', $where) . "
                GROUP BY c.id, td.descripcion_documento
                ORDER BY
                    CASE
                        WHEN COALESCE(c.numero_documento, '') = '00000000'
                             OR UPPER(COALESCE(c.razon_social, '')) = 'CLIENTES VARIOS' THEN 0
                        ELSE 1
                    END,
                    COALESCE(c.razon_social, c.nombre_completo, c.contacto_nombre) ASC
            ");
            $stmt->execute($params);
            jsonResponse(['ok' => true, 'clientes' => $stmt->fetchAll()]);

        case 'obtener':
            $stmt = $db->prepare("SELECT * FROM clientes WHERE id = :id");
            $stmt->execute([':id' => (int)($_GET['id'] ?? 0)]);
            $cliente = $stmt->fetch();
            if (!$cliente) {
                jsonResponse(['error' => 'Cliente no encontrado.'], 404);
            }
            jsonResponse(['ok' => true, 'cliente' => $cliente]);

        case 'crear':
        case 'actualizar':
            $esActualizar = $action === 'actualizar';
            $id = (int)($input['id'] ?? 0);
            if ($esActualizar && !$id) {
                jsonResponse(['error' => 'ID invalido.'], 400);
            }

            $tipoDocumento = obtenerTipoDocumento($db, $input['tipo_documento_id'] ?? 0);
            if (!$tipoDocumento) {
                jsonResponse(['error' => 'Debe seleccionar un tipo de documento valido.'], 422);
            }

            $codigoTipo = trim((string)$tipoDocumento['codigo']);
            $numeroDocumento = trim((string)($input['numero_documento'] ?? ''));
            $validacionDocumento = validarDocumentoPorTipo($codigoTipo, $numeroDocumento);
            if ($validacionDocumento !== null) {
                jsonResponse(['error' => $validacionDocumento], 422);
            }

            $razonSocial = trim((string)($input['razon_social'] ?? ''));
            if ($razonSocial === '') {
                jsonResponse(['error' => $codigoTipo === '6' ? 'Debe ingresar la razon social.' : 'Debe ingresar el nombre del cliente.'], 422);
            }

            $email = trim((string)($input['email'] ?? ''));
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                jsonResponse(['error' => 'El correo no es valido.'], 422);
            }

            $dupSql = "
                SELECT id
                FROM clientes
                WHERE tipo_documento_codigo = :codigo
                  AND numero_documento = :numero
            ";
            $dupParams = [':codigo' => $codigoTipo, ':numero' => $numeroDocumento];
            if ($esActualizar) {
                $dupSql .= ' AND id != :id';
                $dupParams[':id'] = $id;
            }
            $dupStmt = $db->prepare($dupSql);
            $dupStmt->execute($dupParams);
            if ($dupStmt->fetch()) {
                jsonResponse(['error' => 'Ya existe otro cliente con ese documento.'], 422);
            }

            $payload = construirPayloadCliente($input, $tipoDocumento);

            if ($esActualizar) {
                $stmt = $db->prepare("
                    UPDATE clientes SET
                        tipo = :tipo,
                        razon_social = :razon_social,
                        ruc_dni = :ruc_dni,
                        contacto_nombre = :contacto_nombre,
                        email = :email,
                        telefono = :telefono,
                        whatsapp = :whatsapp,
                        direccion = :direccion,
                        estado_crm = :estado_crm,
                        notas = :notas,
                        tipo_documento_id = :tipo_documento_id,
                        tipo_documento_codigo = :tipo_documento_codigo,
                        numero_documento = :numero_documento,
                        nombre_completo = :nombre_completo,
                        codigo_pais = :codigo_pais,
                        ubigeo = :ubigeo
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':id' => $id,
                    ':tipo' => $payload['tipo'],
                    ':razon_social' => $payload['razon_social'],
                    ':ruc_dni' => $payload['ruc_dni'],
                    ':contacto_nombre' => $payload['contacto_nombre'],
                    ':email' => $payload['email'],
                    ':telefono' => $payload['telefono'],
                    ':whatsapp' => $payload['whatsapp'],
                    ':direccion' => $payload['direccion'],
                    ':estado_crm' => $payload['estado_crm'],
                    ':notas' => $payload['notas'],
                    ':tipo_documento_id' => $payload['tipo_documento_id'],
                    ':tipo_documento_codigo' => $payload['tipo_documento_codigo'],
                    ':numero_documento' => $payload['numero_documento'],
                    ':nombre_completo' => $payload['nombre_completo'],
                    ':codigo_pais' => $payload['codigo_pais'],
                    ':ubigeo' => $payload['ubigeo'],
                ]);

                jsonResponse(['ok' => true]);
            }

            $stmt = $db->prepare("
                INSERT INTO clientes (
                    tipo, razon_social, ruc_dni, contacto_nombre, email, telefono, whatsapp,
                    direccion, estado_crm, notas, activo, tipo_documento_id,
                    tipo_documento_codigo, numero_documento, nombre_completo, codigo_pais, ubigeo
                ) VALUES (
                    :tipo, :razon_social, :ruc_dni, :contacto_nombre, :email, :telefono, :whatsapp,
                    :direccion, :estado_crm, :notas, TRUE, :tipo_documento_id,
                    :tipo_documento_codigo, :numero_documento, :nombre_completo, :codigo_pais, :ubigeo
                )
                RETURNING id
            ");
            $stmt->execute([
                ':tipo' => $payload['tipo'],
                ':razon_social' => $payload['razon_social'],
                ':ruc_dni' => $payload['ruc_dni'],
                ':contacto_nombre' => $payload['contacto_nombre'],
                ':email' => $payload['email'],
                ':telefono' => $payload['telefono'],
                ':whatsapp' => $payload['whatsapp'],
                ':direccion' => $payload['direccion'],
                ':estado_crm' => $payload['estado_crm'],
                ':notas' => $payload['notas'],
                ':tipo_documento_id' => $payload['tipo_documento_id'],
                ':tipo_documento_codigo' => $payload['tipo_documento_codigo'],
                ':numero_documento' => $payload['numero_documento'],
                ':nombre_completo' => $payload['nombre_completo'],
                ':codigo_pais' => $payload['codigo_pais'],
                ':ubigeo' => $payload['ubigeo'],
            ]);
            jsonResponse(['ok' => true, 'id' => $stmt->fetchColumn()]);

        case 'eliminar':
            $db->prepare('UPDATE clientes SET activo = FALSE WHERE id = :id')
               ->execute([':id' => (int)($input['id'] ?? 0)]);
            jsonResponse(['ok' => true]);

        case 'proyectos':
            $stmt = $db->prepare("
                SELECT p.id, p.codigo, p.nombre, p.estado, p.prioridad,
                       p.fecha_entrega_estimada, p.costo_estimado, p.costo_real,
                       p.porcentaje_avance, p.created_at,
                       u.nombre AS responsable_nombre
                FROM proyectos p
                LEFT JOIN usuarios u ON u.id = p.responsable_id
                WHERE p.cliente_id = :cid AND p.activo = TRUE
                ORDER BY p.created_at DESC
            ");
            $stmt->execute([':cid' => (int)($_GET['id'] ?? 0)]);
            jsonResponse(['ok' => true, 'proyectos' => $stmt->fetchAll()]);

        default:
            jsonResponse(['error' => 'Accion no reconocida.'], 400);
    }
} catch (PDOException $e) {
    jsonResponse(['error' => 'Error de base de datos: ' . $e->getMessage()], 500);
}
