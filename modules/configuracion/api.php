<?php
ini_set('display_errors', 0);
error_reporting(0);
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
startAppSession();
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = !empty($_POST) ? $_POST : (json_decode(file_get_contents('php://input'), true) ?? []);
    $action = $input['action'] ?? $action;
} else {
    $input = $_GET;
}

$db = getDB();

function asegurarDirectorio(string $path): void
{
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

try {
    switch ($action) {
        case 'ubigeo_resolver':
            $codigo = trim((string)($_GET['codigo'] ?? ''));
            if (!preg_match('/^\d{6}$/', $codigo)) {
                jsonResponse(['error' => 'El ubigeo debe tener 6 digitos.'], 400);
            }

            $tablaUbigeo = $db->query("SELECT to_regclass('public.ubigeo_distritos') AS tabla")->fetch();
            if (empty($tablaUbigeo['tabla'])) {
                jsonResponse(['error' => 'El catalogo de ubigeo no esta cargado.'], 404);
            }

            $stmt = $db->prepare("
                SELECT
                    d.codigo,
                    d.nombre AS distrito,
                    p.nombre AS provincia,
                    dep.nombre AS departamento
                FROM public.ubigeo_distritos d
                INNER JOIN public.ubigeo_provincias p
                    ON p.codigo = d.provincia_codigo
                INNER JOIN public.ubigeo_departamentos dep
                    ON dep.codigo = d.departamento_codigo
                WHERE d.codigo = :codigo
                LIMIT 1
            ");
            $stmt->execute([':codigo' => $codigo]);
            $row = $stmt->fetch();

            if (!$row) {
                jsonResponse(['error' => 'No se encontro el ubigeo solicitado.'], 404);
            }

            jsonResponse([
                'ok' => true,
                'codigo' => $row['codigo'],
                'departamento' => $row['departamento'],
                'provincia' => $row['provincia'],
                'distrito' => $row['distrito'],
            ]);

        case 'empresa_obtener':
            $empresa = $db->query("SELECT * FROM empresa_emisora ORDER BY id LIMIT 1")->fetch();
            $tiposDocumento = $db->query("SELECT id, codigo, descripcion FROM fe_tipos_documento WHERE estado ORDER BY codigo")->fetchAll();
            $formasPago = $db->query("SELECT id, descripcion FROM fe_formas_pago WHERE estado ORDER BY descripcion")->fetchAll();
            jsonResponse(['ok' => true, 'empresa' => $empresa, 'tipos_documento' => $tiposDocumento, 'formas_pago' => $formasPago]);

        case 'empresa_guardar':
            $empresa = $db->query("SELECT id, logo_path, certificate_path, api_url FROM empresa_emisora ORDER BY id LIMIT 1")->fetch();
            if (!$empresa) {
                jsonResponse(['error' => 'No existe registro base de empresa emisora.'], 400);
            }

            $logoPath = $empresa['logo_path'] ?? null;
            $certificatePath = $empresa['certificate_path'] ?? null;
            $apiUrl = $empresa['api_url'] ?? null;
            $ubigeo = trim((string)($input['ubigeo'] ?? ''));
            $departamento = trim((string)($input['departamento'] ?? ''));
            $provincia = trim((string)($input['provincia'] ?? ''));
            $distrito = trim((string)($input['distrito'] ?? ''));

            if ($ubigeo !== '') {
                if (!preg_match('/^\d{6}$/', $ubigeo)) {
                    jsonResponse(['error' => 'El ubigeo debe tener 6 digitos.'], 400);
                }

                $ubigeoStmt = $db->prepare("
                    SELECT
                        d.nombre AS distrito,
                        p.nombre AS provincia,
                        dep.nombre AS departamento
                    FROM public.ubigeo_distritos d
                    INNER JOIN public.ubigeo_provincias p
                        ON p.codigo = d.provincia_codigo
                    INNER JOIN public.ubigeo_departamentos dep
                        ON dep.codigo = d.departamento_codigo
                    WHERE d.codigo = :codigo
                    LIMIT 1
                ");
                $ubigeoStmt->execute([':codigo' => $ubigeo]);
                $ubigeoInfo = $ubigeoStmt->fetch();

                if (!$ubigeoInfo) {
                    jsonResponse(['error' => 'El ubigeo no existe en el catalogo cargado.'], 400);
                }

                $departamento = $ubigeoInfo['departamento'];
                $provincia = $ubigeoInfo['provincia'];
                $distrito = $ubigeoInfo['distrito'];
            } else {
                $departamento = '';
                $provincia = '';
                $distrito = '';
            }

            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $logoDir = dirname(__DIR__, 2) . '/assets/uploads/empresa/';
                asegurarDirectorio($logoDir);
                $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                $logoName = 'logo_empresa_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['logo']['tmp_name'], $logoDir . $logoName);
                $logoPath = 'assets/uploads/empresa/' . $logoName;
            }

            if (isset($_FILES['certificado']) && $_FILES['certificado']['error'] === UPLOAD_ERR_OK) {
                $certDir = dirname(__DIR__, 2) . '/facturacion/certs/';
                asegurarDirectorio($certDir);
                $ext = strtolower(pathinfo($_FILES['certificado']['name'], PATHINFO_EXTENSION));
                $certName = 'empresa_certificado.' . $ext;
                move_uploaded_file($_FILES['certificado']['tmp_name'], $certDir . $certName);
                $certificatePath = 'facturacion/certs/' . $certName;
            }

            $stmt = $db->prepare("
                UPDATE empresa_emisora SET
                    razon_social = :razon_social,
                    nombre_comercial = :nombre_comercial,
                    ruc = :ruc,
                    email = :email,
                    telefono = :telefono,
                    direccion_fiscal = :direccion_fiscal,
                    codigo_pais = :codigo_pais,
                    ubigeo = :ubigeo,
                    departamento = :departamento,
                    provincia = :provincia,
                    distrito = :distrito,
                    tax_enabled = :tax_enabled,
                    logo_path = :logo_path,
                    api_url = :api_url,
                    sunat_username = :sunat_username,
                    sunat_password = :sunat_password,
                    gre_client_id = :gre_client_id,
                    gre_client_secret = :gre_client_secret,
                    certificate_path = :certificate_path,
                    certificate_password = :certificate_password,
                    certificate_expires_at = :certificate_expires_at,
                    sunat_server = :sunat_server,
                    whatsapp_instance = :whatsapp_instance,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => (int)$empresa['id'],
                ':razon_social' => trim((string)($input['razon_social'] ?? '')),
                ':nombre_comercial' => ($input['nombre_comercial'] ?? null) ?: null,
                ':ruc' => ($input['ruc'] ?? null) ?: null,
                ':email' => ($input['email'] ?? null) ?: null,
                ':telefono' => ($input['telefono'] ?? null) ?: null,
                ':direccion_fiscal' => ($input['direccion_fiscal'] ?? null) ?: null,
                ':codigo_pais' => ($input['codigo_pais'] ?? 'PE') ?: 'PE',
                ':ubigeo' => $ubigeo ?: null,
                ':departamento' => $departamento ?: null,
                ':provincia' => $provincia ?: null,
                ':distrito' => $distrito ?: null,
                ':tax_enabled' => (int)filter_var($input['tax_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
                ':logo_path' => $logoPath,
                ':api_url' => $apiUrl,
                ':sunat_username' => ($input['sunat_username'] ?? null) ?: null,
                ':sunat_password' => ($input['sunat_password'] ?? null) ?: null,
                ':gre_client_id' => ($input['gre_client_id'] ?? null) ?: null,
                ':gre_client_secret' => ($input['gre_client_secret'] ?? null) ?: null,
                ':certificate_path' => $certificatePath,
                ':certificate_password' => ($input['certificate_password'] ?? null) ?: null,
                ':certificate_expires_at' => ($input['certificate_expires_at'] ?? null) ?: null,
                ':sunat_server' => ($input['sunat_server'] ?? 'beta') ?: 'beta',
                ':whatsapp_instance' => ($input['whatsapp_instance'] ?? null) ?: null,
            ]);

            jsonResponse(['ok' => true, 'logo_path' => $logoPath, 'certificate_path' => $certificatePath]);

        default:
            jsonResponse(['error' => 'Accion no reconocida.'], 400);
    }
} catch (PDOException $e) {
    jsonResponse(['error' => 'Error BD: ' . $e->getMessage()], 500);
}
