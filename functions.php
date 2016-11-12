<?php 
/* Send mails using PHPmailer class
* @param $from
* @param $to
* @param $message - HTML format
* @param $subject
* @param $attacmhents - Can be a list of comma separated files
*/



function send_mail($from,$to,$message,$subject,$attachments='')

  {                                             
    require_once __DIR__.('/mailer/class.phpmailer.php');
    $mail = new PHPMailer();
    $mail->From =$from; 
    $mail->addAddress($to);
    $mail->SetFrom($from,$from);
    $mail->AddReplyTo($from,$from); 
    $mail->CharSet = 'UTF-8';
    //Provide file path and name of the attachments
    $mail->addAttachment($attachments); //Filename is optional
    $mail->Subject = $subject;
    $mail->MsgHTML($message);
    if(!$mail->send()) 
    {
      echo "Error: " . $mail->ErrorInfo;
    } 
    else 
    {
      echo "Instricciones enviadas con éxito";
    }
  }     
