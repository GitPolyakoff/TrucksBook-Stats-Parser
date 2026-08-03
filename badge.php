<?php
$isDebug = isset($_GET['debug']);
if (!$isDebug) {
    header('Content-Type: image/png');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Access-Control-Allow-Origin: *');
}

$target_user = $_GET['id'] ?? '';
if (empty($target_user) || !is_numeric($target_user)) {
    die("ID не указан.");
}

function fetchTbData($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept-Language: en-US,en;q=0.9',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function fetchTbAjax($url, $referer) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_ENCODING, "");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.9',
        'Cache-Control: no-cache',
        'Connection: keep-alive',
        'Pragma: no-cache',
        'Referer: ' . $referer,
        'Sec-Fetch-Dest: empty',
        'Sec-Fetch-Mode: cors',
        'Sec-Fetch-Site: same-origin',
        'X-Requested-With: XMLHttpRequest',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

$profileUrl = "https://trucksbook.eu/profile/" . $target_user;
$profileHtml = fetchTbData($profileUrl);

preg_match('/profile-background.*?background-image:\s*url\([\'"]?(.*?)[\'"]?\)/is', $profileHtml, $avMatch);
$avatarUrl = $avMatch[1] ?? '';

preg_match('/<span[^>]*class="[^"]*username[^"]*"[^>]*>\s*<img[^>]*src="([^"]*)"[^>]*title="([^"]*)"[^>]*>\s*([^<]+)/i', $profileHtml, $nameMatch);
$flagUrl = $nameMatch[1] ?? '';
$countryName = $nameMatch[2] ?? 'Unknown';
$username = trim($nameMatch[3] ?? 'Driver');

$statsUrl = "https://trucksbook.eu/components/app/profile/game_overview.php?user=" . $target_user . "&game=1&stat=0&period=all";
$statsHtml = fetchTbAjax($statsUrl, $profileUrl);

if ($isDebug) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>Ответ сервера (Статистика):</h1>";
    echo "<textarea style='width:100%;height:300px;'>" . htmlspecialchars($statsHtml) . "</textarea>";
    die();
}

$distance = '0';
$deliveries = '0';

if (preg_match('/fa-arrows-left-right.*?<span[^>]*float-end[^>]*>(.*?)<\/span>/is', $statsHtml, $distMatch)) {
    $distance = preg_replace('/[^\d]/', '', strip_tags($distMatch[1]));
}

if (preg_match('/fa-truck-loading.*?<span[^>]*float-end[^>]*>(.*?)<\/span>/is', $statsHtml, $delMatch)) {
    $deliveries = preg_replace('/[^\d]/', '', strip_tags($delMatch[1]));
}

$distStr = ($distance !== '') ? number_format((int)$distance, 0, '', ' ') . ' km' : '0 km';
$jobsStr = ($deliveries !== '') ? number_format((int)$deliveries, 0, '', ' ') : '0';

$width = 800;
$height = 200;
$img = imagecreatetruecolor($width, $height);
imagealphablending($img, true);
imagesavealpha($img, true);

$transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
imagefill($img, 0, 0, $transparent);

$textColor = imagecolorallocate($img, 255, 255, 255);
$grayColor = imagecolorallocate($img, 156, 163, 175);
$tbBlueColor = imagecolorallocate($img, 58, 114, 189);
$shadowColor = imagecolorallocatealpha($img, 0, 0, 0, 80);

$font = __DIR__ . '/font.ttf'; 

function drawTextWithShadow($img, $size, $x, $y, $color, $shadowColor, $font, $text) {
    imagettftext($img, $size, 0, $x + 2, $y + 2, $shadowColor, $font, $text);
    imagettftext($img, $size, 0, $x, $y, $color, $font, $text);
}

