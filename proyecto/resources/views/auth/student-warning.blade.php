<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #000;
            color: #ff0000;
            font-family: 'Courier New', monospace;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            overflow: hidden;
        }
        .warning-container {
            text-align: center;
            padding: 2rem;
            border: 2px solid #ff0000;
            border-radius: 10px;
            background-color: rgba(0, 0, 0, 0.8);
            animation: glitch 1s infinite;
        }
        .warning-title {
            font-size: 3rem;
            margin-bottom: 1rem;
            text-transform: uppercase;
            text-shadow: 2px 2px 4px #ff0000;
        }
        .warning-message {
            font-size: 1.5rem;
            margin-bottom: 2rem;
        }
        .warning-details {
            font-size: 1rem;
            color: #ff6666;
        }
        @keyframes glitch {
            0% { transform: translate(0) }
            20% { transform: translate(-2px, 2px) }
            40% { transform: translate(-2px, -2px) }
            60% { transform: translate(2px, 2px) }
            80% { transform: translate(2px, -2px) }
            100% { transform: translate(0) }
        }
        .matrix-rain {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }
    </style>
</head>
<body>
    <canvas id="matrix" class="matrix-rain"></canvas>
    <div class="warning-container">
        <h1 class="warning-title">¡ADVERTENCIA!</h1>
        <p class="warning-message">
            ACCESO NO AUTORIZADO DETECTADO
        </p>
        <div class="warning-details">
            <p>Se ha detectado un intento de acceso no autorizado desde tu cuenta.</p>
            <p>Tu actividad ha sido registrada y reportada al administrador del sistema.</p>
            <p>ID de Sesión: {{ session()->getId() }}</p>
            <p>IP: {{ request()->ip() }}</p>
            <p>Hora: {{ now()->format('H:i:s') }}</p>
        </div>
    </div>

    <script>
        // Efecto Matrix
        const canvas = document.getElementById('matrix');
        const ctx = canvas.getContext('2d');

        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#$%^&*()';
        const fontSize = 14;
        const columns = canvas.width / fontSize;

        const drops = [];
        for (let i = 0; i < columns; i++) {
            drops[i] = 1;
        }

        function draw() {
            ctx.fillStyle = 'rgba(0, 0, 0, 0.05)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            ctx.fillStyle = '#0F0';
            ctx.font = fontSize + 'px monospace';

            for (let i = 0; i < drops.length; i++) {
                const text = characters.charAt(Math.floor(Math.random() * characters.length));
                ctx.fillText(text, i * fontSize, drops[i] * fontSize);

                if (drops[i] * fontSize > canvas.height && Math.random() > 0.975) {
                    drops[i] = 0;
                }
                drops[i]++;
            }
        }

        setInterval(draw, 33);
    </script>
</body>
</html> 