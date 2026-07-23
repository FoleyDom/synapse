#!/usr/bin/env php
<?php

declare(strict_types=1);

const DEBUG_CONST = TRUE; // Set to FALSE to disable debug output

/**
 * announce.php — generate cross-posting copy for a new blog post,
 * and optionally push a draft straight to dev.to.
 *
 * Requires PHP 8.1+ with the mbstring extension (on by default in most
 * setups, including Laravel Herd / Sail images).
 *
 * Usage:
 *   php scripts/announce.php posts/2026-07-10-my-post.md
 *   php scripts/announce.php posts/2026-07-10-my-post.md --devto
 *
 * Env vars:
 *   DEV_TO_API_KEY   (only needed with --devto; get one at
 *                     https://dev.to/settings/extensions)
 * 
 * @return array{0: array<string, mixed>, 1: string}
 */
function parse_frontmatter(string $raw): array
{
    if (!preg_match('/^---\r?\n(.*?)\r?\n---\r?\n?(.*)$/s', $raw, $matches))
    {
        throw new RuntimeException('No frontmatter block found (expected --- ... --- at the top of the file).');
    }

    [, $fmBlock, $body] = $matches;
    $data = [];

    foreach (preg_split('/\r?\n/', $fmBlock) as $line)
    {
        if (trim($line) === '')
        {
            continue;
        }

        $idx = strpos($line, ':');
        if ($idx === false)
        {
            continue;
        }

        $key = trim(substr($line, 0, $idx));
        $value = trim(substr($line, $idx + 1));
        $value = trim($value, "\"'");

        if ((str_starts_with($value, '[') && str_ends_with($value, ']')) || (strpos($value, 'tags:') !== FALSE))
        {
            $items = array_map(
                fn(string $item): string => trim(trim($item), "\"'"),
                explode(',', substr($value, 1, -1))
            );
            $data[$key] = array_values(array_filter($items, fn(string $i) => $i !== ''));
        }
        else
        {
            $data[$key] = $value;
        }
    }

    return [$data, trim($body)];
}

/**
 * Convert a tag string into a hashtag.
 *
 * @param string $tag
 * 
 * @return string
 */
function to_hash_tag(string $tag): string
{
    $words = preg_split('/[\s\-_]+/', $tag);
    $capitalized = array_map(
        fn(string $w): string => $w === '' ? '' : mb_strtoupper(mb_substr($w, 0, 1)) . mb_substr($w, 1),
        $words
    );

    return '#' . implode('', $capitalized);
}

/**
 * Build a post for LinkedIn with a 1300-character limit.
 *
 * @param array<string, mixed> $data
 * 
 * @return string
 */
function build_linked_in_post(array $data): string
{
    $tags = $data['tags'] ?? [];

    if (empty($tags))
    {
        throw new RuntimeException('No tags found in frontmatter; at least one tag is required for LinkedIn.');
    }

    $hashtags = implode(' ', array_map('to_hash_tag', array_slice($tags, 0, 4)));

    $lines = ["New post: {$data['title']}", ''];

    if (!empty($data['description']))
    {
        $lines[] = $data['description'];
        $lines[] = '';
    }

    if (!empty($data['canonical_url']))
    {
        $lines[] = $data['canonical_url'];
        $lines[] = '';
    }

    if ($hashtags !== '')
    {
        $lines[] = $hashtags;
    }

    return trim(implode("\n", $lines));
}

/**
 * Build a post for X (formerly Twitter) with a 280-character limit.
 *
 * @param array<string, mixed> $data
 * 
 * @return string
 */
function build_x_post(array $data): string
{
    $tags = $data['tags'] ?? [];
    $hashtags = implode(' ', array_map('to_hash_tag', array_slice($tags, 0, 2)));
    $link = $data['canonical_url'] ?? '';

    $linkCost = $link !== '' ? mb_strlen($link) + 1 : 0;
    $tagCost = $hashtags !== '' ? mb_strlen($hashtags) + 1 : 0;
    $budget = 280 - $linkCost - $tagCost - 1;

    $lead = !empty($data['description'])
        ? "{$data['title']} — {$data['description']}"
        : $data['title'];

    if (mb_strlen($lead) > $budget)
    {
        $lead = trim(mb_substr($lead, 0, max($budget - 1, 0))) . '…';
    }

    return implode(' ', array_filter([$lead, $link, $hashtags], fn($v) => $v !== ''));
}

/**
 * Push a draft article to dev.to via their API.
 *
 * @param array<string, mixed> $data
 * @param string $body
 * 
 * @return array<string, mixed>
 */
function push_to_DevTo(array $data, string $body): array
{
    $apiKey = getenv('DEV_TO_API_KEY');
    if ($apiKey === false || $apiKey === '')
    {
        throw new RuntimeException('DEV_TO_API_KEY is not set. Get one at https://dev.to/settings/extensions');
    }

    $article = [
        'title' => $data['title'],
        'body_markdown' => $body,
        'published' => false,
        'tags' => array_slice($data['tags'] ?? [], 0, 4),
    ];

    if (!empty($data['canonical_url']))
    {
        $article['canonical_url'] = $data['canonical_url'];
    }

    $payload = json_encode(['article' => $article], JSON_THROW_ON_ERROR);

    $ch = curl_init('https://dev.to/api/articles');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            "api-key: {$apiKey}",
        ],
        CURLOPT_RETURNTRANSFER => true,
    ]);

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false)
    {
        throw new RuntimeException("dev.to request failed: {$curlError}");
    }

    if ($status < 200 || $status >= 300)
    {
        throw new RuntimeException("dev.to API [118;1:3uerror ({$status}): {$response}");
    }

    return json_decode($response, true, flags: JSON_THROW_ON_ERROR);
}

