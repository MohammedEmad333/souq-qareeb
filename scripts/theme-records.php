<?php
/**
 * CLI-only backup/restore for three Souq block-theme records.
 * No arbitrary record IDs: locate by type and slug from exported snapshots.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit(2); }
$mode = $argv[1] ?? '';
$dir = $argv[2] ?? '';
if (!in_array($mode, ['backup', 'restore'], true) || !is_dir($dir)) {
    fwrite(STDERR, "Usage: php theme-records.php backup|restore DIRECTORY\n");
    exit(2);
}
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
require '/var/www/html/wp-load.php';

$types = [
    'header' => 'wp_template_part',
    'navigation' => 'wp_navigation',
    'global-styles' => 'wp_global_styles',
];
$records = [];
foreach ($types as $name => $type) {
    $snapshot = json_decode((string) file_get_contents("$dir/$name.json"), true);
    if (!is_array($snapshot) || ($snapshot['type'] ?? '') !== $type ||
        !is_string($snapshot['slug'] ?? null) ||
        !is_string($snapshot['content'] ?? null) ||
        !is_string($snapshot['title'] ?? null)) {
        fwrite(STDERR, "Invalid $name.json\n");
        exit(2);
    }
    $posts = get_posts([
        'post_type' => $type, 'post_status' => 'any',
        'name' => $snapshot['slug'], 'posts_per_page' => 2,
        'suppress_filters' => true,
    ]);
    if (count($posts) !== 1) {
        fwrite(STDERR, "Missing or ambiguous $name\n");
        exit(2);
    }
    $records[$name] = [$posts[0], $snapshot];
}
if ($mode === 'backup') {
    foreach ($records as $name => [$post, $snapshot]) {
        $data = [
            'type' => $post->post_type, 'slug' => $post->post_name,
            'title' => $post->post_title, 'content' => $post->post_content,
        ];
        $json = wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if (!is_string($json) || file_put_contents("$dir/$name.json", $json . "\n") === false) {
            fwrite(STDERR, "Failed to write backup\n");
            exit(2);
        }
        echo "BACKED_UP $name\n";
    }
    exit(0);
}
// Restore is explicit and only targets the three identified records.
// This can replace WordPress editor changes. Only invoke after verifying backup.
foreach ($records as $name => [$post, $snapshot]) {
    $result = wp_update_post([
        'ID' => $post->ID, 'post_title' => $snapshot['title'],
        'post_content' => $snapshot['content'],
    ], true);
    if (is_wp_error($result)) {
        fwrite(STDERR, "Restore failed for $name: " . $result->get_error_message() . "\n");
        exit(1);
    }
    echo "RESTORED $name\n";
}
