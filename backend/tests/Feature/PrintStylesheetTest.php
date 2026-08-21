<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The printed report keeps its bars, and style.css stays untouched (2026-08).
 *
 * A saved PDF of a report showed the labels and the numbers but no bars at all
 * (lead-reported). Bootstrap paints .progress / .progress-bar entirely with
 * background-color, and browsers DROP background colours when printing unless
 * the page opts in — Chrome's "Background graphics" box is off by default. The
 * stat tiles survived because they are drawn with borders, which was the tell.
 *
 * Verified in headless Chromium before this test was written: the same markup
 * printed with printBackground:false yields a PDF containing only white fills
 * without the rule, and the amber fill 1 .7569 .0275 (#ffc107) with it.
 *
 * These assertions are the regression guard rather than the proof — no
 * HTTP-level test runs a print pipeline, so what is checked here is that the
 * rule still exists, still reaches the page, and still cannot have been put in
 * the one file that must never change.
 */
class PrintStylesheetTest extends TestCase
{
    private function adminCss(): string
    {
        $path = public_path('assets/css/admin.css');

        $this->assertFileExists($path, 'admin.css is missing — printed reports lose their bars without it.');

        return file_get_contents($path);
    }

    public function test_the_report_area_opts_in_to_printing_backgrounds(): void
    {
        $css = $this->adminCss();

        $this->assertMatchesRegularExpression(
            '/@media\s+print\s*\{.*?#reportPrintArea.*?print-color-adjust:\s*exact/s',
            $css,
            'The print rule that keeps report bars visible is gone. A saved PDF will '
            .'show the labels and numbers with no bars.'
        );

        // Chrome and Safari still need the prefixed form.
        $this->assertStringContainsString('-webkit-print-color-adjust: exact', $css);
    }

    /**
     * Scoped to the report, not the whole page: forcing every page to print its
     * backgrounds would burn ink on the sidebar and the topbar chrome.
     */
    public function test_the_opt_in_is_scoped_to_the_report_and_not_the_whole_page(): void
    {
        $css = $this->adminCss();

        preg_match('/@media\s+print\s*\{(.*?)\n\}/s', $css, $matches);

        $this->assertNotEmpty($matches, 'No @media print block in admin.css.');
        $this->assertStringNotContainsString(
            'body',
            $matches[1],
            'The print opt-in reaches beyond the report area.'
        );
    }

    /** A fallback for drivers that refuse background graphics whatever the page asks. */
    public function test_the_bar_track_keeps_a_printed_outline(): void
    {
        $this->assertMatchesRegularExpression(
            '/#reportPrintArea\s+\.progress\s*\{[^}]*border:/s',
            $this->adminCss(),
            'Without the track border a bar degrades to nothing rather than to an outline.'
        );
    }

    /** The rules only apply because admin.css loads AFTER style.css. */
    public function test_the_layout_loads_admin_css_after_style_css(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));

        $style = strpos($layout, 'assets/css/style.css');
        $admin = strpos($layout, 'assets/css/admin.css');

        $this->assertNotFalse($admin, 'The layout no longer loads admin.css.');
        $this->assertGreaterThan($style, $admin, 'admin.css must load AFTER style.css or its rules lose the cascade.');
    }

    /**
     * Non-Negotiable Rule 9, asserted rather than trusted.
     *
     * style.css is a copy of the prototype's stylesheet and every side-by-side
     * checkpoint since R1 has depended on it being byte-identical. This is the
     * reason admin.css exists at all, so it is the right place to guard it: an
     * edit to style.css would silently invalidate every checkpoint the project
     * has passed, and nothing else in the suite would notice.
     */
    public function test_style_css_is_still_a_byte_identical_copy_of_the_prototype(): void
    {
        $prototype = base_path('../web/assets/css/style.css');

        if (! file_exists($prototype)) {
            $this->markTestSkipped('The prototype tree is not present in this checkout.');
        }

        $this->assertSame(
            hash_file('sha256', $prototype),
            hash_file('sha256', public_path('assets/css/style.css')),
            'public/assets/css/style.css has been edited. It is a copy of the prototype and must '
            .'stay byte-identical (Rule 9) — put backend-only rules in admin.css instead.'
        );
    }
}