/**
 * Function to run the script from the command line.
 * 
 * @param array<string> $argv
 * 
 * @return void
 */
function main(array $argv): void
{
    $filePath = $argv[1] ?? null;
    _debug_output(message: "File path: {$filePath}", exit: FALSE, exit_string: '');

    $flags = array_slice($argv, 2);
    _debug_output(message: "Flags: " . implode(', ', $flags), exit: FALSE, exit_string: '');

    if ($filePath === null)
    {
        fwrite(STDERR, "Usage: php scripts/announce.php <path-to-post.md> [--devto]\n");
        exit(1);
    }

    $raw = file_get_contents($filePath);
    _debug_output(message: "Raw file content length: " . strlen($raw), exit: FALSE, exit_string: '');

    if ($raw === false)
    {
        fwrite(STDERR, "Could not read file: {$filePath}\n");
        exit(1);
    }

    [$data, $body] = parse_frontmatter($raw);
    _debug_output(message: "Data: " . json_encode($data, JSON_PRETTY_PRINT), exit: FALSE, exit_string: '');
    _debug_output(message: "Body length: " . strlen($body), exit: FALSE, exit_string: '');

    if (empty($data['canonical_url']))
    {
        fwrite(STDERR, "warning: no canonical_url set in frontmatter — add one before cross-posting anywhere.\n");
    }

    echo "\n=== LinkedIn ===\n\n";
    echo build_linked_in_post($data) . "\n";

    echo "\n=== X ===\n\n";
    $xPost = build_x_post($data);
    echo $xPost . "\n";
    echo "\n(" . mb_strlen($xPost) . "/280 characters)\n";

    if (in_array('--devto', $flags, true))
    {
        echo "\n=== dev.to ===\n\n";
        _debug_output(message: "Commented out dev.to for now (07/10/2026) — uncomment to enable.", exit: FALSE, exit_string: '');
        // try
        // {
        //     $result = push_to_DevTo($data, $body);
        //     _debug_output(message: "Draft created: {$result['url']} — review and hit publish when ready.", exit: FALSE, exit_string: '');
        // }
        // catch (RuntimeException | JsonException $e)
        // {
        //     fwrite(STDERR, "Failed to push to dev.to: {$e->getMessage()}\n");
        // }
    }
    else
    {
        echo "\nTo push to dev.to, set the DEV_TO_API_KEY environment variable and run with --devto.\n";
    }
}

/**
 * Test function to verify the parse_frontmatter function.
 *
 * @return void
 */
function test_parse_frontmatter(): void
{
    $raw = "
    ---
    title: My Test Post
    description: This is a test post.
    tags: [test, php, announce]
    canonical_url: https://example.com/my-test-post
    ---
    This is the body of the post.
    ";

    $data = json_decode($raw, true);
    if (empty($data) || !is_array($data))
    {
        throw new RuntimeException('Failed to parse test frontmatter.');
    }
    else
    {
        [$parsedData, $parsedBody] = parse_frontmatter($raw);
        assert($parsedData['title'] === 'My Test Post', 'Failed to parse title');
        assert($parsedData['description'] === 'This is a test post.', 'Failed to parse description');
        assert($parsedData['tags'] === ['test', 'php', 'announce'], 'Failed to parse tags');
        assert($parsedData['canonical_url'] === 'https://example.com/my-test-post', 'Failed to parse canonical_url');
        assert($parsedBody === "This is the body of the post.", 'Failed to parse body');
    }

    return;
}

function test_to_hash_tag(): void
{
    assert(to_hash_tag('php') === '#Php', 'Failed to convert single word tag');
    assert(to_hash_tag('my-tag') === '#MyTag', 'Failed to convert hyphenated tag');
    assert(to_hash_tag('another tag') === '#AnotherTag', 'Failed to convert spaced tag');
    assert(to_hash_tag('multi_word-tag') === '#MultiWordTag', 'Failed to convert multi-word hyphenated tag');
}

function test_build_linked_in_post(): void
{
    $data = [
        'title' => 'My Test Post',
        'description' => 'This is a test post.',
        'tags' => ['test', 'php', 'announce'],
        'canonical_url' => 'https://example.com/my-test-post',
    ];

    $post = build_linked_in_post($data);
    assert(str_contains($post, '#Test'), 'LinkedIn post missing #Test hashtag');
    assert(str_contains($post, '#Php'), 'LinkedIn post missing #Php hashtag');
    assert(str_contains($post, '#Announce'), 'LinkedIn post missing #Announce hashtag');
}

/**
 * Debug output function to print messages to STDERR.
 *  
 * @param string $message The debug message to print.
 * @param bool $exit Whether to exit the script after printing the message.
 * @param string $exit_string The exit message to print if exiting.
 * 
 * @return void
 */
function _debug_output(string $message, bool $exit = FALSE, string $exit_string = ''): void
{
    if (!DEBUG_CONST)
    {
        return;
    }

    $time_stamp = date('Y-m-d H:i:s');

    if ($exit)
    {
        fwrite(STDERR, "[{$time_stamp}] - DEBUG: {$message}\n");
        exit($exit_string);
    }
    else
    {
        fwrite(STDERR, "[{$time_stamp}] - DEBUG: {$message}\n");
    }
}

main($argv);
