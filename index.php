<?php
// Konfiguracja sesji
ini_set('session.cookie_lifetime', 3600); // 1 godzina
ini_set('session.use_strict_mode', 1);
session_start();

// Funkcje pomocnicze
function czy_zalogowany() {
    return isset($_SESSION['id_uzytkownika']) && !empty($_SESSION['id_uzytkownika']);
}

function wymagaj_logowania() {
    if (!czy_zalogowany()) {
        header('Location: login.php');
        exit;
    }
}

function oczysc_dane($dane) {
    return htmlspecialchars(trim($dane), ENT_QUOTES, 'UTF-8');
}

// Wymagaj zalogowania dla dostępu do listy zadań
wymagaj_logowania();

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

// Obsługa formularzy
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['akcja'])) {
        switch ($_POST['akcja']) {
            case 'dodaj':
                if (!empty(trim($_POST['zadanie']))) {
                    $zapytanie = $pdo->prepare("INSERT INTO zadania (tekst, data_utworzenia, id_uzytkownika) VALUES (?, NOW(), ?)");
                    $zapytanie->execute([trim($_POST['zadanie']), $_SESSION['id_uzytkownika']]);
                }
                break;
            
            case 'przelacz':
                $id_zadania = $_POST['id_zadania'];
                $zapytanie = $pdo->prepare("UPDATE zadania SET ukonczone = NOT ukonczone WHERE id = ? AND id_uzytkownika = ?");
                $zapytanie->execute([$id_zadania, $_SESSION['id_uzytkownika']]);
                break;
            
            case 'usun':
                $id_zadania = $_POST['id_zadania'];
                $zapytanie = $pdo->prepare("DELETE FROM zadania WHERE id = ? AND id_uzytkownika = ?");
                $zapytanie->execute([$id_zadania, $_SESSION['id_uzytkownika']]);
                break;
            
            case 'edytuj':
                $id_zadania = $_POST['id_zadania'];
                $nowy_tekst = trim($_POST['nowy_tekst']);
                if (!empty($nowy_tekst)) {
                    $zapytanie = $pdo->prepare("UPDATE zadania SET tekst = ? WHERE id = ? AND id_uzytkownika = ?");
                    $zapytanie->execute([$nowy_tekst, $id_zadania, $_SESSION['id_uzytkownika']]);
                }
                break;
            
            case 'wyczysc_ukonczone':
                $zapytanie = $pdo->prepare("DELETE FROM zadania WHERE ukonczone = 1 AND id_uzytkownika = ?");
                $zapytanie->execute([$_SESSION['id_uzytkownika']]);
                break;
        }
    }
    
    // Przekierowanie aby uniknąć ponownego wysłania formularza
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Pobierz wszystkie zadania dla zalogowanego użytkownika
$zapytanie = $pdo->prepare("SELECT * FROM zadania WHERE id_uzytkownika = ? ORDER BY data_utworzenia DESC");
$zapytanie->execute([$_SESSION['id_uzytkownika']]);
$zadania = $zapytanie->fetchAll(PDO::FETCH_ASSOC);

$wszystkie_zadania = count($zadania);
$ukonczone_zadania = count(array_filter($zadania, function($zadanie) { 
    return $zadanie['ukonczone']; 
}));
$oczekujace_zadania = $wszystkie_zadania - $ukonczone_zadania;

