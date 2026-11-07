<?php
/**
 * MODELO — Estante
 * Acceso a datos y reglas de negocio del estante global y de la celda de cada producto.
 * No imprime HTML ni enruta: solo datos y validacion.
 * Ver ARQUITECTURA_MVC.md
 */
class Estante
{
    const MAX_COLUMNAS = 26;  // A..Z
    const MAX_FILAS    = 99;
    const MIN_GROSOR   = 1;
    const MAX_GROSOR   = 24;
    const MIN_ANCHO    = 240;
    const MAX_ANCHO    = 1400;

    const DEFAULTS = ['columnas' => 6, 'filas' => 6, 'nombre' => 'Estante Principal', 'grosor' => 7, 'ancho' => 360];

    /**
     * Configuracion global (fila unica id=1).
     * Devuelve ['columnas','filas','nombre','grosor','ancho'].
     */
    public static function obtenerConfig(PDO $db): array
    {
        $row = $db->query("SELECT columnas, filas, nombre, grosor_linea, ancho FROM estante_config WHERE id = 1")->fetch();
        if (!$row) {
            // Autoreparacion: si la fila no existe, la crea con valores por defecto.
            $db->exec("INSERT INTO estante_config (id, columnas, filas) VALUES (1, 6, 6) ON CONFLICT (id) DO NOTHING");
            return self::DEFAULTS;
        }
        return [
            'columnas' => (int)$row['columnas'],
            'filas'    => (int)$row['filas'],
            'nombre'   => (string)($row['nombre'] ?? self::DEFAULTS['nombre']),
            'grosor'   => (int)($row['grosor_linea'] ?? self::DEFAULTS['grosor']),
            'ancho'    => (int)($row['ancho'] ?? self::DEFAULTS['ancho']),
        ];
    }

    /**
     * Guarda la configuracion global del estante.
     * $datos: ['columnas','filas','nombre','grosor','ancho']. Lanza Exception si algo es invalido.
     */
    public static function guardarConfig(PDO $db, array $datos): void
    {
        $columnas = (int)($datos['columnas'] ?? 0);
        $filas    = (int)($datos['filas'] ?? 0);
        $grosor   = (int)($datos['grosor'] ?? self::DEFAULTS['grosor']);
        $ancho    = (int)($datos['ancho'] ?? self::DEFAULTS['ancho']);
        $nombre   = trim((string)($datos['nombre'] ?? ''));
        if ($nombre === '') $nombre = self::DEFAULTS['nombre'];
        $nombre   = mb_substr($nombre, 0, 60);

        if ($columnas < 1 || $columnas > self::MAX_COLUMNAS) {
            throw new Exception('Las columnas deben estar entre 1 y ' . self::MAX_COLUMNAS . '.');
        }
        if ($filas < 1 || $filas > self::MAX_FILAS) {
            throw new Exception('Las filas deben estar entre 1 y ' . self::MAX_FILAS . '.');
        }
        if ($grosor < self::MIN_GROSOR || $grosor > self::MAX_GROSOR) {
            throw new Exception('El grosor debe estar entre ' . self::MIN_GROSOR . ' y ' . self::MAX_GROSOR . '.');
        }
        if ($ancho < self::MIN_ANCHO || $ancho > self::MAX_ANCHO) {
            throw new Exception('El ancho debe estar entre ' . self::MIN_ANCHO . ' y ' . self::MAX_ANCHO . ' px.');
        }

        $stmt = $db->prepare("
            INSERT INTO estante_config (id, columnas, filas, nombre, grosor_linea, ancho, updated_at)
            VALUES (1, :c, :f, :n, :g, :a, NOW())
            ON CONFLICT (id) DO UPDATE SET
                columnas = :c, filas = :f, nombre = :n, grosor_linea = :g, ancho = :a, updated_at = NOW()
        ");
        $stmt->execute([':c' => $columnas, ':f' => $filas, ':n' => $nombre, ':g' => $grosor, ':a' => $ancho]);
    }

    /**
     * Parsea un codigo de celda 'B3' -> ['col'=>'B', 'col_num'=>2, 'fila'=>3].
     * Letras iniciales = columna (A=1), digitos finales = fila. Devuelve null si invalido.
     */
    public static function parseCelda(?string $codigo): ?array
    {
        if ($codigo === null) return null;
        $codigo = strtoupper(trim($codigo));
        if ($codigo === '') return null;
        if (!preg_match('/^([A-Z]+)(\d+)$/', $codigo, $m)) return null;

        $letras = $m[1];
        $fila   = (int)$m[2];
        if ($fila < 1) return null;

        // Conversion de letras a numero base-26 (A=1, ..., Z=26, AA=27...).
        $colNum = 0;
        for ($i = 0, $n = strlen($letras); $i < $n; $i++) {
            $colNum = $colNum * 26 + (ord($letras[$i]) - ord('A') + 1);
        }
        return ['col' => $letras, 'col_num' => $colNum, 'fila' => $fila];
    }

    /** Normaliza un codigo de celda a su forma canonica ('b3' -> 'B3'); '' -> null. */
    public static function normalizarCelda(?string $codigo): ?string
    {
        $p = self::parseCelda($codigo);
        return $p ? $p['col'] . $p['fila'] : null;
    }

    /** True si la celda existe dentro del tamaño configurado del estante. */
    public static function celdaValida(PDO $db, ?string $codigo): bool
    {
        $p = self::parseCelda($codigo);
        if (!$p) return false;
        $cfg = self::obtenerConfig($db);
        return $p['col_num'] >= 1 && $p['col_num'] <= $cfg['columnas']
            && $p['fila']    >= 1 && $p['fila']    <= $cfg['filas'];
    }

    /** Devuelve la celda guardada de un producto (ej. 'B3') o null. */
    public static function obtenerCeldaProducto(PDO $db, int $productoId): ?string
    {
        $stmt = $db->prepare("SELECT celda_estante FROM productos WHERE id = :id");
        $stmt->execute([':id' => $productoId]);
        $val = $stmt->fetchColumn();
        return $val !== false && $val !== null && $val !== '' ? (string)$val : null;
    }

    /**
     * Guarda la celda de un producto. Celda vacia/null = desasignar.
     * Valida que la celda exista dentro del estante configurado.
     */
    public static function guardarCeldaProducto(PDO $db, int $productoId, ?string $celda): void
    {
        $celda = self::normalizarCelda($celda);
        if ($celda !== null && !self::celdaValida($db, $celda)) {
            throw new Exception('La celda ' . $celda . ' esta fuera del tamaño del estante.');
        }
        $stmt = $db->prepare("UPDATE productos SET celda_estante = :celda, updated_at = NOW() WHERE id = :id");
        $stmt->execute([':celda' => $celda, ':id' => $productoId]);
    }

    /** Etiqueta de columna a partir de su indice 1..26 -> 'A'..'Z'. */
    public static function letraColumna(int $colNum): string
    {
        $s = '';
        while ($colNum > 0) {
            $colNum--;
            $s = chr(ord('A') + ($colNum % 26)) . $s;
            $colNum = intdiv($colNum, 26);
        }
        return $s;
    }
}
