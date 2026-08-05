<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Skip a test that needs a REAL raster image, when PHP cannot make one.
     *
     * `UploadedFile::fake()->image()` draws its file through PHP's GD
     * extension, which is not enabled in every Windows PHP build. RVMS itself
     * never needs GD — a damage photo is stored as uploaded, and the `image`
     * and `mimes` rules sniff the mime type through finfo — so a machine
     * without it runs the whole system correctly and only cannot FABRICATE a
     * photo inside a test.
     *
     * Skipping rather than failing keeps that distinction honest: a red suite
     * should mean the code is wrong, not that a developer's PHP was built with
     * different flags. The security half of this area does not depend on GD at
     * all — `test_an_svg_is_refused_as_a_photo` builds its SVG with
     * `createWithContent`, so the rule that closed C1 is still proven
     * everywhere. What is skipped is the companion check that legitimate
     * jpeg/png/webp uploads still succeed, which the manual checklist covers by
     * filing a report with a real camera photo.
     */
    protected function requiresImageGeneration(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped(
                "PHP's GD extension is not enabled, so a fake image cannot be generated. "
                .'Enable `extension=gd` in the php.ini reported by `php --ini` to run this test. '
                .'The application itself does not require GD.'
            );
        }
    }
}