// Ustawienia czasowe
date_default_timezone_set('Europe/Warsaw');
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista Zadań - PHP</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="kontener">
        <div class="naglowek">
            <h1>📋 Moja Lista Zadań</h1>
            <p>Witaj, <?php echo oczysc_dane($_SESSION['imie'] . ' ' . $_SESSION['nazwisko']); ?>! 
               <a href="logout.php" style="color: white; text-decoration: underline;">Wyloguj się</a></p>
            <div class="statystyki">
                <div class="statystyka">
                    <span class="liczba-statystyka"><?php echo $wszystkie_zadania; ?></span>
                    <span>Wszystkich zadań</span>
                </div>
                <div class="statystyka">
                    <span class="liczba-statystyka"><?php echo $oczekujace_zadania; ?></span>
                    <span>Oczekujących</span>
                </div>
                <div class="statystyka">
                    <span class="liczba-statystyka"><?php echo $ukonczone_zadania; ?></span>
                    <span>Ukończonych</span>
                </div>
            </div>
        </div>

        <div class="formularz-dodawania">
            <form method="POST" class="grupa-input">
                <input type="hidden" name="akcja" value="dodaj">
                <input type="text" name="zadanie" class="pole-zadania" placeholder="Co trzeba zrobić?" required>
                <button type="submit" class="przycisk przycisk-glowny">Dodaj zadanie</button>
            </form>
        </div>

        <div class="sekcja-zadan">
            <?php if (empty($zadania)): ?>
                <div class="pusty-stan">
                    <div class="ikona-pustego-stanu">✨</div>
                    <h3>Brak zadań!</h3>
                    <p>Dodaj swoje pierwsze zadanie powyżej, aby zacząć.</p>
                </div>
            <?php else: ?>
                <div class="naglowek-zadan">
                    <h3>Twoje zadania</h3>
                    <?php if ($ukonczone_zadania > 0): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="akcja" value="wyczysc_ukonczone">
                            <button type="submit" class="przycisk przycisk-drugorzedny przycisk-maly">Wyczyść ukończone</button>
                        </form>
                    <?php endif; ?>
                </div>

                <?php foreach ($zadania as $zadanie): ?>
                    <div class="element-zadania <?php echo $zadanie['ukonczone'] ? 'ukonczone' : ''; ?>">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="akcja" value="przelacz">
                            <input type="hidden" name="id_zadania" value="<?php echo $zadanie['id']; ?>">
                            <input type="checkbox" class="checkbox-zadania" <?php echo $zadanie['ukonczone'] ? 'checked' : ''; ?> onchange="this.form.submit()">
                        </form>

                        <div style="flex: 1;">
                            <div class="tekst-zadania <?php echo $zadanie['ukonczone'] ? 'ukonczone' : ''; ?>">
                                <?php echo htmlspecialchars($zadanie['tekst']); ?>
                            </div>
                            <div class="meta-zadania">
                                Dodano: <?php echo date('j M Y, G:i', strtotime($zadanie['data_utworzenia'])); ?>
                            </div>
                        </div>

                        <div class="akcje-zadania">
                            <button onclick="edytujZadanie('<?php echo $zadanie['id']; ?>')" class="przycisk przycisk-drugorzedny przycisk-maly">Edytuj</button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Czy na pewno chcesz usunąć to zadanie?')">
                                <input type="hidden" name="akcja" value="usun">
                                <input type="hidden" name="id_zadania" value="<?php echo $zadanie['id']; ?>">
                                <button type="submit" class="przycisk przycisk-niebezpieczny przycisk-maly">Usuń</button>
                            </form>
                        </div>
                    </div>

                    <!-- Ukryty formularz edycji dla każdego zadania -->
                    <div id="edycja-<?php echo $zadanie['id']; ?>" class="element-zadania" style="display: none;">
                        <form method="POST" class="formularz-edycji">
                            <input type="hidden" name="akcja" value="edytuj">
                            <input type="hidden" name="id_zadania" value="<?php echo $zadanie['id']; ?>">
                            <input type="text" name="nowy_tekst" class="pole-edycji" value="<?php echo htmlspecialchars($zadanie['tekst']); ?>" required>
                            <button type="submit" class="przycisk przycisk-glowny przycisk-maly">Zapisz</button>
                            <button type="button" onclick="anulujEdycje('<?php echo $zadanie['id']; ?>')" class="przycisk przycisk-drugorzedny przycisk-maly">Anuluj</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function edytujZadanie(idZadania) {
            // Ukryj oryginalne zadanie
            const elementyZadan = document.querySelectorAll('.element-zadania:not([id])');
            const formularzEdycji = document.getElementById('edycja-' + idZadania);
            
            // Ukryj wszystkie zadania i pokaż tylko formularz edycji
            elementyZadan.forEach(element => {
                if (element.querySelector('input[value="' + idZadania + '"]')) {
                    element.style.display = 'none';
                }
            });
            
            formularzEdycji.style.display = 'flex';
            formularzEdycji.querySelector('.pole-edycji').focus();
        }

        function anulujEdycje(idZadania) {
            // Pokaż oryginalne zadanie
            const elementyZadan = document.querySelectorAll('.element-zadania:not([id])');
            const formularzEdycji = document.getElementById('edycja-' + idZadania);
            
            elementyZadan.forEach(element => {
                if (element.querySelector('input[value="' + idZadania + '"]')) {
                    element.style.display = 'flex';
                }
            });
            
            formularzEdycji.style.display = 'none';
        }

        // Automatyczne skupienie na polu zadania po załadowaniu strony
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('.pole-zadania').focus();
        });
    </script>
</body>
</html>