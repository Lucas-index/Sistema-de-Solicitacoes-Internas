<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f4f4f5; margin: 0; padding: 24px; }
    .card { background: #fff; border-radius: 8px; max-width: 520px; margin: 0 auto; padding: 32px; border: 1px solid #e4e4e7; }
    .logo { font-size: 13px; color: #71717a; margin-bottom: 4px; }
    .titulo { font-size: 18px; font-weight: 600; color: #18181b; margin: 0 0 20px; }
    .mensagem { font-size: 15px; color: #3f3f46; line-height: 1.6; margin: 0 0 28px; }
    .rodape { font-size: 12px; color: #a1a1aa; border-top: 1px solid #f4f4f5; padding-top: 16px; margin-top: 8px; }
  </style>
</head>
<body>
  <div class="card">
    <p class="logo">Empresa</p>
    <p class="titulo">Olá, {{ $nomeDestinatario }}</p>
    <p class="mensagem">{{ $mensagem }}</p>
    <p class="rodape">
      Este é um aviso automático do Sistema de Solicitações Internas.<br>
      Acesse o sistema para ver os detalhes.
    </p>
  </div>
</body>
</html>