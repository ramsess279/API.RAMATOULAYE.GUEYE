<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code de vérification - Banque</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        .verification-code {
            background-color: #f8f9fa;
            border: 2px solid #007bff;
            border-radius: 5px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
            font-size: 32px;
            font-weight: bold;
            color: #007bff;
            letter-spacing: 5px;
        }
        .instructions {
            margin: 20px 0;
            padding: 15px;
            background-color: #e9ecef;
            border-left: 4px solid #007bff;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
        .contact-info {
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">BANQUE SÉNÉGAL</div>
            <h1>Code de vérification</h1>
        </div>

        <p>Bonjour <strong>{{ $nom }} {{ $prenom }}</strong>,</p>

        <p>Votre compte bancaire a été créé avec succès. Pour finaliser votre inscription et activer votre compte, veuillez utiliser le code de vérification suivant :</p>

        <div class="verification-code">
            {{ $code }}
        </div>

        <div class="instructions">
            <h3>Instructions :</h3>
            <ul>
                <li>Utilisez ce code lors de votre première connexion à l'application bancaire</li>
                <li>Le code est valable pendant <strong>{{ $expires_in }} minutes</strong></li>
                <li>Ne partagez ce code avec personne</li>
                <li>Le code expirera automatiquement après la période indiquée</li>
            </ul>
        </div>

        <div class="warning">
            <strong>⚠️ Sécurité :</strong> Si vous n'avez pas demandé la création de ce compte bancaire, veuillez ignorer cet email et contacter immédiatement notre service client.
        </div>

        <p>Nous vous remercions de votre confiance et sommes heureux de vous compter parmi nos clients.</p>

        <p>Cordialement,<br>
        <strong>L'équipe de la Banque Sénégalaise</strong></p>

        <div class="footer">
            <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>

            <div class="contact-info">
                <p><strong>Service Client :</strong> +221 33 123 45 67</p>
                <p><strong>Email :</strong> support@banque.sn</p>
                <p><strong>Site web :</strong> www.banque.sn</p>
            </div>
        </div>
    </div>
</body>
</html>