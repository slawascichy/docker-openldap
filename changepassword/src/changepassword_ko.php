<?php
/**
 * LDAP 비밀번호 변경 페이지
 * OpenLDAP ppolicy 및 lastbind 최적화 버전
 */

$message = array();
$message_css = "";

function changePassword($user, $oldPassword, $newPassword, $newPasswordCnf) {
    global $message, $message_css;
    
    try {
        // 1. 클라이언트 측 품질 검증 (PHP)
        if ($newPassword !== $newPasswordCnf) {
            $message[] = "오류: 새 비밀번호가 일치하지 않습니다.";
            return false;
        }
        if (strlen($newPassword) < 12) {
            $message[] = "오류: 비밀번호는 최소 12자 이상이어야 합니다.";
            return false;
        }
        if (!preg_match("/\d/", $newPassword)) { // [0-9]
            $message[] = "오류: 비밀번호에 숫자가 최소 하나 포함되어야 합니다.";
            return false;
        }
        if (!preg_match("/[A-Z]/", $newPassword)) {
            $message[] = "오류: 비밀번호에 대문자가 최소 하나 포함되어야 합니다.";
            return false;
        }
        if (!preg_match("/[a-z]/", $newPassword)) {
            $message[] = "오류: 비밀번호에 소문자가 최소 하나 포함되어야 합니다.";
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
        
        // 2. 기술 계정으로 바인드
        if (!@ldap_bind($con, $tech_bind_dn, $tech_bind_pw)) {
            throw new Exception("Technical Bind Failed: " . ldap_error($con));
        }
        
        // 3. 사용자 검색
        $filter = "(|(uid=$user)(mail=$user))";
        $user_search = @ldap_search($con, $base_dn, $filter);
        if (!$user_search) {
            throw new Exception("LDAP Search Failed: " . ldap_error($con));
        }
        
        // 4. 사용자 데이터 가져오기
        $user_get = ldap_get_entries($con, $user_search);
        if ($user_get['count'] == 0) {
            $message[] = "오류: 사용자를 찾을 수 없습니다.";
            return false;
        }
        $user_dn = $user_get[0]["dn"];
        $user_id = $user_get[0]["uid"][0];
        
        // 5. 기존 비밀번호 확인 (사용자로 재바인드)
        if (!@ldap_bind($con, $user_dn, $oldPassword)) {
            $errno = ldap_errno($con);
            if ($errno == 49) {
                $message[] = "오류: 현재 비밀번호가 올바르지 않거나 계정이 잠겨 있습니다.";
            } else {
                $message[] = "오류: LDAP 인증 문제 발생 ($errno).";
            }
            return false;
        }
        
        // 6. 비밀번호 변경 (Modify)
        $entry = array();
        $entry["userPassword"] = $newPassword;
        if (!@ldap_modify($con, $user_dn, $entry)) {
            $errno = ldap_errno($con);
            if ($errno == 19 || $errno == 53) {
                $message[] = "정책 오류: 비밀번호가 거부되었습니다 (이전 사용 기록 확인).";
            } else {
                $message[] = "서버 오류 ($errno).";
            }
            return false;
        }
        
        $message_css = "yes";
        $message[] = "성공: $user_id 사용자의 비밀번호가 변경되었습니다.";
        
        // 7. mail 속성이 정의된 경우 이메일 알림 전송
        $mail_address = isset($user_get[0]["mail"][0]) ? $user_get[0]["mail"][0] : null;
        $first_name = isset($user_get[0]["givenname"][0]) ? $user_get[0]["givenname"][0] : $user_id;
        
        if ($mail_address) {
            $from_env = getenv('MAIL_FROM_ADDRESS') ?: "IBPM Change Password Service <no-reply@ibpm.pro>";
            $subject = "비밀번호 변경 알림 - IBPM";
            $encoded_subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
            $body = "안녕하세요 $first_name 님,\n\n" .
            "귀하의 계정($user_id) 비밀번호가 성공적으로 변경되었습니다. " .
            "본인이 변경하지 않았다면 즉시 관리자에게 문의하십시오.";
            
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type: text/plain; charset=UTF-8" . "\r\n";
            $headers .= "From: " . $from_env . "\r\n";
            @mail($mail_address, $encoded_subject, $body, $headers);
        } else {
            $message[] = "경고: 이메일 알림이 전송되지 않았습니다 (프로필에 이메일 주소가 없음).";
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Password Change Critical Error: " . $e->getMessage());
        $message[] = "오류: 서버 내부 오류가 발생했습니다. 관리자에게 문의하세요.";
        return false;
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>비밀번호 변경 - IBPM S.A.</title>
    <style>
        body { font-family: "Malgun Gothic", Dotum, sans-serif; background-color: #113456; color: white; font-size: 0.9em; margin: 0; padding: 20px; }
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
        <a href="http://wwwiq.ibpm.pro"><img src="img/ibpm_white.png" height="25" alt="IBPM S.A."/></a>
        <h2>IBPM S.A. 계정 관리</h2>
    </div>
    <h3>비밀번호 변경</h3>
    <p style="font-size: 0.85em;">비밀번호 요구 사항:<br>
    최소 12자 이상, 대문자, 소문자 및 숫자가 각각 하나 이상 포함되어야 합니다.</p>
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
            <tr><th>사용자 ID / 이메일:</th><td><input name="username" type="text" required></td></tr>
            <tr><th>현재 비밀번호:</th><td><input name="oldPassword" type="password" required></td></tr>
            <tr><th>새 비밀번호:</th><td><input name="newPassword1" type="password" required></td></tr>
            <tr><th>새 비밀번호 확인:</th><td><input name="newPassword2" type="password" required></td></tr>
            <tr>
                <td colspan="2" style="text-align: center;">
                    <input name="submitted" type="submit" value="비밀번호 변경">
                    <button type="button" onclick="window.location.href='index.php';">취소</button>
                </td>
            </tr>
        </table>
    </form>
</div>
</body>
</html>