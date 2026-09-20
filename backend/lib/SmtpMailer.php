<?php

declare(strict_types=1);

final class SmtpMailer
{
    public function send(string $toEmail, string $subject, string $htmlBody): void
    {
        $settings = MailConfig::settings();
        if ($settings['username'] === '' || $settings['password'] === '') {
          throw new RuntimeException('SMTP credentials are not configured.');
        }

        $socket = $this->connect($settings);

        try {
            $this->expect($socket, [220]);
            $this->command($socket, 'EHLO pwe.local', [250]);

            if ($settings['encryption'] === 'tls') {
                $this->command($socket, 'STARTTLS', [220]);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('Unable to enable TLS encryption.');
                }
                $this->command($socket, 'EHLO pwe.local', [250]);
            }

            $this->command($socket, 'AUTH LOGIN', [334]);
            $this->command($socket, base64_encode($settings['username']), [334]);
            $this->command($socket, base64_encode($settings['password']), [235]);
            $this->command($socket, 'MAIL FROM:<' . $settings['fromEmail'] . '>', [250]);
            $this->command($socket, 'RCPT TO:<' . $toEmail . '>', [250, 251]);
            $this->command($socket, 'DATA', [354]);

            $headers = [
                'From: ' . $settings['fromName'] . ' <' . $settings['fromEmail'] . '>',
                'To: <' . $toEmail . '>',
                'Subject: ' . $subject,
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
            ];

            $message = implode("\r\n", $headers) . "\r\n\r\n" . $htmlBody . "\r\n.";
            fwrite($socket, $message . "\r\n");
            $this->expect($socket, [250]);
            $this->command($socket, 'QUIT', [221]);
        } finally {
            fclose($socket);
        }
    }

    private function connect(array $settings)
    {
        $prefix = $settings['encryption'] === 'ssl' ? 'ssl://' : '';
        $socket = stream_socket_client(
            $prefix . $settings['host'] . ':' . $settings['port'],
            $errno,
            $errstr,
            20
        );

        if ($socket === false) {
            throw new RuntimeException('SMTP connection failed: ' . $errstr);
        }

        stream_set_timeout($socket, 20);
        return $socket;
    }

    private function command($socket, string $command, array $expectedCodes): void
    {
        fwrite($socket, $command . "\r\n");
        $this->expect($socket, $expectedCodes);
    }

    private function expect($socket, array $expectedCodes): void
    {
        $response = '';

        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            throw new RuntimeException('SMTP error: ' . trim($response));
        }
    }
}
