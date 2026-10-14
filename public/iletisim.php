<?php
// İletişim formu — e-posta ile bildirim (dtaycan.com.tr ile aynı yöntem: doğrudan MX teslimi, bkz. smtp.php).
// Alıcı: info@lsdenttek.com.tr → Cloudflare Email Routing → dt.mehmettas@hotmail.com (29.09 kuruldu, aktif).
// Gönderici SPF'i: lsdenttek.com.tr TXT kaydına ip4:89.19.29.128 eklendi (29.09).
require __DIR__ . '/smtp.php';
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo '{"ok":false}'; exit; }
if (!empty($_POST['site'])) { echo '{"ok":true}'; exit; } // bot tuzağı

// Spam koruması (13.10.2026, "Submit lsdenttek.xyz in Google's Search Index" reklam mesajı sonrası): elenen istekler sessizce başarılı
// görünür (bota ipucu verilmez) ve docroot dışındaki iletisim-spam.log'a nedenle yazılır — yanlış eleme olursa oradan bulunur.
$spam = function ($neden) {
    @file_put_contents(__DIR__ . '/../iletisim-spam.log', date('c') . "\t" . $neden . "\t" . json_encode($_POST, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
    echo '{"ok":true}';
    exit;
};
$gecen = isset($_POST['gecen']) ? (int)$_POST['gecen'] : -1;
if ($gecen < 0) { echo '{"ok":false}'; exit; }   // tarayıcıdaki betik çalışmadan doğrudan gönderim: gerçek ziyaretçi değil (forma hata uyarısı döner)
if ($gecen < 4000) { $spam('cok-hizli'); }        // insan formu 4 saniyeden kısa sürede dolduramaz

$f = function ($k, $max = 200) { return mb_substr(trim(strip_tags($_POST[$k] ?? '')), 0, $max); };
$ad = $f('ad', 80); $tel = $f('telefon', 24); $eposta = $f('eposta', 120); $ulke = $f('ulke', 60);
$konu = $f('konu', 60); $hekim = $f('hekim', 60); $not = $f('not', 800); $lang = $f('lang', 5);

$metin = $ad . ' ' . $ulke . ' ' . $konu . ' ' . $hekim . ' ' . $not;
// Yalnız spam'in sık kullandığı alan adı uzantıları elenir; Drive / WeTransfer gibi röntgen bağlantıları (yurt dışı hasta) geçer
if (preg_match('~\b[a-z0-9-]+\.(xyz|top|click|pro|shop|online|site|club|icu|live)\b~i', $metin)) { $spam('baglanti'); }
if (preg_match('~\.(xyz|top|click|icu|tk|ml|ga|cf|gq)$~i', $eposta)) { $spam('eposta-uzantisi'); }
if (preg_match("~(search index|backlink|seo (services|expert)|indexregister|submit .{0,40} to google|google's search)~i", $metin)) { $spam('spam-ifadesi'); }

if ($ad === '' || strlen(preg_replace('/\D/', '', $tel)) < 8) { echo '{"ok":false}'; exit; }

$to = 'info@lsdenttek.com.tr';
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
$sent = ls_smtp_send($to, $subject, $body, $from, 'LS DentTek iletişim');

// yedek: sunucuda log (mail gitmese de kayıt kalsın) — docroot dışı
@file_put_contents(__DIR__ . '/../iletisim-talepleri.log', date('c') . "\t$ad\t$tel\t$eposta\t$ulke\t$konu\t$hekim\t" . str_replace(["\r", "\n"], ' ', $not) . "\t" . ($sent ? 'mail-ok' : 'mail-fail') . "\n", FILE_APPEND);

echo json_encode(['ok' => true, 'mail' => (bool)$sent]);
