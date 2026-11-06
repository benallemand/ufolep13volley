<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../classes/PhotoImage.php';

use PHPUnit\Framework\TestCase;

/**
 * Issue #461 — photos redressées d'après leur orientation EXIF, réduites et
 * réencodées en JPEG.
 *
 * L'image de référence, « à l'endroit », est une grille de 3 × 2 carrés de
 * couleurs différentes. Pour chaque orientation, on l'enregistre comme le
 * ferait un appareil photo (pixels déplacés à la main, sans GD), avec
 * l'étiquette EXIF correspondante : une fois normalisée, elle doit redevenir
 * la grille de référence.
 */
class PhotoImageTest extends TestCase
{
    private const BLOCK = 20;
    private const COLS = 3;
    private const ROWS = 2;
    private const COLORS = array(
        array(220, 30, 30), array(30, 200, 30), array(30, 30, 220),
        array(230, 210, 20), array(20, 200, 210), array(200, 30, 200),
    );

    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/photo_image_test_' . uniqid();
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: array() as $file) {
            unlink($file);
        }
        rmdir($this->dir);
    }

    private function color_at(int $x, int $y): array
    {
        return self::COLORS[intdiv($y, self::BLOCK) * self::COLS + intdiv($x, self::BLOCK)];
    }

    /** Position, dans l'image enregistrée, du pixel (x, y) de l'image à l'endroit. */
    private function stored_position(int $orientation, int $x, int $y, int $w, int $h): array
    {
        return match ($orientation) {
            1 => array($x, $y),
            2 => array($w - 1 - $x, $y),
            3 => array($w - 1 - $x, $h - 1 - $y),
            4 => array($x, $h - 1 - $y),
            5 => array($y, $x),
            6 => array($y, $w - 1 - $x),
            7 => array($h - 1 - $y, $w - 1 - $x),
            8 => array($h - 1 - $y, $x),
        };
    }

    /** JPEG « tel que pris par l'appareil », avec son étiquette d'orientation. */
    private function camera_jpeg(int $orientation, bool $little_endian = false): string
    {
        $w = self::COLS * self::BLOCK;
        $h = self::ROWS * self::BLOCK;
        $turned = $orientation >= 5;
        $image = imagecreatetruecolor($turned ? $h : $w, $turned ? $w : $h);
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                [$sx, $sy] = $this->stored_position($orientation, $x, $y, $w, $h);
                [$r, $g, $b] = $this->color_at($x, $y);
                imagesetpixel($image, $sx, $sy, imagecolorallocate($image, $r, $g, $b));
            }
        }
        ob_start();
        imagejpeg($image, null, 95);
        $jpeg = ob_get_clean();
        imagedestroy($image);

        // n / N : entiers 16 / 32 bits, gros-boutistes (« MM ») ou petits-boutistes (« II »)
        $pack = fn(string $format, int $value) => pack($little_endian ? ($format === 'n' ? 'v' : 'V') : $format, $value);
        $tiff = ($little_endian ? 'II' : 'MM') . $pack('n', 42) . $pack('N', 8)
            . $pack('n', 1)                                                    // une entrée
            . $pack('n', 0x0112) . $pack('n', 3) . $pack('N', 1)               // orientation, SHORT, 1 valeur
            . $pack('n', $orientation) . "\0\0"
            . $pack('N', 0);                                                   // pas d'autre répertoire
        $app1 = "Exif\0\0" . $tiff;
        return "\xFF\xD8" . "\xFF\xE1" . pack('n', strlen($app1) + 2) . $app1 . substr($jpeg, 2);
    }

    private function write(string $name, string $content): string
    {
        $path = "$this->dir/$name";
        file_put_contents($path, $content);
        return $path;
    }

    private function assert_upright(string $path): void
    {
        $image = imagecreatefromjpeg($path);
        self::assertSame(self::COLS * self::BLOCK, imagesx($image));
        self::assertSame(self::ROWS * self::BLOCK, imagesy($image));
        foreach (range(0, self::COLS * self::ROWS - 1) as $index) {
            $cx = ($index % self::COLS) * self::BLOCK + intdiv(self::BLOCK, 2);
            $cy = intdiv($index, self::COLS) * self::BLOCK + intdiv(self::BLOCK, 2);
            $rgb = imagecolorat($image, $cx, $cy);
            $actual = array(($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, $rgb & 0xFF);
            foreach (self::COLORS[$index] as $channel => $expected) {
                self::assertEqualsWithDelta($expected, $actual[$channel], 40,
                    "carré $index : couleur attendue " . implode(',', self::COLORS[$index]) . ', lue ' . implode(',', $actual));
            }
        }
        imagedestroy($image);
    }

    public function test_lit_l_orientation_exif(): void
    {
        foreach (range(1, 8) as $orientation) {
            self::assertSame($orientation, PhotoImage::exif_orientation($this->write("o$orientation.jpg", $this->camera_jpeg($orientation))));
        }
        self::assertSame(6, PhotoImage::exif_orientation($this->write('le.jpg', $this->camera_jpeg(6, true))));
    }

    public function test_sans_etiquette_l_orientation_vaut_1(): void
    {
        $image = imagecreatetruecolor(10, 10);
        $path = "$this->dir/plain.jpg";
        imagejpeg($image, $path);
        self::assertSame(1, PhotoImage::exif_orientation($path));
        self::assertSame(1, PhotoImage::exif_orientation($this->write('png.jpg', "\x89PNG\r\n\x1a\n")));
        self::assertSame(1, PhotoImage::exif_orientation("$this->dir/absent.jpg"));
    }

    public function test_chaque_orientation_est_redressee(): void
    {
        foreach (range(1, 8) as $orientation) {
            $path = $this->write("o$orientation.jpg", $this->camera_jpeg($orientation));
            self::assertTrue(PhotoImage::normalize($path));
            $this->assert_upright($path);
            self::assertSame(1, PhotoImage::exif_orientation($path), "orientation $orientation : plus d'étiquette");
        }
    }

    public function test_une_grande_photo_est_reduite_en_gardant_ses_proportions(): void
    {
        // photo d'iPhone : 4032 × 3024 couchée, orientation 6
        $image = imagecreatetruecolor(4032, 3024);
        ob_start();
        imagejpeg($image, null, 50);
        $jpeg = ob_get_clean();
        imagedestroy($image);
        $app1 = "Exif\0\0MM\0\x2A\0\0\0\x08\0\x01\x01\x12\0\x03\0\0\0\x01\0\x06\0\0\0\0\0\0";
        $path = $this->write('iphone.jpg', "\xFF\xD8\xFF\xE1" . pack('n', strlen($app1) + 2) . $app1 . substr($jpeg, 2));

        $low = "$this->dir/low/iphone.jpg";
        self::assertTrue(PhotoImage::low($path, $low));
        self::assertSame(array(300, 400), array_slice(getimagesize($low), 0, 2));

        self::assertTrue(PhotoImage::normalize($path));
        self::assertSame(array(900, 1200), array_slice(getimagesize($path), 0, 2));
        unlink($low);
        rmdir(dirname($low));
    }

    public function test_une_petite_photo_n_est_pas_agrandie_et_un_png_devient_un_jpeg(): void
    {
        $image = imagecreatetruecolor(120, 160);
        $path = "$this->dir/small.jpg";
        imagepng($image, $path);
        imagedestroy($image);
        self::assertTrue(PhotoImage::normalize($path));
        $info = getimagesize($path);
        self::assertSame(array(120, 160), array_slice($info, 0, 2));
        self::assertSame('image/jpeg', $info['mime']);
    }

    public function test_un_fichier_illisible_n_est_pas_touche(): void
    {
        $path = $this->write('broken.jpg', 'pas une image');
        self::assertFalse(PhotoImage::normalize($path));
        self::assertSame('pas une image', file_get_contents($path));
    }
}
