<?php
/**
 * mail.php — отправка email
 * Приоритет: Resend API → SMTP → PHP mail() (с fallback plain text)
 */

require_once __DIR__ . '/config.php';

/** @var array|null Последний результат отправки */
$GLOBALS['_mf_last_mail'] = null;

function getLastMailResult(): ?array {
    return $GLOBALS['_mf_last_mail'] ?? null;
}

/**
 * @return array{ok:bool, method:string, error:?string}
 */
function sendEmail(string $to, string $subject, string $htmlBody, ?string $textBody = null): array {
    $fromEmail = env('MAIL_FROM', 'noreply@localhost');
    $fromName  = env('MAIL_FROM_NAME', 'Mendflow');
    $textBody  = $textBody ?? htmlToPlainText($htmlBody);

    $attempts = [];

    // 1. Resend API (надёжно, бесплатно до 100 писем/день)
    if (env('RESEND_API_KEY')) {
        $r = resendSend($to, $subject, $htmlBody, $fromEmail, $fromName);
        $attempts[] = $r;
        if ($r['ok']) return storeMailResult($r);
    }

    // 2. SMTP
    if (env('SMTP_HOST')) {
        $r = smtpSend($to, $subject, $htmlBody, $textBody, $fromEmail, $fromName);
        $attempts[] = $r;
        if ($r['ok']) return storeMailResult($r);
    }

    // 3. PHP mail() — multipart
    $r = phpMailSend($to, $subject, $htmlBody, $textBody, $fromEmail, $fromName, true);
    $attempts[] = $r;
    if ($r['ok']) return storeMailResult($r);

    // 4. PHP mail() — только plain text (лучше работает на shared hosting)
    $r = phpMailSend($to, $subject, $htmlBody, $textBody, $fromEmail, $fromName, false);
    $attempts[] = $r;
    if ($r['ok']) return storeMailResult($r);

    $last = end($attempts) ?: ['ok' => false, 'method' => 'none', 'error' => 'Не удалось отправить'];
    return storeMailResult($last);
}

function storeMailResult(array $result): array {
    $GLOBALS['_mf_last_mail'] = $result;
    return $result;
}

function htmlToPlainText(string $html): string {
    $text = preg_replace('/<br\s*\/?>/i', "\n", $html);
    $text = strip_tags($text);
    return html_entity_decode(trim($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function emailTemplate(string $title, string $contentHtml): string {
    $brand = env('MAIL_FROM_NAME', 'Mendflow');
    return <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><title>{$title}</title></head>
<body style="margin:0;padding:24px;background:#f6f1f8;font-family:Arial,sans-serif;color:#1a1523">
  <div style="max-width:520px;margin:0 auto;background:#fff;border-radius:16px;padding:28px;border:1px solid #ece7f4">
    <div style="font-size:22px;font-weight:700;margin-bottom:8px">{$brand}</div>
    <h1 style="font-size:20px;margin:0 0 16px">{$title}</h1>
    <div style="font-size:15px;line-height:1.6;color:#4b5563">{$contentHtml}</div>
    <p style="margin-top:24px;font-size:12px;color:#9ca3af">Если вы не запрашивали это письмо, проигнорируйте его.</p>
  </div>
</body>
</html>
HTML;
}

/**
 * @return array{ok:bool, method:string, error:?string}
 */
function resendSend(string $to, string $subject, string $html, string $fromEmail, string $fromName): array {
    $key = env('RESEND_API_KEY');
    if (!$key) return ['ok' => false, 'method' => 'resend', 'error' => 'No API key'];

    $payload = json_encode([
        'from'    => "{$fromName} <{$fromEmail}>",
        'to'      => [$to],
        'subject' => $subject,
        'html'    => $html,
    ], JSON_UNESCAPED_UNICODE);

    if (function_exists('curl_init')) {
        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $key,
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $resp = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code >= 200 && $code < 300) {
            return ['ok' => true, 'method' => 'resend', 'error' => null];
        }
        $err = json_decode($resp, true);
        return ['ok' => false, 'method' => 'resend', 'error' => $err['message'] ?? "HTTP {$code}"];
    }

    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Authorization: Bearer {$key}\r\nContent-Type: application/json\r\n",
            'content' => $payload,
            'timeout' => 15,
        ],
    ]);
    $resp = @file_get_contents('https://api.resend.com/emails', false, $ctx);
    if ($resp !== false) {
        return ['ok' => true, 'method' => 'resend', 'error' => null];
    }
    return ['ok' => false, 'method' => 'resend', 'error' => 'Resend request failed'];
}

