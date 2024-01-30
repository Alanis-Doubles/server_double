<?php

$siteName = 'foo';
$name = 'bar';
$pageDescription = 'baz';

$manifest = [
    "name" => $siteName,
    "gcm_user_visible_only" => true,
    "short_name" => $name,
    "description" => $pageDescription,
    "start_url" => "/index.php",
    "display" => "standalone",
    "orientation" => "portrait",
    "background_color" => "darkblue",
    "theme_color" => "#f0f0f0",
    "icons" => [
        "src" => "logo-load.png",
        "sizes"=> "96x96 128x128 144x144",
        "type" => "image/png"
    ],
    "src" => "logo-icon.png",
    "sizes" => "48x48 72x72",
    "type" => "image/png"
];

header('Content-Type: application/json');
echo json_encode($manifest);