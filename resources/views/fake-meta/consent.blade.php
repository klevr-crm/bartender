<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fake Meta — Conectar WhatsApp</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1), 0 4px 12px rgba(0,0,0,0.08);
            padding: 40px;
            max-width: 480px;
            width: 100%;
            text-align: center;
        }
        h1 {
            font-size: 22px;
            margin-bottom: 12px;
            color: #1c1e21;
        }
        p {
            color: #606770;
            font-size: 15px;
            line-height: 1.5;
            margin-bottom: 24px;
        }
        .badge {
            display: inline-block;
            background: #e4e6eb;
            color: #050505;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .btn {
            display: inline-block;
            background: #1877f2;
            color: #fff;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
        }
        .btn:hover {
            background: #166fe5;
        }
        .note {
            margin-top: 20px;
            font-size: 13px;
            color: #8a8d91;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="badge">Simulação Local</div>
        <h1>Fake Meta — Conectar WhatsApp</h1>
        <p>
            Esta é uma simulação local do fluxo de consentimento da Meta.
            Ao continuar, você será redirecionado de volta ao CRM com um código de autorização de teste.
        </p>
        @if($redirectUrl !== '')
            <a href="{{ $redirectUrl }}" class="btn">Continuar como Bartender</a>
        @else
            <p class="note">Nenhum <em>redirect_uri</em> foi fornecido.</p>
        @endif
        <p class="note">Nenhum dado real é compartilhado com a Meta.</p>
    </div>
</body>
</html>
