<?php

/**
 * Traitement des photos de joueurs (issue #461).
 *
 * Une photo d'iPhone est enregistrée couchée, avec une étiquette EXIF
 * « orientation » qui dit comment la tourner. Le navigateur l'applique, mais GD
 * l'ignore et ne la recopie pas : toute version réencodée restait de travers.
 * `normalize()` redresse donc l'image d'après cette étiquette, la ramène à une
 * taille maximale et l'écrit en vrai JPEG, sans étiquette.
 *
 * L'orientation est lue directement dans le fichier : l'extension PHP `exif`
 * n'est pas installée partout (absente de l'image Docker et de la CI).
 */
class PhotoImage
{
    /** Original conservé : de quoi imprimer une fiche équipe. */
    public const MAX_WIDTH = 900;
    public const MAX_HEIGHT = 1200;
    /** Version allégée (`players_pics_low`) : vignettes, live, fiches équipe. */
    public const LOW_WIDTH = 300;
    public const LOW_HEIGHT = 400;

    /**
     * Orientation EXIF (1 à 8) d'un JPEG ; 1 si absente ou illisible.
     */
    public static function exif_orientation(string $path): int
    {
        $data = @file_get_contents($path, false, null, 0, 256 * 1024);
        if ($data === false || strlen($data) < 4 || substr($data, 0, 2) !== "\xFF\xD8") {
            return 1;
        }
        $offset = 2;
        $length = strlen($data);
        while ($offset + 4 <= $length && $data[$offset] === "\xFF") {
            $marker = ord($data[$offset + 1]);
            $size = unpack('n', substr($data, $offset + 2, 2))[1];
            if ($marker === 0xE1 && substr($data, $offset + 4, 6) === "Exif\0\0") {
                return self::tiff_orientation(substr($data, $offset + 10, $size - 8));
            }
            if ($marker === 0xDA) {
                break; // début de l'image : plus d'en-tête
            }
            $offset += 2 + $size;
        }
        return 1;
    }

    /** Lit la balise 0x0112 du premier répertoire d'un bloc TIFF. */
    private static function tiff_orientation(string $tiff): int
    {
        if (strlen($tiff) < 8) {
            return 1;
        }
        $little = substr($tiff, 0, 2) === 'II';
        if (!$little && substr($tiff, 0, 2) !== 'MM') {
            return 1;
        }
        $short = fn(int $at) => unpack($little ? 'v' : 'n', substr($tiff, $at, 2))[1];
        $long = fn(int $at) => unpack($little ? 'V' : 'N', substr($tiff, $at, 4))[1];
        $ifd = $long(4);
        if ($ifd + 2 > strlen($tiff)) {
            return 1;
        }
        $entries = $short($ifd);
        for ($i = 0; $i < $entries; $i++) {
            $entry = $ifd + 2 + 12 * $i;
            if ($entry + 12 > strlen($tiff)) {
                break;
            }
            if ($short($entry) === 0x0112) {
                $value = $short($entry + 8);
                return $value >= 1 && $value <= 8 ? $value : 1;
            }
        }
        return 1;
    }

    /**
     * Redresse, réduit et réencode une photo.
     *
     * @param string $source fichier d'origine (JPEG, PNG, GIF ou WebP)
     * @param string|null $destination fichier écrit ; l'original par défaut
     * @return bool false si l'image est illisible (le fichier n'est pas touché)
     */
    public static function normalize(string $source, int $max_width = self::MAX_WIDTH,
                                     int $max_height = self::MAX_HEIGHT, ?string $destination = null,
                                     int $quality = 85): bool
    {
        $content = @file_get_contents($source);
        $image = $content === false ? false : @imagecreatefromstring($content);
        if ($image === false) {
            return false;
        }
        $image = self::apply_orientation($image, self::exif_orientation($source));

        $width = imagesx($image);
        $height = imagesy($image);
        $ratio = min(1, $max_width / $width, $max_height / $height);
        $target_width = max(1, (int)round($width * $ratio));
        $target_height = max(1, (int)round($height * $ratio));
        $output = imagecreatetruecolor($target_width, $target_height);
        // fond blanc sous la transparence d'un PNG ou d'un GIF
        imagefill($output, 0, 0, imagecolorallocate($output, 255, 255, 255));
        imagecopyresampled($output, $image, 0, 0, 0, 0, $target_width, $target_height, $width, $height);
        imagedestroy($image);

        $written = imagejpeg($output, $destination ?? $source, $quality);
        imagedestroy($output);
        return $written;
    }

    /** Version allégée d'une photo, dans `players_pics_low`. */
    public static function low(string $source, string $destination): bool
    {
        $folder = dirname($destination);
        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }
        return self::normalize($source, self::LOW_WIDTH, self::LOW_HEIGHT, $destination, 75);
    }

    /**
     * Applique une orientation EXIF : 2 à 8 combinent un quart de tour et un
     * effet miroir. `imagerotate()` tourne dans le sens inverse des aiguilles.
     * @param GdImage $image
     * @return GdImage
     */
    private static function apply_orientation($image, int $orientation)
    {
        if (in_array($orientation, array(2, 4, 5, 7), true)) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        }
        // miroir puis : 5 = transposée, 7 = transverse
        $angle = match ($orientation) {
            3, 4 => 180,
            6, 7 => 270,
            5, 8 => 90,
            default => 0,
        };
        if ($angle !== 0) {
            $rotated = imagerotate($image, $angle, 0);
            imagedestroy($image);
            $image = $rotated;
        }
        return $image;
    }
}
