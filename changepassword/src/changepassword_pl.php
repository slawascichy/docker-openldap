<?php
/**
 * Strona zmiany hasła LDAP
 * Zoptymalizowana pod OpenLDAP z nakładkami ppolicy i lastbind
 */

$message = array();
$message_css = "";

function changePassword($user, $oldPassword, $newPassword, $newPasswordCnf) {
    global $message, $message_css;
    
    try {
        // 1. Walidacja jakości po stronie klienta (PHP)
        if ($newPassword !== $newPasswordCnf) {
            $message[] = "Błąd: Nowe hasła nie są identyczne.";
            return false;
        }
        if (strlen($newPassword) < 12) {
            $message[] = "Błąd: Hasło musi mieć co najmniej 12 znaków.";
            return false;
        }
        if (!preg_match("/\d/", $newPassword)) { // [0-9]
            $message[] = "Błąd: Hasło musi zawierać co najmniej jedną cyfrę.";
            return false;
        }
        if (!preg_match("/[A-Z]/", $newPassword)) {
            $message[] = "Błąd: Hasło musi zawierać co najmniej jedną wielką literę.";
            return false;
        }
        if (!preg_match("/[a-z]/", $newPassword)) {
            $message[] = "Błąd: Hasło musi zawierać co najmniej jedną małą literę.";
            return false;
        }
        
        $server = getenv('LDAP_SERVER') ?: "openldap";
        $base_dn = "dc=ibpm,dc=pro";
        $tech_bind_dn = getenv('LDAP_TECH_USER_DN');
        $tech_bind_pw = getenv('LDAP_TECH_USER_PW');
        $user_get = array('count' => 0);
        
        $con = ldap_connect($server);
        ldap_set_option($con, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($con, LDAP_OPT_REFERRALS, 0);
        
        // 2. Bind kontem technicznym
        if (!@ldap_bind($con, $tech_bind_dn, $tech_bind_pw)) {
            throw new Exception("Technical Bind Failed: " . ldap_error($con));
        }
        
        // 3. Szukanie użytkownika
        $filter = "(|(uid=$user)(mail=$user))";
        $user_search = @ldap_search($con, $base_dn, $filter);
        if (!$user_search) {
            throw new Exception("LDAP Search Failed: " . ldap_error($con));
        }
        
        // 4. Pobieranie danych
        $user_get = ldap_get_entries($con, $user_search);
        if ($user_get['count'] == 0) {
            $message[] = "Błąd: Użytkownik nie został znaleziony.";
            return false;
        }
        $user_dn = $user_get[0]["dn"];
        $user_id = $user_get[0]["uid"][0];
        
        // 5. Weryfikacja STAREGO HASŁA (Re-bind)
        if (!@ldap_bind($con, $user_dn, $oldPassword)) {
            $errno = ldap_errno($con);
            if ($errno == 49) {
                $message[] = "Błąd: Nieprawidłowe obecne hasło lub konto jest zablokowane.";
            } else {
                $message[] = "Błąd: Problem z uwierzytelnianiem LDAP ($errno).";
            }
            return false;
        }
        
        // 6. Zmiana hasła (Modify)
        $entry = array();
        $entry["userPassword"] = $newPassword;
        if (!@ldap_modify($con, $user_dn, $entry)) {
            $errno = ldap_errno($con);
            if ($errno == 19 || $errno == 53) {
                $message[] = "Błąd polityki: Hasło odrzucone (sprawdź historię haseł).";
            } else {
                $message[] = "Błąd serwera ($errno).";
            }
            return false;
        }
        
        $message_css = "yes";
        $message[] = "Sukces: Hasło dla użytkownika $user_id zostało zmienione.";
        
        // 7. Powiadomienie e-mail jeżeli atrybut mail został zdefiniowany
        $mail_address = isset($user_get[0]["mail"][0]) ? $user_get[0]["mail"][0] : null;
        $first_name = isset($user_get[0]["givenname"][0]) ? $user_get[0]["givenname"][0] : $user_id;
        
        if ($mail_address) {
            $from_env = getenv('MAIL_FROM_ADDRESS') ?: "IBPM Change Password Service <no-reply@ibpm.pro>";
            $subject = "Notyfikacja zmiany hasła - IBPM";
            $encoded_subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
            $body = "Witaj $first_name,\n\n" .
            "Hasło do Twojego konta ($user_id) zostało pomyślnie zmienione. " .
            "Jeżeli to nie Ty dokonałeś tej zmiany, skontaktuj się niezwłocznie z administratorem.";
            
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type: text/plain; charset=UTF-8" . "\r\n";
            // PHP mail() poprawnie obsłuży format "Nazwa <email@domena.pl>"
            $headers .= "From: " . $from_env . "\r\n";
            @mail($mail_address, $encoded_subject, $body, $headers);
        } else {
            $message[] = "Ostrzeżenie: Powiadomienie e-mail nie zostało wysłane (brak adresu e-mail w profilu).";
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Password Change Critical Error: " . $e->getMessage());
        $message[] = "Błąd: Wystąpił wewnętrzny błąd serwera. Skontaktuj się z adminem.";
        return false;
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Zmiana Hasła - IBPM S.A.</title>
    <style>
        body { font-family: Verdana, Arial, sans-serif; background-color: #113456; color: white; font-size: 0.9em; margin: 0; padding: 20px; }
        #container { width: 500px; margin: 50px auto; text-align: center; background: rgba(255,255,255,0.05); padding: 20px; border-radius: 15px; }
        table { width: 100%; margin-top: 20px; }
        th { text-align: right; padding: 10px; }
        td { padding: 10px; }
        input { padding: 5px; width: 180px; }
        .msg_yes { color: #d4ead4; background: rgba(0,255,0,0.1); border: 1px solid green; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .msg_no { color: #ffbaba; background: rgba(255,0,0,0.1); border: 1px solid red; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .logo { margin-bottom: 20px; }
        button, input[type="submit"] { padding: 8px 15px; cursor: pointer; }
    </style>
</head>
<body>
<div id="container">
    <div class="logo">
        <a href="http://intranet.ibpm.pro/pl"><img src="img/IBPM-R-logo-white.png" height="25" alt="IBPM S.A."/></a>
        <h2>Zarządzanie Tożsamością IBPM S.A.</h2>
    </div>
    <h3>Zmień swoje hasło</h3>
    <p style="font-size: 0.85em;">Wymagania dotyczące hasła:<br>
    Minimum 12 znaków, w tym co najmniej jedna wielka litera, jedna mała i jedna cyfra.</p>
    <?php
    if (isset($_POST["submitted"])) {
        changePassword($_POST['username'], $_POST['oldPassword'], $_POST['newPassword1'], $_POST['newPassword2']);
        $class = ($message_css == "yes") ? "msg_yes" : "msg_no";
        echo "<div class='$class'>";
        foreach ($message as $one) { echo "<p>$one</p>"; }
        echo "</div>";
    }
    ?>
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
        <table>
            <tr><th>Użytkownik / E-mail:</th><td><input name="username" type="text" required></td></tr>
            <tr><th>Obecne hasło:</th><td><input name="oldPassword" type="password" required></td></tr>
            <tr><th>Nowe hasło:</th><td><input name="newPassword1" type="password" required></td></tr>
            <tr><th>Potwierdź nowe hasło:</th><td><input name="newPassword2" type="password" required></td></tr>
            <tr>
                <td colspan="2" style="text-align: center;">
                    <input name="submitted" type="submit" value="Zaktualizuj hasło">
                    <button type="button" onclick="window.location.href='index.php';">Anuluj</button>
                </td>
            </tr>
        </table>
    </form>
</div>
</body>
</html>