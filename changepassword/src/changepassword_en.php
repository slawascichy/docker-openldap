<?php
/**
 * LDAP Password Change Page
 * Optimized for OpenLDAP with ppolicy & lastbind
 */

$message = array();
$message_css = "";

function changePassword($user, $oldPassword, $newPassword, $newPasswordCnf) {
    global $message, $message_css;
    
    try {

        // 1. Client-side Quality Validation (PHP)
        if ($newPassword !== $newPasswordCnf) {
            $message[] = "Error: New passwords do not match.";
            return false;
        }
        if (strlen($newPassword) < 12) {
            $message[] = "Error: Password must be at least 12 characters long.";
            return false;
        }
        if (!preg_match("/\d/", $newPassword)) { // [0-9]
            $message[] = "Error: Password must contain at least one number.";
            return false;
        }
        if (!preg_match("/[A-Z]/", $newPassword)) {
            $message[] = "Error: Password must contain at least one uppercase letter.";
            return false;
        }
        if (!preg_match("/[a-z]/", $newPassword)) {
            $message[] = "Error: Password must contain at least one lowercase letter.";
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
        
        // 2. Bind with a technical account to be able to search
        if (!@ldap_bind($con, $tech_bind_dn, $tech_bind_pw)) {
            throw new Exception("Technical Bind Failed: " . ldap_error($con));
        }
        
        // 3. Search - make sure the filter is identical to the one tested in Studio
        $filter = "(|(uid=$user)(mail=$user))";
        $user_search = @ldap_search($con, $base_dn, $filter);
        if (!$user_search) {
            throw new Exception("LDAP Search Failed: " . ldap_error($con));
        }

        // 4. Downloading user data
        $user_get = ldap_get_entries($con, $user_search);
        if ($user_get['count'] == 0) {
            $message[] = "Error: User not found.";
            return false;
        }
        $user_dn = $user_get[0]["dn"];
        $user_id = $user_get[0]["uid"][0];
        
        
        // 5. Verify OLD PASSWORD (Re-bind as user)
        // This checks whether the user has entered the correct password and is not blocked.
        if (!@ldap_bind($con, $user_dn, $oldPassword)) {
            $errno = ldap_errno($con);
            if ($errno == 49) {
                $message[] = "Error: Invalid current password or account is locked.";
            } else {
                $message[] = "Error: LDAP authentication issue ($errno).";
            }
            return false;
        }
        
        // 6. Change Password (Modify) - we still use Plain Text for PPolicy
        $entry = array();
        $entry["userPassword"] = $newPassword;
        if (!@ldap_modify($con, $user_dn, $entry)) {
            $errno = ldap_errno($con);
            if ($errno == 19 || $errno == 53) {
                $message[] = "Policy Error: Password rejected (check history).";
            } else {
                $message[] = "Server Error ($errno).";
            }
            return false;
        }

        $message_css = "yes";
        $message[] = "Success: Password for user $user_id has been changed.";
        
        // 7. Email notification if mail attribute is defined
        $mail_address = isset($user_get[0]["mail"][0]) ? $user_get[0]["mail"][0] : null;
        $first_name = isset($user_get[0]["givenname"][0]) ? $user_get[0]["givenname"][0] : $user_id;
        
        if ($mail_address) {
            $from_env = getenv('MAIL_FROM_ADDRESS') ?: "IBPM Change Password Service <no-reply@ibpm.pro>";
            $subject = "Password Change Notification - IBPM";
            $encoded_subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
            $body = "Hello $first_name,\n\n" .
            "The password for your account ($user_id) has been changed successfully. " .
            "If you did not perform this action, please contact the administrator immediately.";
            
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type: text/plain; charset=UTF-8" . "\r\n";
            $headers .= "From: " . $from_env . "\r\n";
            @mail($mail_address, $encoded_subject, $body, $headers);
        } else {
            $message[] = "Warning: Email notification was not sent (missing email address in profile).";
        }
        
        return true;
        
    } catch (Exception $e) {
        // We log the error to the system, but we do NOT show it to the user
        error_log("Password Change Critical Error: " . $e->getMessage());
        $message[] = "Error: An internal server error occurred. Please contact admin.";
        return false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password - IBPM S.A.</title>
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
        <a href="http://intranet.ibpm.pro"><img src="img/IBPM-R-logo-white.png" height="50" alt="IBPM S.A."/></a>
        <h2>IBPM S.A. Identity Management</h2>
    </div>

    <h3>Change Your Password</h3>
    <p style="font-size: 0.85em;">Password Requirements:<br>
    Min. 12 characters, at least one uppercase, one lowercase, and one number.</p>
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
            <tr><th>Username / E-mail:</th><td><input name="username" type="text" required></td></tr>
            <tr><th>Current Password:</th><td><input name="oldPassword" type="password" required></td></tr>
            <tr><th>New Password:</th><td><input name="newPassword1" type="password" required></td></tr>
            <tr><th>Confirm New Password:</th><td><input name="newPassword2" type="password" required></td></tr>
            <tr>
                <td colspan="2" style="text-align: center;">
                    <input name="submitted" type="submit" value="Update Password">
                    <button type="button" onclick="window.location.href='index.php';">Cancel</button>
                </td>
            </tr>
        </table>
    </form>
</div>

</body>
</html>