<?php

// Genera ficha.html a partir de ficha.php. Desde docs/:
//   php generar-ficha.php > ficha.html
//   "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" --headless=new --no-pdf-header-footer --print-to-pdf=ficha.pdf ficha.html

require __DIR__.'/ficha.php';

$comentarios = [];
foreach (file(__DIR__.'/ficha.php') as $linea) {
    if (preg_match("/^\s*'([a-z_]+)',\s*\/\/\s*(.+)$/", $linea, $m)) {
        $comentarios[$m[1]] = trim($m[2]);
    }
}

$e = fn (string $t): string => htmlspecialchars($t, ENT_QUOTES, 'UTF-8');

function valor(string $clave, mixed $valor, array $comentarios, callable $e): string
{
    if ($clave === 'reglas') {
        $items = array_map(function ($r) use ($e) {
            [$titulo, $texto] = explode(': ', $r, 2);

            return '<li><b>'.$e($titulo).':</b> '.$e($texto).'</li>';
        }, $valor);

        return '<ul>'.implode('', $items).'</ul>';
    }
    if ($clave === 'entidades') {
        $items = array_map(fn ($t) => '<li><code>'.$e($t).'</code>'
            .(isset($comentarios[$t]) ? ' <span class="nota">'.$e($comentarios[$t]).'</span>' : '').'</li>', $valor);

        return '<ol>'.implode('', $items).'</ol>';
    }
    if (is_array($valor)) {
        return '<ul>'.implode('', array_map(fn ($v) => '<li>'.$e($v).'</li>', $valor)).'</ul>';
    }
    if ($clave === 'repositorio') {
        return '<a href="https://'.$e($valor).'">'.$e($valor).'</a>';
    }

    return $e($valor);
}

$filas = '';
foreach ($ficha as $clave => $valor) {
    $filas .= '<tr><th>'.$e($clave).'</th><td>'.valor($clave, $valor, $comentarios, $e).'</td></tr>'."\n";
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>TurnoBarber — Ficha del proyecto</title>
<style>
  @page { size: A4; margin: 16mm 18mm; }
  body { margin: 0; color: #1c1b19; font: 10.5pt/1.5 "Helvetica Neue", Arial, sans-serif; }
  .curso { font: 9pt Menlo, monospace; color: #5d5a54; margin: 0 0 4pt; }
  h1 { font: 700 24pt/1.2 "Iowan Old Style", Georgia, serif; margin: 0 0 2pt; }
  .sub { color: #5d5a54; margin: 0 0 8pt; }
  .poste { height: 7px; background: repeating-linear-gradient(-45deg, #9e2a2b 0 12px, #fff 12px 24px, #1f4e79 24px 36px, #fff 36px 48px); border: 1px solid #cfc9bd; margin-bottom: 6pt; }
  table { width: 100%; border-collapse: collapse; }
  th, td { padding: 6pt 6pt; border-bottom: 1px solid #cfc9bd; vertical-align: top; text-align: left; }
  th { width: 30mm; font: 600 9.5pt Menlo, monospace; color: #9e2a2b; }
  tr { break-inside: avoid; }
  ul, ol { margin: 0; padding-left: 14pt; }
  li { margin-bottom: 2pt; }
  code { font: 9pt Menlo, monospace; background: #f4f1ea; padding: 0 2pt; }
  .nota { color: #5d5a54; font: 8.5pt Menlo, monospace; }
  a { color: #1f4e79; }
</style>
</head>
<body>
  <p class="curso">UNAB · Desarrollo y Arquitectura Backend · Proyecto de Aula</p>
  <h1>Ficha del proyecto</h1>
  <p class="sub">TurnoBarber · David Orozco Barbería y Peluquería</p>
  <div class="poste"></div>
  <table>
<?= $filas ?>
  </table>
</body>
</html>
