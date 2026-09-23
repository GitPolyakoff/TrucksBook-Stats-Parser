<?php
$isDebug = isset($_GET['debug']);
$format = strtolower($_GET['format'] ?? 'png');

if (!$isDebug && $format !== 'json') {
    header('Content-Type: image/png');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
}
header('Access-Control-Allow-Origin: *');

$target_user = $_GET['id'] ?? '';
if (empty($target_user) || !is_numeric($target_user)) {
    if ($format === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'ID не указан'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    die("ID не указан.");
}

function fetchTbData($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept-Language: en-US,en;q=0.9',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0 Safari/537.36'
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
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.9',
        'Referer: ' . $referer,
        'X-Requested-With: XMLHttpRequest',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0 Safari/537.36'
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

preg_match('/id="followers-count">\s*(\d+)\s*</i', $profileHtml, $followersMatch);
$followers = isset($followersMatch[1]) ? (int)$followersMatch[1] : 0;

preg_match('/data-load="#follows-list"[^>]*>.*?(\d+)\s*</is', $profileHtml, $followingMatch);
$following = isset($followingMatch[1]) ? (int)$followingMatch[1] : 0;

preg_match('/<div class="profile-info-item">\s*<i class="[^"]*fa-building[^"]*"><\/i>(.*?)<\/div>/is', $profileHtml, $companyMatch);
$companyText = isset($companyMatch[1]) ? trim(strip_tags($companyMatch[1])) : 'Loner';

$isPremium = (strpos($profileHtml, 'icon_premium') !== false);

$statsUrl = "https://trucksbook.eu/user-game-overview/" . $target_user . "?game=1&period=all";
$statsHtml = fetchTbAjax($statsUrl, $profileUrl);

$distance = '0';
$deliveries = '0';

if (preg_match('/fa-arrows-left-right.*?<span[^>]*float-end[^>]*>(.*?)<\/span>/is', $statsHtml, $distMatch)) {
    $distance = preg_replace('/[^\d]/', '', strip_tags($distMatch[1]));
}
if (preg_match('/fa-truck-loading.*?<span[^>]*float-end[^>]*>(.*?)<\/span>/is', $statsHtml, $delMatch)) {
    $deliveries = preg_replace('/[^\d]/', '', strip_tags($delMatch[1]));
}

$careerUrl = "https://trucksbook.eu/user-career-stats/" . $target_user . "?game=1";
$careerHtml = fetchTbAjax($careerUrl, $profileUrl);

preg_match_all('/<span[^>]*class=["\']float-end["\'][^>]*>(.*?)<\/span>/is', $careerHtml, $careerMatches);
$cItems = $careerMatches[1] ?? [];

function parseCareerItem($html) {
    $img = '';
    if (preg_match('/<img[^>]*src=["\']([^"\']+)["\']/i', $html, $imgMatch)) {
        $img = $imgMatch[1];
    }
    return ['text' => trim(strip_tags($html)), 'image' => $img];
}

$jobType = isset($cItems[0]) ? parseCareerItem($cItems[0])['text'] : '';
$fromData = isset($cItems[1]) ? parseCareerItem($cItems[1]) : ['text'=>'', 'image'=>''];
$toData = isset($cItems[2]) ? parseCareerItem($cItems[2]) : ['text'=>'', 'image'=>''];
$senderImg = isset($cItems[3]) ? parseCareerItem($cItems[3])['image'] : '';
$receiverImg = isset($cItems[4]) ? parseCareerItem($cItems[4])['image'] : '';
$cargo = isset($cItems[5]) ? parseCareerItem($cItems[5])['text'] : '';
$parking = isset($cItems[6]) ? parseCareerItem($cItems[6])['text'] : '';
$truckData = isset($cItems[7]) ? parseCareerItem($cItems[7]) : ['text'=>'', 'image'=>''];
$timeline = [];
if (preg_match_all('/<article[^>]*>(.*?)<\/article>/is', $profileHtml, $articles)) {
    foreach ($articles[1] as $articleHtml) {
        
        $title = '';
        if (preg_match('/<h2 class="card-title">\s*(.*?)\s*<\/h2>/is', $articleHtml, $titleMatch)) {
            $title = trim(strip_tags($titleMatch[1]));
        }
        
        $date = '';
        if (preg_match('/<time[^>]*data-time=["\']([^"\']+)["\']/is', $articleHtml, $dateMatch)) {
            $date = $dateMatch[1];
        }
        
        $companyDetails = '';
        if (preg_match('/<div class="card-body">.*?<a[^>]*>(.*?)<\/a>/is', $articleHtml, $compMatch)) {
            $companyDetails = trim(strip_tags($compMatch[1]));
        }

        $footerText = '';
        if (preg_match('/<div class="card-footer[^>]*>(.*?)<\/div>/is', $articleHtml, $footMatch)) {
            $rawFooter = preg_replace('/<time.*?>.*?<\/time>/is', '', $footMatch[1]);
            $footerText = trim(strip_tags($rawFooter));
            $footerText = str_replace(['&nbsp;', '&amp;nbsp;'], ' ', $footerText);
        }
        
        $timeline[] = [
            'event' => $title,
            'date' => $date,
            'company_name' => $companyDetails,
            'status_change' => $footerText
        ];
    }
}

$awards = [];
if (preg_match_all('/<div class="p-3 profile-info-item d-flex gap-2 align-items-center">(.*?)<\/span>\s*<\/div>/is', $profileHtml, $awardItems)) {
    foreach ($awardItems[1] as $itemHtml) {
        $gameIcon = '';
        if (preg_match('/<img[^>]*src=["\']([^"\']+)["\']/is', $itemHtml, $imgM)) {
            $gameIcon = $imgM[1];
        }

        $title = '';
        if (preg_match('/<span class="flex-grow-1">\s*<span>(.*?)<\/span>/is', $itemHtml, $titleM)) {
            $title = trim(strip_tags($titleM[1]));
        }

        $desc = '';
        if (preg_match('/<span class="text-muted">(.*?)<\/span>/is', $itemHtml, $descM)) {
            $desc = trim(strip_tags($descM[1]));
        }

        $color = 'none';
        if (preg_match('/color-([a-z]+)/i', $itemHtml, $colorM)) {
            $color = $colorM[1];
        }

        $awards[] = [
            'title' => $title,
            'description' => $desc,
            'game_icon' => $gameIcon,
            'trophy_color' => $color
        ];
    }
}

if ($format === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    
    $apiResponse = [
        'user' => [
            'id' => $target_user,
            'username' => $username,
            'country' => $countryName,
            'avatar_url' => $avatarUrl,
            'flag_url' => $flagUrl,
            'company' => $companyText,
            'followers' => $followers,
            'following' => $following,
            'is_premium' => $isPremium
        ],
        'lifetime_stats' => [
            'distance_km' => (int)$distance,
            'deliveries' => (int)$deliveries
        ],
        'frequent_deliveries' => [
            'job_type' => $jobType,
            'from' => [
                'city' => $fromData['text'],
                'flag_url' => $fromData['image']
            ],
            'to' => [
                'city' => $toData['text'],
                'flag_url' => $toData['image']
            ],
            'sender_company_image' => $senderImg,
            'receiver_company_image' => $receiverImg,
            'cargo' => $cargo,
            'parking_problems' => $parking,
            'truck' => [
                'model' => $truckData['text'],
                'brand_image' => $truckData['image']
            ]
        ],
        'awards' => $awards,
        'news_feed' => $timeline
    ];
    
    echo json_encode($apiResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($isDebug) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>Ответ сервера (Статистика):</h1><textarea style='width:100%;height:300px;'>" . htmlspecialchars($statsHtml) . "</textarea>";
    die();
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

$brandingText = 'TB-BANNER BY POLYAKOFF';
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