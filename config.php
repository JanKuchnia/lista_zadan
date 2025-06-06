<?php
// config.php - Plik konfiguracyjny

// Konfiguracja bazy danych
define('DB_HOST', 'localhost');
define('DB_NAME', 'lista_zadan');
define('DB_USER', 'uzytkownik_todo');  // Zmień na swoje dane
define('DB_PASS', 'bezpieczne_haslo123');  // Zmień na swoje hasło

// Konfiguracja sesji
ini_set('session.cookie_lifetime', 3600); // 1 godzina
ini_set('session.use_strict_mode', 1);
session_start();

// Funkcja połączenia z bazą danych
function polacz_z_baza() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch(PDOException $e) {
        die("Błąd połączenia z bazą danych: " . $e->getMessage());
    }
}

// Funkcja sprawdzania czy użytkownik jest zalogowany
function czy_zalogowany() {
    return isset($_SESSION['id_uzytkownika']) && !empty($_SESSION['id_uzytkownika']);
}

// Funkcja przekierowania jeśli nie zalogowany
function wymagaj_logowania() {
    if (!czy_zalogowany()) {
        header('Location: logowanie.php');
        exit;
    }
}

// Funkcja pobierania danych zalogowanego użytkownika
function pobierz_dane_uzytkownika() {
    if (!czy_zalogowany()) {
        return null;
    }
    
    $pdo = polacz_z_baza();
    $zapytanie = $pdo->prepare("SELECT * FROM uzytkownicy WHERE id = ?");
    $zapytanie->execute([$_SESSION['id_uzytkownika']]);
    return $zapytanie->fetch();
}

// Funkcja sanityzacji danych wejściowych
function oczysc_dane($dane) {
    return htmlspecialchars(trim($dane), ENT_QUOTES, 'UTF-8');
}

// Funkcja walidacji emaila
function waliduj_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Funkcja generowania bezpiecznego hasła hash
function zaszyfruj_haslo($haslo) {
    return password_hash($haslo, PASSWORD_DEFAULT);
}

// Funkcja weryfikacji hasła
function sprawdz_haslo($haslo, $hash) {
    return password_verify($haslo, $hash);
}

// Funkcja wylogowania
function wyloguj() {
    session_destroy();
    header('Location: logowanie.php');
    exit;
}

// Funkcja formatowania daty po polsku
function formatuj_date_pl($data) {
    $miesiace = [
        1 => 'stycznia', 2 => 'lutego', 3 => 'marca', 4 => 'kwietnia',
        5 => 'maja', 6 => 'czerwca', 7 => 'lipca', 8 => 'sierpnia',
        9 => 'września', 10 => 'października', 11 => 'listopada', 12 => 'grudnia'
    ];
    
    $timestamp = strtotime($data);
    $dzien = date('j', $timestamp);
    $miesiac = $miesiace[date('n', $timestamp)];
    $rok = date('Y', $timestamp);
    $godzina = date('H:i', $timestamp);
    
    return "$dzien $miesiac $rok, $godzina";
}

// Ustawienia czasowe
date_default_timezone_set('Europe/Warsaw');
?>