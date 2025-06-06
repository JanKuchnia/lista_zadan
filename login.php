<?php
// Konfiguracja sesji
ini_set('session.cookie_lifetime', 3600); // 1 godzina
ini_set('session.use_strict_mode', 1);
session_start();

// Funkcje pomocnicze
function czy_zalogowany() {
    return isset($_SESSION['id_uzytkownika']) && !empty($_SESSION['id_uzytkownika']);
}

function oczysc_dane($dane) {
    return htmlspecialchars(trim($dane), ENT_QUOTES, 'UTF-8');
}

function sprawdz_haslo($haslo, $hash) {
    return password_verify($haslo, $hash);
}

// Przekieruj jeśli już zalogowany
if (czy_zalogowany()) {
    header('Location: index.php');
    exit;
}

// Konfiguracja bazy danych
$host = 'localhost';
$nazwa_bazy = 'lista_zadan';
$uzytkownik = 'root';  // Zmień na swoje dane
$haslo = '';           // Zmień na swoje hasło

try {
    $pdo = new PDO("mysql:host=$host;dbname=$nazwa_bazy;charset=utf8", $uzytkownik, $haslo);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Błąd połączenia z bazą danych: " . $e->getMessage());
}

$komunikat_bledu = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = oczysc_dane($_POST['login'] ?? '');
    $haslo_input = $_POST['haslo'] ?? '';
    $zapamietaj_mnie = isset($_POST['zapamietaj_mnie']);
    
    if (empty($login) || empty($haslo_input)) {
        $komunikat_bledu = 'Proszę wypełnić wszystkie pola.';
    } else {
        // Sprawdź czy login to nazwa użytkownika czy email
        $zapytanie = $pdo->prepare("
            SELECT id, nazwa_uzytkownika, email, haslo, imie, nazwisko 
            FROM uzytkownicy 
            WHERE (nazwa_uzytkownika = ? OR email = ?) AND aktywny = 1
        ");
        $zapytanie->execute([$login, $login]);
        $uzytkownik = $zapytanie->fetch();
        
        if ($uzytkownik && sprawdz_haslo($haslo_input, $uzytkownik['haslo'])) {
            // Pomyślne logowanie
            $_SESSION['id_uzytkownika'] = $uzytkownik['id'];
            $_SESSION['nazwa_uzytkownika'] = $uzytkownik['nazwa_uzytkownika'];
            $_SESSION['imie'] = $uzytkownik['imie'];
            $_SESSION['nazwisko'] = $uzytkownik['nazwisko'];
            
            // Zaktualizuj ostatnie logowanie
            $zapytanie_update = $pdo->prepare("UPDATE uzytkownicy SET ostatnie_logowanie = NOW() WHERE id = ?");
            $zapytanie_update->execute([$uzytkownik['id']]);
            
            // Jeśli zaznaczono "Zapamiętaj mnie"
            if ($zapamietaj_mnie) {
                setcookie('zapamietaj_login', $login, time() + (30 * 24 * 3600), '/', '', false, true); // 30 dni
            }
            
            // Przekieruj do głównej strony
            header('Location: index.php');
            exit;
        } else {
            $komunikat_bledu = 'Nieprawidłowa nazwa użytkownika/email lub hasło.';
        }
    }
}

// Sprawdź czy jest zapisany login w ciasteczku
$zapamietany_login = $_COOKIE['zapamietaj_login'] ?? '';
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logowanie - Lista Zadań</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh;">
    <div class="kontener-logowania">
        <div class="naglowek-logowania">
            <h1>Logowanie</h1>
            <p>Zaloguj się, aby zarządzać swoimi zadaniami</p>
        </div>

        <div class="formularz-logowania">
            <?php if (!empty($komunikat_bledu)): ?>
                <div class="komunikat komunikat-blad">
                    <?php echo $komunikat_bledu; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="grupa-pola">
                    <label for="login" class="etykieta-pola">Nazwa użytkownika lub email</label>
                    <input type="text" id="login" name="login" class="pole-formularza" value="<?php echo $zapamietany_login; ?>" required>
                </div>

                <div class="grupa-pola">
                    <label for="haslo" class="etykieta-pola">Hasło</label>
                    <input type="password" id="haslo" name="haslo" class="pole-formularza" required>
                </div>

                <div class="grupa-pola" style="flex-direction: row; align-items: center;">
                    <input type="checkbox" id="zapamietaj_mnie" name="zapamietaj_mnie" style="margin-right: 10px;">
                    <label for="zapamietaj_mnie">Zapamiętaj mnie</label>
                </div>

                <div class="grupa-pola" style="margin-bottom: 0;">
                    <button type="submit" class="przycisk przycisk-glowny" style="width: 100%;">Zaloguj się</button>
                </div>
            </form>

            <div style="text-align: center; margin-top: 20px;">
                <p>Nie masz jeszcze konta? <a href="rejestracja.php" style="color: #667eea;">Zarejestruj się</a></p>
                <p style="margin-top: 10px;"><a href="home.php" style="color: #667eea;">Powrót do strony głównej</a></p>
            </div>
        </div>
    </div>
</body>
</html>