<?php
// İletişim formu — e-posta ile bildirim (dtaycan.com.tr ile aynı yöntem: doğrudan MX teslimi, bkz. smtp.php).
// Alıcı: info@lsdenttek.com.tr → Cloudflare Email Routing → dt.mehmettas@hotmail.com (domain + Cloudflare kurulunca).
// Kurulum tamamlanana kadar alıcı info@dtaycan.com.tr (zaten hotmail'e yönlendirili). Gönderici SPF'i: lsdenttek.com.tr TXT
// kaydına ip4:89.19.29.128 (Natro cpls05) eklenmeli; aksi hâlde iletisim@lsdenttek.com.tr göndericisi reddedilebilir.
require __DIR__ . '/smtp.php';
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo '{"ok":false}'; exit; }
if (!empty($_POST['site'])) { echo '{"ok":true}'; exit; } // bot tuzağı
$f = function ($k, $max = 200) { return mb_substr(trim(strip_tags($_POST[$k] ?? '')), 0, $max); };
$ad = $f('ad', 80); $tel = $f('telefon', 24); $eposta = $f('eposta', 120); $ulke = $f('ulke', 60);
$konu = $f('konu', 60); $hekim = $f('hekim', 60); $not = $f('not', 800); $lang = $f('lang', 5);
if ($ad === '' || strlen(preg_replace('/\D/', '', $tel)) < 8) { echo '{"ok":false}'; exit; }

$to = 'info@dtaycan.com.tr'; // TODO domain gelince: info@lsdenttek.com.tr
$from = 'iletisim@lsdenttek.com.tr';
$subject = 'LS DentTek iletişim: ' . $ad . ($ulke ? " ($ulke)" : '');
$body = "lsdenttek.com.tr — iletişim formu (" . strtoupper($lang) . ")\n\n"
      . "Ad Soyad : $ad\n"
      . "Telefon  : $tel\n"
      . "E-posta  : $eposta\n"
      . "Ülke     : $ulke\n"
      . "Konu     : $konu\n"
      . "Hekim    : $hekim\n"
      . "Not      : $not\n\n"
      . "Tarih    : " . date('d.m.Y H:i') . "\n"
      . "IP       : " . ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '') . "\n";
$sent = ls_smtp_send($to, $subject, $body, $from, 'lsdenttek.com.tr iletişim');

// yedek: sunucuda log (mail gitmese de kayıt kalsın) — docroot dışı
@file_put_contents(__DIR__ . '/../iletisim-talepleri.log', date('c') . "\t$ad\t$tel\t$eposta\t$ulke\t$konu\t$hekim\t" . str_replace(["\r", "\n"], ' ', $not) . "\t" . ($sent ? 'mail-ok' : 'mail-fail') . "\n", FILE_APPEND);

echo json_encode(['ok' => true, 'mail' => (bool)$sent]);
