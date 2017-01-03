<?php
declare(strict_types = 1);

namespace mheinzerling\dist;


use mheinzerling\commons\NoopFtpConnection;

class TemplateTest extends \PHPUnit_Framework_TestCase
{

    public function testGet()
    {
        $file = Template::get("root.htaccess");
        static::assertTrue(file_exists($file), $file);
    }

    public function testUpload()
    {
        $ftp = new class extends NoopFtpConnection
        {
            public function upload(string $target, $source, int $mode = FTP_ASCII, \Closure $progressCallback = null): void
            {
                \PHPUnit_Framework_TestCase::assertEquals(".htaccess", $target);
                $progressCallback(0, 102);
                \PHPUnit_Framework_TestCase::assertEquals("RewriteEngine on\n\nRewriteCond %{REQUEST_FILENAME} !/context/xyz\nRewriteRule .* /context/xyz/$0 [L,QSA]", stream_get_contents($source));
                $progressCallback(70, 102);
                \PHPUnit_Framework_TestCase::assertEquals(FTP_BINARY, $mode);
                $progressCallback(80, 102);
                $progressCallback(90, 102);
                $progressCallback(102, 102);
            }
        };
        $output = new TestOutput();
        Template::upload($ftp, $output, Template::get("root.htaccess"), ".htaccess", ['VERSION' => "xyz", "PATH" => "context"]);
        static::assertEquals(str_replace("\r", "", "   0/102 [>---------------------------]   0%
  70/102 [===========================================>--------------------]  68%
  80/102 [==================================================>-------------]  78%
  90/102 [========================================================>-------]  88%
 102/102 [================================================================] 100%
"), $output->output);
    }
}