<?php
declare(strict_types=1);

namespace Shivas\VersioningBundle\Tests\Formatter;

use PHPUnit\Framework\TestCase;
use Shivas\VersioningBundle\Formatter\GitDescribeFormatter;
use Version\Version;

/**
 * Class GitDescribeFormatterTest
 */
final class GitDescribeFormatterTest extends TestCase
{
    public function testInitializable(): void
    {
        $formatter = new GitDescribeFormatter();

        $this->assertInstanceOf(GitDescribeFormatter::class, $formatter);
    }

    public function testInitialVersion(): void
    {
        $formatter = new GitDescribeFormatter();
        $version = Version::fromString('0.1.0');

        $this->assertEquals('0.1.0', $formatter->format($version), 'Basic version number');
    }

    public function testGitVersion(): void
    {
        $formatter = new GitDescribeFormatter();
        $version = Version::fromString('1.4.1-0-gd891f45');

        $this->assertEquals('1.4.1', $formatter->format($version), 'Tag commit should ignore hash');
    }

    public function testGitHashVersion(): void
    {
        $formatter = new GitDescribeFormatter();
        $version = Version::fromString('1.4.1-1-g7f07e6d');

        $this->assertEquals('1.4.1-dev.g7f07e6d', $formatter->format($version), 'Not on tag commit adds dev.hash');
    }

    public function testGitMultipleCommitsVersion(): void
    {
        $formatter = new GitDescribeFormatter();
        $version = Version::fromString('2.3.3-201-g1c224d9fa');

        $this->assertEquals('2.3.3-dev.g1c224d9fa', $formatter->format($version), 'Multiple commits since last tag');
    }

    public function testGitLongVersion(): void
    {
        $formatter = new GitDescribeFormatter();
        $version = Version::fromString('1.2.3-foo-bar.1-0-gd891f45');

        $this->assertEquals('1.2.3-foo-bar.1', $formatter->format($version), 'Long version on tag commit');
    }

    public function testGitLongHashVersion(): void
    {
        $formatter = new GitDescribeFormatter();
        $version = Version::fromString('1.2.3-foo-bar.1-203-g13ebcdd');

        $this->assertEquals('1.2.3-foo-bar.1.dev.13ebcdd', $formatter->format($version), 'Long version not on tag commit');
    }
}
