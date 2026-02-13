<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

?><!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Shopify Vitamin Assistant</title>
  <style>
    body{font-family:Arial,sans-serif;max-width:760px;margin:40px auto;padding:0 16px;color:#20302a}
    code{background:#f3f7f5;padding:2px 6px;border-radius:6px}
  </style>
</head>
<body>
  <h1>Asistente flotante para Shopify (Vitaminas y Salud Natural)</h1>
  <p>Instalación rápida:</p>
  <ol>
    <li>Copia <code>.env.example</code> a <code>.env</code> y configura claves.</li>
    <li>Sube esta carpeta dentro de <code>public_html</code>.</li>
    <li>En Shopify, agrega el snippet JavaScript (ver README) en tu tema.</li>
    <li>Prueba el endpoint: <code>/api/chat.php</code></li>
  </ol>
</body>
</html>
