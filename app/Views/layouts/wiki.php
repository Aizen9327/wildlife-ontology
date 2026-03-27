<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentation — Novyra Graphis</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
    <nav class="nav">
        <div class="nav-brand">
            <a href="/">
                <svg class="nav-logo-svg" viewBox="0 0 34 34" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <polygon points="17,2 31,9.5 31,24.5 17,32 3,24.5 3,9.5" fill="none" stroke="#22c55e" stroke-width="1.5"/>
                    <polygon points="17,7 26.5,12.5 26.5,21.5 17,27 7.5,21.5 7.5,12.5" fill="rgba(34,197,94,0.1)" stroke="#16a34a" stroke-width="1"/>
                    <circle cx="17" cy="17" r="3" fill="#22c55e"/>
                </svg>
                <div class="nav-wordmark">
                    <span class="nav-agency">Novyra</span>
                    <span class="nav-app">Novyra <span class="hl">Graphis</span></span>
                </div>
            </a>
        </div>
        <div class="nav-links">
            <a href="/" class="nav-link">Visualiseur</a>
            <div class="nav-divider"></div>
            <a href="/wiki" class="nav-link">Documentation</a>
            <div class="nav-divider"></div>
            <span class="nav-badge">OWL / RDF</span>
        </div>
    </nav>
    <main class="main">
        <?= $content ?>
    </main>
</body>
</html>
