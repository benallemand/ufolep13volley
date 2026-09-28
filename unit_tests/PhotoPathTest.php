<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../classes/Photo.php';

use PHPUnit\Framework\TestCase;

/**
 * Issue #352 — `photo/get_photo` est public et servait n'importe quel fichier
 * du site. Seules les images de `teams_pics/` et `images/` restent servables.
 *
 * Aucun accès base : `resolve_servable_path` ne fait que du système de fichiers.
 */
class PhotoPathTest extends TestCase
{
    public function test_une_image_d_equipe_est_servie(): void
    {
        $team_pics = glob(__DIR__ . '/../teams_pics/*.{jpg,jpeg,png}', GLOB_BRACE);
        if (empty($team_pics)) {
            self::markTestSkipped('aucune photo d\'équipe dans ce checkout');
        }
        $relative = 'teams_pics/' . basename($team_pics[0]);
        self::assertSame(realpath($team_pics[0]), Photo::resolve_servable_path($relative));
    }

    public function test_une_image_du_site_est_servie(): void
    {
        self::assertSame(realpath(__DIR__ . '/../images/unknownTeam.png'),
            Photo::resolve_servable_path('images/unknownTeam.png'));
    }

    /**
     * @dataProvider chemins_interdits
     */
    public function test_les_autres_fichiers_sont_refuses(mixed $path): void
    {
        self::assertNull(Photo::resolve_servable_path($path));
    }

    public static function chemins_interdits(): array
    {
        return array(
            'fichier de configuration' => array('.env'),
            'code source' => array('classes/Database.php'),
            'fichier du dépôt' => array('composer.json'),
            'remontée de répertoire' => array('teams_pics/../.env'),
            'remontée hors du site' => array('../../../../etc/passwd'),
            'chemin absolu' => array('/etc/passwd'),
            'image hors des répertoires autorisés' => array('dist/../images/../composer.json'),
            'répertoire autorisé, fichier non image' => array('images/../rest/access.php'),
            'octet nul' => array("images/unknownTeam.png\0.php"),
            'fichier absent' => array('teams_pics/n-existe-pas.jpg'),
            'vide' => array(''),
            'null' => array(null),
            'tableau' => array(array('images/unknownTeam.png')),
        );
    }
}
