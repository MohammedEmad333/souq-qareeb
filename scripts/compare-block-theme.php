<?php
/**
 * Read-only comparison of versioned WordPress block-theme snapshots.
 *
 * Run inside the WordPress container with:
 *   php /tmp/souq-compare-block-theme.php /path/to/exports
 *
 * No changes are made to WordPress content or the database.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(2);
}

$directory = $argv[1] ?? '';
if (!is_dir($directory)) {
    fwrite(STDERR, "Snapshot directory not found\n");
    exit(2);
}

$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
require '/var/www/html/wp-load.php';

$files = [
    'header.json' => 'wp_template_part',
    'navigation.json' => 'wp_navigation',
    'global-styles.json' => 'wp_global_styles',
];

$changed = false;

foreach ($files as $file => $expectedType) {
    $raw = file_get_contents(rtrim($directory, '/') . '/' . $file);
    $saved = $raw === false ? null : json_decode($raw, true);

    if (!is_array($saved)
        || ($saved['type'] ?? null) !== $expectedType
        || !is_string($saved['slug'] ?? null)
        || !is_string($saved['content'] ?? null)
    ) {
        fwrite(STDERR, "INVALID SNAPSHOT: $file\n");
        exit(2);
    }

    $matches = get_posts([
        'post_type' => $expectedType,
        'post_status' => 'any',
        'name' => $saved['slug'],
        'posts_per_page' => 2,
        'suppress_filters' => true,
    ]);
    if (count($matches) !== 1) {
        echo "MISSING_OR_AMBIGUOUS $file matches=" . count($matches) . "\n";
        $changed = true;
        continue;
    }

    $live = $matches[0];
    $sameContent = hash_equals(
        hash('sha256', $saved['content']),
        hash('sha256', $live->post_content)
    );
    $sameTitle = ($saved['title'] ?? '') === $live->post_title;

    echo ($sameContent && $sameTitle ? 'UNCHANGED' : 'DIFFERS') . " $file\n";
    if (!$sameContent || !$sameTitle) {
        $changed = true;
    }
}

exit($changed ? 1 : 0);
