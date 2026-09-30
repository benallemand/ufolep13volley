// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Issue #314 — renvoyer un email précis depuis l'écran Emails.
 *
 * Setup : un email en erreur (`emails_setup.php`). L'envoi réel dépend de
 * l'environnement (Mailpit en dev, aucun serveur de mail en CI) : on vérifie
 * que le renvoi est tenté et que la fenêtre affiche le statut relu, pas que le
 * message arrive.
 */
async function loginAsAdmin(page, request, baseURL) {
    const res = await request.get('/e2e/helpers/admin_session.php');
    expect(res.status(), 'Le helper de session admin doit répondre 200').toBe(200);
    const { session_id } = await res.json();
    await page.context().addCookies([{ name: 'PHPSESSID', value: session_id, url: baseURL }]);
}

test.describe('Issue #314 — renvoyer un email', () => {
    test.beforeEach(async ({ request }) => {
        const body = await (await request.get('/e2e/helpers/emails_setup.php')).json();
        expect(body.error, `Setup failed: ${body.error}`).toBeUndefined();
    });

    test.afterEach(async ({ request }) => {
        await request.get('/e2e/helpers/emails_setup.php?teardown=1');
    });

    test('la fenêtre du message renvoie ce message-là et affiche son nouveau statut', async ({ page, request, baseURL }) => {
        await loginAsAdmin(page, request, baseURL);
        await page.goto('/admin/index.html#/emails');
        await page.getByPlaceholder(/Rechercher/).fill('E2E issue314 renvoi');
        const row = page.locator('tbody tr', { hasText: 'E2E issue314 renvoi' });
        await expect(row).toHaveCount(1, { timeout: 15000 });
        await row.click();

        const dialog = page.locator('dialog.modal-open');
        await expect(dialog).toContainText('e2e.issue314@ufolep.test');
        await expect(dialog.locator('.badge')).toHaveText('ERROR');
        // La prévisualisation reste dans une iframe sandboxée (#292)
        await expect(dialog.locator('iframe[sandbox=""]')).toHaveCount(1);

        page.once('dialog', (confirm) => confirm.accept());
        const [resend] = await Promise.all([
            page.waitForResponse((r) => r.url().includes('/emails/resend_email')),
            dialog.getByTestId('email-resend').click(),
        ]);
        expect(resend.status(), await resend.text()).toBe(200);
        const status = await page.waitForResponse((r) => r.url().includes('/emails/get_email_status'));
        const { sending_status } = await status.json();
        expect(['DONE', 'ERROR']).toContain(sending_status);
        await expect(dialog.locator('.badge')).toHaveText(sending_status);
        await page.screenshot({ path: 'test-results/issue-314/email_renvoye.png', fullPage: true });
    });
});
