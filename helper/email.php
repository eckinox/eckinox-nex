<?php

namespace Eckinox\Nex;

/**
 * @author       Mikael Laforge <mikael.laforge@twikiconcept.com>
 * @version      1.1.1
 * @package      Nex
 * @subpackage   core
 *
 * @update (05/09/2010) [Mikael Laforge] - 1.0.0 - Script Creation
 * @update (16/01/2010) [Mikael Laforge] - 1.1.0 - Added methods convertSeparator() and to[].
 * 												 Method send() now support list of email in a string using method to[]
 * @update (04/05/2012) [Mikael Laforge] - 1.1.1 - Added argument $separator in to[] method
 *
 * This class was made to help with sending emails
 */
abstract class email {

    /**
     * Create email headers and send email
     * @param string|array			$to - send email to
     * @param string				$from - mailer
     * @param string				$subject - email title
     * @param string				$message - email message
     * @param string				$html - if html if allowed or not
     * @param string                $headers - additionnal headers
     * @return bool					email delivery status
     * @uses DOMAIN
     */
    public static function send($to, $from, $subject, $message, $html = false, $headers = '') {
        // Make sure $to is array
        $to = (is_array($to)) ? $to : self::toArray($to);

        // Make sure $message is standardized
        $message = self::polish_body($message);

        // Make sure subject is standardized
        $subject = self::polish_subject($subject);

        // From cleaning
        $name = '';
        $boom = explode('<', $from);
        if (count($boom) > 1) {
            $name = trim($boom[0]) . ' ';
            $from = str_replace('>', '', $boom[1]);
        }
        $from = trim($from);

        // Determine the message type
        $html = ($html === true) ? 'text/html' : 'text/plain';

        $f = "MIME-Version: 1.0\n";
        $f .= 'From: ' . $name . '<' . $from . ">\n";
        $f .= 'Reply-To: ' . $name . '<' . $from . ">\n";
        $f .= "X-Sender: " . NEX_DOMAIN . "\n";

        $f .= 'X-Mailer: PHP/' . phpversion() . "\n"; //mailer
        $f .= "X-Priority: 3\n"; //1 UrgentMessage, 3 Normal
        $f .= 'Return-Path: <' . $from . ">\n";
        $f .= "Content-Type: $html; charset=UTF-8\n";
        $f .= $headers;

        $error = false;
        // Send mails 1 by 1
        foreach ($to as $email) {
            if (!mail($email, $subject, $message, $f)) {
                $error = true;
            }
        }

        return (($error == false) ? true : false );
    }

    /**
     * Wrap any line longer then 70 chars to satisfy emails standards
     * @param string $str
     */
    public static function polish_body($str) {
        $str = wordwrap($str, 70, "\n");
        $str = str_replace("\r\n", "\n", $str);
        return $str;
    }

    /**
     * Polish Subject for Utf-8
     * @param string $str
     */
    public static function polish_subject($str) {
        $str = mb_encode_mimeheader($str, 'UTF-8', 'B', "\n");
        return $str;
    }

    public static function toArray($emails, $separ = ',') {
        if (is_string($emails))
            $emails = explode($separ, $emails);

        foreach ($emails as $i => $email) {
            $emails[$i] = trim($email);
        }

        return $emails;
    }

}