/**
 * @return array{ok:bool, method:string, error:?string}
 */
function phpMailSend(string $to, string $subject, string $htmlBody, string $textBody, string $fromEmail, string $fromName, bool $multipart): array {
    if (!function_exists('mail')) {
        return ['ok' => false, 'method' => 'mail', 'error' => 'mail() недоступна на сервере'];
    }

    @ini_set('sendmail_from', $fromEmail);

    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $fromHeader     = "{$fromName} <{$fromEmail}>";

    if ($multipart) {
        $boundary = 'mf_' . md5(uniqid('', true));
        $headers = implode("\r\n", [
            "From: {$fromHeader}",
            "Reply-To: {$fromEmail}",
            "MIME-Version: 1.0",
            "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
            "X-Mailer: Mendflow",
        ]);
        $body  = "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n{$textBody}\r\n\r\n";
        $body .= "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n{$htmlBody}\r\n\r\n";
        $body .= "--{$boundary}--";
    } else {
        $headers = implode("\r\n", [
            "From: {$fromHeader}",
            "Reply-To: {$fromEmail}",
            "Content-Type: text/plain; charset=UTF-8",
            "X-Mailer: Mendflow",
        ]);
        $body = $textBody;
    }

    // Envelope sender — важно для InfinityFree и shared hosting
    $params = "-f" . $fromEmail;
    $ok = @mail($to, $encodedSubject, $body, $headers, $params);

    return [
        'ok'     => (bool)$ok,
        'method' => $multipart ? 'mail-multipart' : 'mail-plain',
        'error'  => $ok ? null : 'mail() вернула false — проверьте MAIL_FROM и SMTP',
    ];
}

/**
 * @return array{ok:bool, method:string, error:?string}
 */
function smtpSend(string $to, string $subject, string $htmlBody, string $textBody, string $fromEmail, string $fromName): array {
    $host   = env('SMTP_HOST');
    $port   = (int)env('SMTP_PORT', 587);
    $user   = env('SMTP_USER', '');
    $pass   = env('SMTP_PASS', '');
    $secure = strtolower(env('SMTP_SECURE', 'tls'));

    $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host;
    $fp = @stream_socket_client("{$remote}:{$port}", $errno, $errstr, 20);
    if (!$fp) {
        return ['ok' => false, 'method' => 'smtp', 'error' => "SMTP connect: {$errstr} ({$errno})"];
    }

    stream_set_timeout($fp, 20);

    $read = function() use ($fp) {
        $data = '';
        while ($line = fgets($fp, 515)) {
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $data;
    };
    $write = function($cmd) use ($fp) { fwrite($fp, $cmd . "\r\n"); };

    $read();
    $write('EHLO mendflow.local');
    $read();

    if ($secure === 'tls') {
        $write('STARTTLS');
        $read();
        if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($fp);
            return ['ok' => false, 'method' => 'smtp', 'error' => 'STARTTLS failed'];
        }
        $write('EHLO mendflow.local');
        $read();
    }

    if ($user && $pass) {
        $write('AUTH LOGIN');
        $read();
        $write(base64_encode($user));
        $read();
        $write(base64_encode($pass));
        $resp = $read();
        if (strpos($resp, '235') === false) {
            fclose($fp);
            return ['ok' => false, 'method' => 'smtp', 'error' => 'SMTP auth failed'];
        }
    }

    $write("MAIL FROM:<{$fromEmail}>");
    $read();
    $write("RCPT TO:<{$to}>");
    $read();
    $write('DATA');
    $read();

    $boundary = 'mf_' . md5(uniqid('', true));
    $msg  = "From: {$fromName} <{$fromEmail}>\r\n";
    $msg .= "To: {$to}\r\n";
    $msg .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $msg .= "MIME-Version: 1.0\r\n";
    $msg .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n\r\n";
    $msg .= "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n{$textBody}\r\n\r\n";
    $msg .= "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n{$htmlBody}\r\n\r\n";
    $msg .= "--{$boundary}--\r\n.";
    $write($msg);
    $resp = $read();

    $write('QUIT');
    fclose($fp);

    $ok = strpos($resp, '250') !== false;
    return ['ok' => $ok, 'method' => 'smtp', 'error' => $ok ? null : trim($resp)];
}
