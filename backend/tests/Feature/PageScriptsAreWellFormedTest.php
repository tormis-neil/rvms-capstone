<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every dashboard page's inline <script> must be balanced.
 *
 * The rest of the suite structurally cannot see this: every other test drives
 * the server over HTTP and never executes page JavaScript, so a page whose
 * script is malformed still returns 200 with all the right markup and passes
 * everything.
 *
 * A real regression motivated this (2026-08). Removing the delete button cut
 * from its comment to the first line matching `});`, which was the inner close
 * of a callback rather than the end of the block — leaving two orphaned closers
 * behind on Vehicles and Drivers. A script that does not parse does not run AT
 * ALL, so every listener in it was lost, including the one that fills the View
 * modal from the clicked row. The modal opened showing stale content, which
 * read as "the wrong vehicle's details" while the page looked perfectly normal
 * and the whole test suite stayed green.
 *
 * Counting braces is deliberately cruder than parsing: it needs no JavaScript
 * engine, so it runs anywhere `php artisan test` runs, and unbalanced braces is
 * the shape this class of edit always takes.
 */
class PageScriptsAreWellFormedTest extends TestCase
{
    /** @return list<array{string}> */
    public static function bladeViewsWithScripts(): array
    {
        $root = dirname(__DIR__, 2).'/resources/views';
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));

        $cases = [];
        foreach ($files as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            $contents = (string) file_get_contents($file->getPathname());
            if (! preg_match('/<script(?![^>]*\bsrc=)[^>]*>/', $contents)) {
                continue;
            }
            $relative = str_replace($root.'/', '', $file->getPathname());
            $cases[$relative] = [$relative];
        }

        ksort($cases);

        return $cases;
    }

    #[DataProvider('bladeViewsWithScripts')]
    public function test_the_page_script_is_balanced(string $relativePath): void
    {
        $path = dirname(__DIR__, 2).'/resources/views/'.$relativePath;
        $contents = (string) file_get_contents($path);

        preg_match_all('/<script(?![^>]*\bsrc=)[^>]*>(.*?)<\/script>/s', $contents, $matches);

        foreach ($matches[1] as $index => $script) {
            $js = self::stripBlade($script);

            $open = substr_count($js, '{');
            $close = substr_count($js, '}');

            $this->assertSame(
                $open,
                $close,
                sprintf(
                    "%s: script block %d has %d '{' and %d '}'. An unbalanced script does not "
                    .'parse, and a script that does not parse does not run at all — every '
                    .'handler on the page is silently lost while the page still renders.',
                    $relativePath, $index, $open, $close
                )
            );
        }
    }

    /**
     * Remove the Blade constructs that carry braces of their own, so what is
     * counted is the JavaScript and nothing else.
     *
     * `@json(...)` is matched by balancing parentheses rather than lazily: it
     * routinely wraps a nested call such as `@json(route('x', ['id' => 1]))`,
     * and stopping at the first `)` leaves a stray one that looks like a defect
     * in a file that is perfectly fine.
     */
    private static function stripBlade(string $script): string
    {
        $script = preg_replace('/\{!!.*?!!\}/s', 'null', $script) ?? $script;
        $script = self::stripDirective($script, '@json');

        return preg_replace('/\{\{.*?\}\}/s', 'null', $script) ?? $script;
    }

    private static function stripDirective(string $subject, string $tag): string
    {
        $out = '';
        $offset = 0;

        while (($start = strpos($subject, $tag, $offset)) !== false) {
            $cursor = $start + strlen($tag);
            while ($cursor < strlen($subject) && in_array($subject[$cursor], [' ', "\t"], true)) {
                $cursor++;
            }

            if ($cursor >= strlen($subject) || $subject[$cursor] !== '(') {
                $out .= substr($subject, $offset, $cursor - $offset);
                $offset = $cursor;

                continue;
            }

            $depth = 0;
            $end = $cursor;
            for (; $end < strlen($subject); $end++) {
                if ($subject[$end] === '(') {
                    $depth++;
                } elseif ($subject[$end] === ')') {
                    $depth--;
                    if ($depth === 0) {
                        break;
                    }
                }
            }

            $out .= substr($subject, $offset, $start - $offset).'null';
            $offset = $end + 1;
        }

        return $out.substr($subject, $offset);
    }
}
