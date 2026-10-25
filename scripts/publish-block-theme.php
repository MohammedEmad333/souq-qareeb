<?php
/** CLI-only, conservative theme deployment. No HTTP access. */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(2);
$mode = $argv[1] ?? '';
$root = $argv[2] ?? '';
if (!in_array($mode, ['check', 'apply'], true) || !is_dir($root)) exit(2);
$_SERVER['HTTP_HOST'] = 'souq.edubridge.win';
$_SERVER['HTTPS'] = 'on';
require '/var/www/html/wp-load.php';

$types = ['header'=>'wp_template_part', 'navigation'=>'wp_navigation', 'global-styles'=>'wp_global_styles'];
function block_normalize(string $s): string {
    return preg_replace('/(?<=-->)[ \\t]*\\R(?:[ \\t]*\\R)+(?=[ \\t]*<!-- wp:)/', "\n", $s) ?? $s;
}
function load_snapshot(string $path, string $expected): array {
    $content = @file_get_contents($path);
    $json = is_string($content) ? json_decode($content, true) : null;
    if (!is_array($json) || ($json['type'] ?? null) !== $expected ||
        !is_string($json['slug'] ?? null) ||
        !is_string($json['title'] ?? null) ||
        !is_string($json['content'] ?? null)) {
        throw new RuntimeException("Invalid snapshot: " . basename($path));
    }
    return $json;
}
try {
    $changes = [];
    foreach ($types as $name => $type) {
        $baseline = load_snapshot("$root/baseline/$name.json", $type);
        $desired = load_snapshot("$root/desired/$name.json", $type);
        if ($baseline['slug'] !== $desired['slug']) throw new RuntimeException("Slug mismatch: $name");
        $posts = get_posts([
            'post_type'=>$type, 'name'=>$baseline['slug'], 'post_status'=>'any',
            'posts_per_page'=>2, 'suppress_filters'=>true,
        ]);
        if (count($posts) !== 1) throw new RuntimeException("Missing/ambiguous record: $name");
        $live = $posts[0];
        if (!hash_equals(hash('sha256', block_normalize($baseline['content'])), hash('sha256', block_normalize($live->post_content)))
            || $baseline['title'] !== $live->post_title) {
            throw new RuntimeException("DRIFT: $name — live content changed since baseline");
        }
        $different = $live->post_title !== $desired['title'] ||
            !hash_equals(hash('sha256', block_normalize($live->post_content)), hash('sha256', block_normalize($desired['content'])));
        echo ($different ? 'WOULD_UPDATE ' : 'UNCHANGED ') . "$name\n";
        if ($different) $changes[] = ['name'=>$name, 'post'=>$live, 'desired'=>$desired];
    }
    if ($mode === 'check') exit(0);
    // All three records validated BEFORE the first write.
    $written = [];
    try {
        foreach ($changes as $row) {
            $post = $row['post'];
            $result = wp_update_post([
                'ID'=>$post->ID,
                'post_title'=>$row['desired']['title'],
                'post_content'=>wp_slash($row['desired']['content']),
            ], true);
            if (is_wp_error($result) || !$result) {
                throw new RuntimeException("Write failure: " . $row['name']);
            }
            $written[] = $row;
            echo "UPDATED " . $row['name'] . "\n";
        }
    } catch (Throwable $error) {
        fwrite(STDERR, "Update failed; attempting restore of earlier records.\n");
        foreach (array_reverse($written) as $row) {
            $old = $row['post'];
            $rollback = wp_update_post([
                'ID'=>$old->ID, 'post_title'=>$old->post_title,
                'post_content'=>wp_slash($old->post_content),
            ], true);
            if (is_wp_error($rollback)) fwrite(STDERR, "ROLLBACK FAILED for " . $row['name'] . "\n");
        }
        throw $error;
    }
    echo "Theme deployment complete. Check website and keep recovery backup.\n";
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
