<?php
/**
 * VISTA — Estante
 * Dibuja el estante en SVG a partir de {config, celdaActiva}. Solo presentacion.
 * No consulta la BD ni contiene logica de negocio.
 * Ver ARQUITECTURA_MVC.md
 */
require_once __DIR__ . '/../models/Estante.php';

/**
 * Devuelve el SVG del estante como string.
 *
 * @param array       $config      ['columnas'=>int, 'filas'=>int]
 * @param string|null $celdaActiva Codigo de celda a resaltar (ej. 'B3') o null.
 */
function renderEstanteSVG(array $config, ?string $celdaActiva = null): string
{
    $cols  = max(1, (int)($config['columnas'] ?? 1));
    $filas = max(1, (int)($config['filas'] ?? 1));
    $activa = Estante::parseCelda($celdaActiva); // ['col_num','fila'] o null

    // Geometria del estante (unidades SVG), proporcional al ancho configurado.
    $shelf = min(24, max(1, (int)($config['grosor'] ?? 7)));   // grosor de repisa/montante
    $ancho = min(1400, max(240, (int)($config['ancho'] ?? 360))); // ancho total del estante en px
    $gutterL = 24; $gutterT = 20; $pad = 12;

    // El ancho total se reparte: el ancho de cada celda sale del espacio disponible.
    $cell  = max(18, ($ancho - 2 * $pad - $gutterL - 2 * $shelf) / $cols); // slot por columna
    $rowH  = max(26, round($cell * 1.12));  // banda por fila (caja + repisa)
    $boxW  = max(12, $cell - 8);
    $boxH  = max(12, $rowH - 14);

    $gridW = $cols * $cell;
    $gridH = $filas * $rowH;

    $leftPostX = $pad + $gutterL;           // montante izquierdo
    $x0        = $leftPostX + $shelf;        // primer slot
    $rightPostX = $x0 + $gridW;              // montante derecho
    $frameTop  = $pad + $gutterT;            // borde superior del marco
    $frameBot  = $frameTop + $gridH + $shelf;

    $W = $rightPostX + $shelf + $pad;
    $H = $frameBot + $pad;

    $over = 3; // vuelo de repisas sobre los montantes
    $railL = $leftPostX - $over;
    $railW = ($rightPostX + $shelf) - $railL + $over;

    $DARK = '#334155';
    $fs = max(9, min(15, round($cell * 0.28))); // fuente de etiquetas proporcional

    $svg  = '<svg class="estante-svg" viewBox="0 0 ' . $W . ' ' . $H . '" '
          . 'style="width:100%;max-width:' . $W . 'px;height:auto;font-family:inherit" '
          . 'role="img" aria-label="Ubicacion en estante">';

    // Etiquetas de columna (A, B, C...) arriba.
    for ($c = 1; $c <= $cols; $c++) {
        $cx = $x0 + ($c - 1) * $cell + $cell / 2;
        $svg .= '<text x="' . $cx . '" y="' . ($frameTop - 7) . '" text-anchor="middle" '
              . 'font-size="' . $fs . '" font-weight="700" fill="#64748b">'
              . htmlspecialchars(Estante::letraColumna($c), ENT_QUOTES) . '</text>';
    }

    // Repisas horizontales: riel superior + una bajo cada fila.
    for ($i = 0; $i <= $filas; $i++) {
        $ry = $frameTop + $i * $rowH;
        $svg .= '<rect x="' . $railL . '" y="' . $ry . '" width="' . $railW . '" height="' . $shelf . '" rx="2" fill="' . $DARK . '"/>';
    }
    // Montantes laterales.
    $svg .= '<rect x="' . $leftPostX . '" y="' . $frameTop . '" width="' . $shelf . '" height="' . ($gridH + $shelf) . '" rx="2" fill="' . $DARK . '"/>';
    $svg .= '<rect x="' . $rightPostX . '" y="' . $frameTop . '" width="' . $shelf . '" height="' . ($gridH + $shelf) . '" rx="2" fill="' . $DARK . '"/>';

    // Cajas por celda. Fila mas alta arriba.
    for ($f = $filas; $f >= 1; $f--) {
        $rowIdx = $filas - $f;                       // 0 = arriba
        $bandTop = $frameTop + $rowIdx * $rowH + $shelf;
        $shelfY  = $frameTop + ($rowIdx + 1) * $rowH; // repisa inferior de la banda
        $boxY    = $shelfY - $boxH;                   // caja apoyada en la repisa

        // Numero de fila a la izquierda (centrado en la banda).
        $svg .= '<text x="' . ($pad + $gutterL - 8) . '" y="' . ($bandTop + ($rowH - $shelf) / 2 + 4) . '" '
              . 'text-anchor="middle" font-size="' . $fs . '" font-weight="700" fill="#64748b">' . $f . '</text>';

        for ($c = 1; $c <= $cols; $c++) {
            $boxX = $x0 + ($c - 1) * $cell + ($cell - $boxW) / 2;
            $esActiva = $activa && $activa['col_num'] === $c && $activa['fila'] === $f;
            $svg .= estanteCajaSVG($boxX, $boxY, $boxW, $boxH, $esActiva,
                        $esActiva ? Estante::letraColumna($c) . $f : '');
        }
    }

    return $svg . '</svg>';
}

/** Dibuja una caja de carton en (x,y). Gris por defecto, naranja si esta activa. */
function estanteCajaSVG(float $x, float $y, float $w, float $h, bool $activa, string $label = ''): string
{
    if ($activa) { $fill = 'var(--primary)'; $stroke = 'var(--primary-dark, #ea580c)'; $det = 'rgba(255,255,255,.55)'; }
    else         { $fill = '#c9d6e5';        $stroke = '#90a4bd';                      $det = '#90a4bd'; }

    $s  = '<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" rx="3" '
        . 'fill="' . $fill . '" stroke="' . $stroke . '" stroke-width="1.5"/>';
    // Solapa superior (costura de la tapa).
    $s .= '<line x1="' . $x . '" y1="' . ($y + $h * 0.3) . '" x2="' . ($x + $w) . '" y2="' . ($y + $h * 0.3) . '" stroke="' . $det . '" stroke-width="1.2"/>';
    // Cinta central y dos pestañas.
    $cx = $x + $w / 2;
    $s .= '<line x1="' . $cx . '" y1="' . $y . '" x2="' . $cx . '" y2="' . ($y + $h * 0.3) . '" stroke="' . $det . '" stroke-width="1.2"/>';

    if ($label !== '') {
        $lfs = max(8, min(16, round($h * 0.42)));
        $s .= '<text x="' . $cx . '" y="' . ($y + $h * 0.68) . '" text-anchor="middle" '
            . 'font-size="' . $lfs . '" font-weight="800" fill="#ffffff">' . htmlspecialchars($label, ENT_QUOTES) . '</text>';
    }
    return $s;
}
