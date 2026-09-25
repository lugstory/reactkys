<?php
use PHPMailer\PHPMailer\PHPMailer as PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;


$rdir = str_replace("\\", "/", __DIR__);  

require $rdir.'/PHPMailer/src/PHPMailer.php';
require $rdir.'/PHPMailer/src/Exception.php';
require $rdir.'/PHPMailer/src/SMTP.php';

require_once "./dbdriver.php";
require_once "./helper.php";
require_once "./firms/events.php";


$eventsObj = new events($conn);
$events = $eventsObj->getFutureEvents(); // ← tady je hlavní změna

if (!empty($events)) {

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->CharSet = "UTF-8";
    $mail->Host = 'smtp.websupport.cz';
    $mail->SMTPAuth = true;
    $mail->Username = 'info@crm.skch.cz';
    $mail->Password = 'nZ9(:cDIC`pg>W[D!tZ=';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;

    $mail->setFrom('info@crm.skch.cz', 'CRM Ječná');
    $mail->addAddress('masopust@spsejecna.cz');

    $mail->isHTML(true);
    $mail->Subject = 'Nadcházející akce';

    $body_string = "";

    foreach ($events as $row) {
        $body_string .= $row["name"] . " (" . $row["firma"] . "): "
            . $row["description"]
            . " [" . $row["time_start"] . "]<br>";
    }

    $mail->Body = $body_string;
    $mail->AltBody = strip_tags($body_string);

    $mail->send();
    echo "Email sent!";

} else {
    echo "0 results";
}
?>
