<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Demo Widget</title></head>
<body>
  <h2>Demo chat flotante</h2>
  <script>
    window.VITAMIN_CHAT_CONFIG = {
      endpoint: 'http://localhost:8000/api/chat.php',
      apiKey: 'demo-key',
      title: 'Asistente Salud Natural',
      buttonText: 'Asesor de Salud'
    };
  </script>
  <script src="/assets/widget.js"></script>
</body>
</html>
