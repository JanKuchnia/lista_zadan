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

function waliduj_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function zaszyfruj_haslo($haslo) {
    return password_hash($haslo, PASSWORD_DEFAULT);
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

$komunikaty_bledu = [];
$komunikat_sukcesu = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nazwa_uzytkownika = oczysc_dane($_POST['nazwa_uzytkownika'] ?? '');
    $email = oczysc_dane($_POST['email'] ?? '');
    $haslo = $_POST['haslo'] ?? '';
    $powtorz_haslo = $_POST['powtorz_haslo'] ?? '';
    $imie = oczysc_dane($_POST['imie'] ?? '');
    $nazwisko = oczysc_dane($_POST['nazwisko'] ?? '');
    
    // Walidacja danych
    if (empty($nazwa_uzytkownika)) {
        $komunikaty_bledu[] = 'Nazwa użytkownika jest wymagana.';
    } elseif (strlen($nazwa_uzytkownika) < 3) {
        $komunikaty_bledu[] = 'Nazwa użytkownika musi mieć co najmniej 3 znaki.';
    }
    
    if (empty($email)) {
        $komunikaty_bledu[] = 'Adres email jest wymagany.';
    } elseif (!waliduj_email($email)) {
        $komunikaty_bledu[] = 'Nieprawidłowy format adresu email.';
    }
    
    if (empty($haslo)) {
        $komunikaty_bledu[] = 'Hasło jest wymagane.';
    } elseif (strlen($haslo) < 6) {
        $komunikaty_bledu[] = 'Hasło musi mieć co najmniej 6 znaków.';
    }
    
    if ($haslo !== $powtorz_haslo) {
        $komunikaty_bledu[] = 'Hasła nie są identyczne.';
    }
    
    if (empty($imie)) {
        $komunikaty_bledu[] = 'Imię jest wymagane.';
    }
    
    if (empty($nazwisko)) {
        $komunikaty_bledu[] = 'Nazwisko jest wymagane.';
    }
    
    // Sprawdź czy nazwa użytkownika i email są unikalne
    if (empty($komunikaty_bledu)) {
        $zapytanie = $pdo->prepare("SELECT COUNT(*) FROM uzytkownicy WHERE nazwa_uzytkownika = ? OR email = ?");
        $zapytanie->execute([$nazwa_uzytkownika, $email]);
        
        if ($zapytanie->fetchColumn() > 0) {
            // Sprawdź dokładnie co jest zajęte
            $zapytanie = $pdo->prepare("SELECT nazwa_uzytkownika, email FROM uzytkownicy WHERE nazwa_uzytkownika = ? OR email = ?");
            $zapytanie->execute([$nazwa_uzytkownika, $email]);
            $istniejacy = $zapytanie->fetch();
            
            if ($istniejacy['nazwa_uzytkownika'] === $nazwa_uzytkownika) {
                $komunikaty_bledu[] = 'Nazwa użytkownika jest już zajęta.';
            }
            if ($istniejacy['email'] === $email) {
                $komunikaty_bledu[] = 'Adres email jest już zarejestrowany.';
            }
        }
    }
    
    // Jeśli brak błędów, zarejestruj użytkownika
    if (empty($komunikaty_bledu)) {
        try {
            $haslo_hash = zaszyfruj_haslo($haslo);
            
            $zapytanie = $pdo->prepare("
                INSERT INTO uzytkownicy (nazwa_uzytkownika, email, haslo, imie, nazwisko, aktywny) 
                VALUES (?, ?, ?, ?, ?, 1)
            ");
            
            if ($zapytanie->execute([$nazwa_uzytkownika, $email, $haslo_hash, $imie, $nazwisko])) {
                $komunikat_sukcesu = 'Konto zostało utworzone pomyślnie! Możesz się teraz zalogować.';
                
                // Wyczyść formularz
                $nazwa_uzytkownika = $email = $imie = $nazwisko = '';
            }
        } catch (PDOException $e) {
            $komunikaty_bledu[] = 'Wystąpił błąd podczas rejestracji. Spróbuj ponownie.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejestracja - Lista Zadań</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh;">
    <div class="kontener-rejestracji">
        <div class="naglowek-rejestracji">
            <h1>Rejestracja</h1>
            <p>Utwórz konto, aby korzystać z aplikacji Lista Zadań</p>
        </div>

        <div class="formularz-rejestracji">
            <?php if (!empty($komunikat_sukcesu)): ?>
                <div class="komunikat komunikat-sukces">
                    <?php echo $komunikat_sukcesu; ?>
                    <p style="margin-top: 10px;"><a href="login.php" style="color: #155724; text-decoration: underline;">Przejdź do logowania</a></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($komunikaty_bledu)): ?>
                <div class="komunikat komunikat-blad">
                    <strong>Wystąpiły błędy:</strong>
                    <ul class="lista-bledow">
                        <?php foreach ($komunikaty_bledu as $blad): ?>
                            <li><?php echo $blad; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (empty($komunikat_sukcesu)): ?>
                <form method="POST" action="">
                    <div class="grupa-pola">
                        <label for="nazwa_uzytkownika" class="etykieta-pola">Nazwa użytkownika</label>
                        <input type="text" id="nazwa_uzytkownika" name="nazwa_uzytkownika" class="pole-formularza" value="<?php echo isset($nazwa_uzytkownika) ? $nazwa_uzytkownika : ''; ?>" required>
                    </div>

                    <div class="grupa-pola">
                        <label for="email" class="etykieta-pola">Adres email</label>
                        <input type="email" id="email" name="email" class="pole-formularza" value="<?php echo isset($email) ? $email : ''; ?>" required>
                    </div>

                    <div class="grupa-pola">
                        <label for="haslo" class="etykieta-pola">Hasło</label>
                        <input type="password" id="haslo" name="haslo" class="pole-formularza" required>
                    </div>

                    <div class="grupa-pola">
                        <label for="powtorz_haslo" class="etykieta-pola">Powtórz hasło</label>
                        <input type="password" id="powtorz_haslo" name="powtorz_haslo" class="pole-formularza" required>
                    </div>

                    <div class="grupa-pola">
                        <label for="imie" class="etykieta-pola">Imię</label>
                        <input type="text" id="imie" name="imie" class="pole-formularza" value="<?php echo isset($imie) ? $imie : ''; ?>" required>
                    </div>

                    <div class="grupa-pola">
                        <label for="nazwisko" class="etykieta-pola">Nazwisko</label>
                        <input type="text" id="nazwisko" name="nazwisko" class="pole-formularza" value="<?php echo isset($nazwisko) ? $nazwisko : ''; ?>" required>
                    </div>

                    <div class="grupa-pola" style="margin-bottom: 0;">
                        <button type="submit" class="przycisk przycisk-glowny" style="width: 100%;">Zarejestruj się</button>
                    </div>
                </form>
            <?php endif; ?>

            <div style="text-align: center; margin-top: 20px;">
                <p>Masz już konto? <a href="login.php" style="color: #667eea;">Zaloguj się</a></p>
                <p style="margin-top: 10px;"><a href="home.php" style="color: #667eea;">Powrót do strony głównej</a></p>
            </div>
        </div>
    </div>
</body>
</html>