<?php

declare(strict_types=1);

namespace BeLocal\Tests\Unit;

use BeLocal\Transport;
use PHPUnit\Framework\TestCase;

class SdkVersionTagTest extends TestCase
{
    /**
     * This test checks that the git tag associated with the current commit
     * contains a semantic version matching Transport::SDK_VERSION.
     *
     * The test will fail if no exact tag is present on HEAD, ensuring that
     * tags are created before pushing commits.
     */
    public function testSdkVersionMatchesGitTag()
    {
        $output = [];
        $exitCode = 0;
        @exec('git describe --tags --exact-match 2>&1', $output, $exitCode);

        if ($exitCode !== 0 || empty($output)) {
            $this->fail('No exact git tag found on HEAD. Tag must be created before pushing commits.');
            return;
        }

        $tag = trim(implode("\n", $output));

        if (!preg_match('/(?P<version>\d+\.\d+\.\d+)/', $tag, $m)) {
            $this->fail('Current tag does not contain a semantic version (x.y.z): ' . $tag);
        }

        $tagVersion = $m['version'];

        $this->assertSame(
            Transport::SDK_VERSION,
            $tagVersion,
            sprintf('SDK version (%s) must match current git tag version (%s). Full tag: %s', Transport::SDK_VERSION, $tagVersion, $tag)
        );
    }
}
