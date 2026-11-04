<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../rest/trim_parameters.php';

use PHPUnit\Framework\TestCase;

/**
 * Issue #452 — les saisies arrivent sans blancs de début et de fin.
 */
class TrimParametersTest extends TestCase
{
    public function test_blancs_de_debut_et_de_fin_retires(): void
    {
        $this->assertSame('BONHOMME', trim_text("\tBONHOMME"));
        $this->assertSame('Cyril', trim_text("  Cyril \r\n"));
        // espace insécable et BOM d'un copier-coller
        $this->assertSame('Grégory', trim_text("\u{00A0}Grégory\u{FEFF}"));
        $this->assertSame('', trim_text(" \t "));
    }

    public function test_le_milieu_de_la_chaine_est_garde(): void
    {
        $this->assertSame('LAVILLE DE LA PLAIGNE', trim_text(' LAVILLE DE LA PLAIGNE '));
        $this->assertSame("ligne 1\nligne 2", trim_text("\nligne 1\nligne 2\n"));
    }

    public function test_tous_les_parametres_texte_tableaux_compris(): void
    {
        $this->assertSame(
            array(
                'nom' => 'BONHOMME',
                'prenom' => 'Cyril',
                'id_club' => '16',
                'reinforcements' => array('12' => '34', '13' => 'x'),
            ),
            trim_text_parameters(array(
                'nom' => "\tBONHOMME",
                'prenom' => 'Cyril ',
                'id_club' => ' 16',
                'reinforcements' => array('12' => '34 ', '13' => ' x'),
            )));
    }

    public function test_les_mots_de_passe_ne_sont_pas_touches(): void
    {
        $parameters = trim_text_parameters(array(
            'new_password' => ' secret ',
            'new_password_again' => ' secret ',
            'password' => "mot de passe\t",
        ));
        $this->assertSame(' secret ', $parameters['new_password']);
        $this->assertSame(' secret ', $parameters['new_password_again']);
        $this->assertSame("mot de passe\t", $parameters['password']);
    }

    public function test_le_routeur_nettoie_les_ecritures(): void
    {
        $source = file_get_contents(__DIR__ . '/../rest/action.php');
        $this->assertSame(1, preg_match(
            "/case 'POST':.*?trim_text_parameters\\(\\\$parameters\\).*?call_user_func_array/s", $source));
    }
}
