<?php
/**
 * @copyright	Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
switch ($lang){
    case "en":
        header('Location: /changepassword_en.php');
        break;
    case "ko":
        header('Location: /changepassword_ko.php');
        break;
    default:
        header('Location: /changepassword_pl.php');
        break;
}

?>
