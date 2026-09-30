<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/UfolepTestCase.php';
require_once __DIR__ . '/../classes/SqlManager.php';
require_once __DIR__ . '/../classes/Emails.php';

/**
 * Issue #314 — renvoyer un email précis, au lieu de relancer toutes les erreurs.
 *
 * L'envoi réel dépend de l'environnement (Mailpit en dev, aucun serveur de
 * mail en CI) : le test vérifie que l'envoi a été TENTÉ — la ligne ne reste pas
 * en file (TO_DO) — et que le résultat se lit dans le statut.
 */
class EmailResendTest extends UfolepTestCase
{
    private int $id_email;

    protected function setUp(): void
    {
        parent::setUp();
        $this->delete_test_data();
        $this->id_email = (int)$this->sql->execute(
            "INSERT INTO emails SET from_email = 'noreply@ufolep.test', to_email = 'issue314@ufolep.test', cc = '', bcc = '',
                 subject = 'issue314 sujet', body = '<p>issue314 corps</p>', sending_status = 'ERROR',
                 creation_date = NOW()");
        $this->sql->execute(
            "INSERT INTO emails SET from_email = 'noreply@ufolep.test', to_email = 'issue314.autre@ufolep.test', cc = '', bcc = '',
                 subject = 'issue314 autre', body = '<p>autre</p>', sending_status = 'ERROR', creation_date = NOW()");
        $this->connect_as_admin();
    }

    protected function tearDown(): void
    {
        $this->delete_test_data();
        parent::tearDown();
    }

    private function delete_test_data(): void
    {
        $this->sql->execute("DELETE FROM emails WHERE subject LIKE 'issue314%'");
        $this->sql->execute("DELETE FROM activity WHERE comment LIKE 'Email renvoyé à issue314%'");
    }

    private function status(string $subject): string
    {
        return $this->sql->execute("SELECT sending_status FROM emails WHERE subject = ?",
            array(array('type' => 's', 'value' => $subject)))[0]['sending_status'];
    }

    public function test_renvoyer_tente_l_envoi_de_ce_seul_message(): void
    {
        (new Emails())->resend_email($this->id_email);

        $this->assertContains($this->status('issue314 sujet'), array('DONE', 'ERROR'),
            'l\'envoi est tenté tout de suite : la ligne ne reste pas en file');
        $this->assertSame('ERROR', $this->status('issue314 autre'), 'les autres erreurs ne sont pas touchées');
        $this->assertCount(1, $this->sql->execute(
            "SELECT id FROM activity WHERE comment = 'Email renvoyé à issue314@ufolep.test : issue314 sujet'"));
    }

    public function test_le_statut_se_relit_sans_le_corps(): void
    {
        $status = (new Emails())->get_email_status($this->id_email);
        $this->assertSame(array('id', 'sending_status', 'sent_date'), array_keys($status));
        $this->assertSame('ERROR', $status['sending_status']);
    }

    public function test_reserve_a_l_admin_et_email_inconnu(): void
    {
        $this->connect_as_team_leader(1);
        try {
            (new Emails())->resend_email($this->id_email);
            $this->fail('réservé à l\'admin');
        } catch (Exception $e) {
            $this->assertSame(403, $e->getCode());
        }
        $this->assertSame('ERROR', $this->status('issue314 sujet'));

        $this->connect_as_admin();
        foreach (array(fn() => (new Emails())->resend_email(999999999), fn() => (new Emails())->get_email_status(999999999)) as $call) {
            try {
                $call();
                $this->fail('email inconnu');
            } catch (Exception $e) {
                $this->assertSame(404, $e->getCode());
            }
        }
        try {
            (new Emails())->resend_email('1 OR 1=1');
            $this->fail('identifiant invalide');
        } catch (Exception $e) {
            $this->assertStringContainsString('invalide', $e->getMessage());
        }
    }
}
