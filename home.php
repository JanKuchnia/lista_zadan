<?php
// Konfiguracja sesji
ini_set('session.cookie_lifetime', 3600); // 1 godzina
ini_set('session.use_strict_mode', 1);
session_start();

// Funkcja sprawdzania czy użytkownik jest zalogowany
function czy_zalogowany() {
    return isset($_SESSION['id_uzytkownika']) && !empty($_SESSION['id_uzytkownika']);
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista Zadań - Twój Osobisty Organizator</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body style="background: white; padding: 0;">
    <header class="showcase-header">
        <nav class="showcase-nav">
            <div class="showcase-logo">Lista Zadań</div>
            <div class="showcase-menu">
                <a href="home.php">Strona główna</a>
                <a href="#funkcje">Funkcje</a>
                <?php if (czy_zalogowany()): ?>
                    <a href="index.php">Moje zadania</a>
                    <a href="logout.php">Wyloguj się</a>
                <?php else: ?>
                    <a href="login.php">Logowanie</a>
                    <a href="rejestracja.php">Rejestracja</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <section class="showcase-hero">
        <h1>Zarządzaj swoimi zadaniami z łatwością</h1>
        <p>Lista Zadań to nowoczesna aplikacja, która pomoże Ci zorganizować codzienne obowiązki i zwiększyć produktywność.</p>
        <?php if (czy_zalogowany()): ?>
            <a href="index.php" class="showcase-cta">Przejdź do moich zadań</a>
        <?php else: ?>
            <a href="rejestracja.php" class="showcase-cta">Rozpocznij za darmo</a>
        <?php endif; ?>
    </section>

    <section class="showcase-features" id="funkcje">
        <div class="showcase-feature">
            <div class="showcase-feature-icon">
                <i class="fas fa-tasks"></i>
            </div>
            <h3>Zarządzanie zadaniami</h3>
            <p>Dodawaj, edytuj i usuwaj zadania. Oznaczaj je jako ukończone i śledź swój postęp.</p>
        </div>

        <div class="showcase-feature">
            <div class="showcase-feature-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <h3>Bezpieczne konto</h3>
            <p>Twoje dane są chronione. Dostęp do zadań masz tylko Ty po zalogowaniu się na swoje konto.</p>
        </div>

        <div class="showcase-feature">
            <div class="showcase-feature-icon">
                <i class="fas fa-mobile-alt"></i>
            </div>
            <h3>Responsywny design</h3>
            <p>Korzystaj z aplikacji na dowolnym urządzeniu - komputerze, tablecie czy smartfonie.</p>
        </div>
    </section>

    <footer class="showcase-footer">
        <p>&copy; 2023 Lista Zadań. Wszystkie prawa zastrzeżone.</p>
    </footer>
</body>
</html>