function drawTextRightShadow($img, $size, $y, $color, $shadowColor, $font, $text, $rightX) {
    $bbox = imagettfbbox($size, 0, $font, $text);
    $textWidth = $bbox[2] - $bbox[0];
    $x = $rightX - $textWidth;
    drawTextWithShadow($img, $size, $x, $y, $color, $shadowColor, $font, $text);
}

if (!empty($avatarUrl)) {
    $avatarData = fetchTbData($avatarUrl);
    if ($avatarData) {
        $avatarImg = @imagecreatefromstring($avatarData);
        if ($avatarImg) {
            imagecopyresampled($img, $avatarImg, 30, 30, 0, 0, 140, 140, imagesx($avatarImg), imagesy($avatarImg));
            imagedestroy($avatarImg);
        }
    }
}

$fontSize = 26;
$maxWidth = 245;
while ($fontSize > 10) {
    $bbox = imagettfbbox($fontSize, 0, $font, $username);
    if (abs($bbox[2] - $bbox[0]) <= $maxWidth) break;
    $fontSize--;
}
drawTextWithShadow($img, $fontSize, 195, 75, $textColor, $shadowColor, $font, $username);

if (!empty($flagUrl)) {
    $flagData = fetchTbData($flagUrl);
    if ($flagData) {
        $flagImg = @imagecreatefromstring($flagData);
        if ($flagImg) {
            imagecopyresampled($img, $flagImg, 195, 98, 0, 0, 24, 16, imagesx($flagImg), imagesy($flagImg));
            imagedestroy($flagImg);
        }
    }
}
drawTextWithShadow($img, 14, 230, 112, $grayColor, $shadowColor, $font, mb_strtoupper($countryName));

drawTextWithShadow($img, 12, 195, 138, $grayColor, $shadowColor, $font, '#' . $target_user);

$etsIconUrl = 'https://trucksbook.eu/data/system/icon_ets2.png';
$etsIconData = fetchTbData($etsIconUrl);
if ($etsIconData) {
    $etsIconImg = @imagecreatefromstring($etsIconData);
    if ($etsIconImg) {
        imagecopyresampled($img, $etsIconImg, 485, 47, 0, 0, 16, 16, imagesx($etsIconImg), imagesy($etsIconImg));
        imagedestroy($etsIconImg);
    }
}

drawTextWithShadow($img, 12, 509, 60, $tbBlueColor, $shadowColor, $font, 'ETS2 LIFETIME STATS');

drawTextWithShadow($img, 11, 485, 105, $grayColor, $shadowColor, $font, 'DISTANCE:');
drawTextRightShadow($img, 16, 105, $textColor, $shadowColor, $font, $distStr, 770);

drawTextWithShadow($img, 11, 485, 145, $grayColor, $shadowColor, $font, 'DELIVERIES:');
drawTextRightShadow($img, 16, 145, $textColor, $shadowColor, $font, $jobsStr, 770);

$brandingText = 'TB-BADGE BY POLYAKOFF';
$brandingSize = 9;
$bboxBrand = imagettfbbox($brandingSize, 0, $font, $brandingText);
$brandWidth = $bboxBrand[2] - $bboxBrand[0];
$brandX = 770 - $brandWidth;

drawTextWithShadow($img, $brandingSize, $brandX, 185, $grayColor, $shadowColor, $font, $brandingText);

$tbLogoUrl = 'https://trucksbook.eu/components/assets/img/logo_white.png';
$tbLogoData = fetchTbData($tbLogoUrl);
if ($tbLogoData) {
    $tbLogoImg = @imagecreatefromstring($tbLogoData);
    if ($tbLogoImg) {
        $logoSize = 16;
        $logoX = $brandX - $logoSize - 8;
        imagecopyresampled($img, $tbLogoImg, $logoX, 174, 0, 0, $logoSize, $logoSize, imagesx($tbLogoImg), imagesy($tbLogoImg));
        imagedestroy($tbLogoImg);
    }
}

imagepng($img);
imagedestroy($img);
?>