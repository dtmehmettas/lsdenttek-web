<?php
// Doğrudan MX teslimi ile e-posta gönderimi.
// Natro paylaşımlı hostingde PHP mail() kapalı (disable_functions); 25/587 portları açık.
// Alıcının MX sunucusuna (info@lsdenttek.com.tr için Cloudflare Email Routing) SMTP ile bağlanıp
// mesajı teslim ederiz; Cloudflare da hotmail'e yönlendirir. Şifre/hesap gerekmez.
// SPF: lsdenttek.com.tr TXT kaydında ip4:89.19.29.128 var, bu yüzden iletisim@lsdenttek.com.tr göndericisi geçer.

function ls_smtp_send(string $to, string $subject, string $body, string $from, string $fromName = 'lsdenttek.com.tr', ?string &$log = null): bool
{
    $log = '';
    $domain = substr(strrchr($to, '@'), 1);
    $hosts = [];
    if (getmxrr($domain, $mxs, $weights)) {
        array_multisort($weights, SORT_ASC, $mxs);
        $hosts = $mxs;
    }
    if (!$hosts) $hosts = [$domain];

    $fromDomain = substr(strrchr($from, '@'), 1);
    $msgId = '<' . bin2hex(random_bytes(12)) . '@' . $fromDomain . '>';
    $headers = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$from>\r\n"
        . "To: <$to>\r\n"
        . "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n"
        . "Date: " . date('r') . "\r\n"
        . "Message-ID: $msgId\r\n"
        . "MIME-Version: 1.0\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: 8bit\r\n";
    $data = $headers . "\r\n" . str_replace("\n", "\r\n", str_replace("\r\n", "\n", $body));
    $data = preg_replace('/^\./m', '..', $data); // nokta doldurma

    foreach ($hosts as $host) {
        $sock = @stream_socket_client("tcp://$host:25", $errno, $errstr, 10);
        if (!$sock) { $log .= "$host: baglanti yok ($errstr)\n"; continue; }
        stream_set_timeout($sock, 15);
        $read = function () use ($sock) {
            $out = '';
            while (($line = fgets($sock, 1024)) !== false) { $out .= $line; if (!isset($line[3]) || $line[3] !== '-') break; }
            return $out;
        };
        $cmd = function ($c) use ($sock, $read, &$log) { fwrite($sock, $c . "\r\n"); $r = $read(); $log .= substr($c, 0, 40) . " -> " . trim(substr($r, 0, 80)) . "\n"; return $r; };
        $ok = function ($r) { return $r !== '' && in_array($r[0], ['2', '3'], true); };

        $r = $read();
        if (!$ok($r)) { fclose($sock); $log .= "$host: banner $r\n"; continue; }
        $r = $cmd("EHLO $fromDomain");
        if ($ok($r) && stripos($r, 'STARTTLS') !== false) {
            $r = $cmd('STARTTLS');
            if ($ok($r) && @stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                $r = $cmd("EHLO $fromDomain");
            }
        }
        if (!$ok($r)) { fclose($sock); continue; }
        if (!$ok($cmd("MAIL FROM:<$from>"))) { fclose($sock); continue; }
        if (!$ok($cmd("RCPT TO:<$to>"))) { fclose($sock); continue; }
        if (!$ok($cmd('DATA'))) { fclose($sock); continue; }
        fwrite($sock, $data . "\r\n.\r\n");
        $r = $read(); $log .= "DATA -> " . trim(substr($r, 0, 80)) . "\n";
        $cmd('QUIT');
        fclose($sock);
        if ($ok($r)) return true;
    }
    return false;
}
