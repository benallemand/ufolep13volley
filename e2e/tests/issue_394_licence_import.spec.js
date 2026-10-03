// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Import groupé des licences (issue #394)
 *
 * Le serveur est simulé (`page.route`) : la lecture des PDF et l'enregistrement
 * sont couverts par `LicenceImportTest`, et de vraies licences importées ici
 * modifieraient des joueurs de la base. On vérifie l'écran :
 *   - plusieurs fichiers d'un coup, les non-PDF ignorés ;
 *   - un envoi par fichier, une progression ;
 *   - le compte rendu par licence, et le nouvel essai des fichiers en erreur.
 */

/** Même principe que issue_308 : cookie posé sur `baseURL` avant de naviguer. */
async function loginAsAdmin(page, request, baseURL) {
    const res = await request.get('/e2e/helpers/admin_session.php');
    expect(res.status(), 'Le helper de session admin doit répondre 200').toBe(200);
    const { session_id } = await res.json();
    expect(session_id).toBeTruthy();
    await page.context().addCookies([{
        name: 'PHPSESSID',
        value: session_id,
        url: baseURL || 'http://localhost',
    }]);
}

const pdf = (name) => ({ name, mimeType: 'application/pdf', buffer: Buffer.from('%PDF-1.4 ' + name) });

/** Réponses simulées, par nom de fichier. Le fichier C échoue la première fois. */
function simulateServer(page) {
    const calls = [];
    let failuresLeft = 1;
    return page.route('**/rest/action.php/player/update_from_licence_file', async (route) => {
        const body = route.request().postDataBuffer()?.toString('latin1') || '';
        const name = (body.match(/filename="([^"]+)"/) || [])[1];
        calls.push(name);
        const json = (report, message) => route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ success: true, message, report }),
        });
        if (name === 'licence-a.pdf') {
            return json([{ joueur: 'DUPONT Anne', status: 'created', photo: false }], '1 licence(s) importée(s)');
        }
        if (name === 'licence-b.pdf') {
            return json([
                { joueur: 'MARTIN Paul', status: 'updated', photo: true },
                { joueur: 'DURAND Luc', status: 'rejected', message: "licence du club n° 13000000, qui n'est pas le vôtre" },
            ], '1 licence(s) importée(s), 1 écartée(s)');
        }
        if (failuresLeft > 0) {
            failuresLeft -= 1;
            return route.fulfill({
                status: 500, contentType: 'application/json',
                body: JSON.stringify({ success: false, message: 'Erreur serveur simulée' }),
            });
        }
        return json([{ joueur: 'PETIT Léa', status: 'updated', photo: true }], '1 licence(s) importée(s)');
    }).then(() => calls);
}

test.describe('Issue #394 — import groupé des licences', () => {

    test('plusieurs PDF, progression, compte rendu et nouvel essai', async ({ page, request, baseURL }) => {
        test.setTimeout(90000);
        await loginAsAdmin(page, request, baseURL);
        const calls = await simulateServer(page);

        await page.goto('/admin/index.html#/players');
        await page.getByRole('button', { name: /Importer des licences/ }).click({ timeout: 30000 });
        const modal = page.getByTestId('licence-import');
        await expect(modal).toBeVisible();

        await modal.getByTestId('licence-import-input').setInputFiles([
            pdf('licence-a.pdf'),
            pdf('licence-b.pdf'),
            pdf('licence-c.pdf'),
            { name: 'notes.txt', mimeType: 'text/plain', buffer: Buffer.from('pas un pdf') },
        ]);
        await expect(modal.getByTestId('licence-import-ignored')).toContainText('1 fichier(s) ignoré(s)');
        const submit = modal.getByTestId('licence-import-submit');
        await expect(submit).toContainText('Importer 3 fichier(s)');

        await submit.click();
        await expect(modal.getByTestId('licence-import-progress')).toHaveText('3 / 3');
        const summary = modal.getByTestId('licence-import-summary');
        await expect(summary).toContainText('1 mise(s) à jour, 1 création(s), 1 licence(s) écartée(s), 1 fichier(s) en erreur');
        await expect(summary).toContainText("1 sans photo : à ajouter avant d'inscrire le joueur sur une feuille de match");
        // Un envoi par fichier.
        expect([...calls].sort()).toEqual(['licence-a.pdf', 'licence-b.pdf', 'licence-c.pdf']);
        await expect(modal).toContainText("DURAND Luc : écartée, licence du club n° 13000000, qui n'est pas le vôtre");
        await expect(modal).toContainText('DUPONT Anne : créé, sans photo');
        await expect(modal.getByTestId('licence-import-file-error')).toContainText('Erreur serveur simulée');
        await page.screenshot({ path: 'test-results/issue-394/compte-rendu.png', fullPage: true });

        // Seul le fichier en erreur repart.
        await modal.getByRole('button', { name: /Réessayer les fichiers en erreur/ }).click();
        await expect(summary).toContainText('2 mise(s) à jour, 1 création(s), 1 licence(s) écartée(s).');
        await expect(modal.getByTestId('licence-import-file-error')).toHaveCount(0);
        expect(calls.filter((c) => c === 'licence-c.pdf')).toHaveLength(2);
        expect(calls).toHaveLength(4);

        await modal.getByRole('button', { name: 'Fermer' }).click();
        await expect(modal).toHaveCount(0);
    });

    test('sur téléphone, le bouton de sélection est là, sans invite au glisser-déposer', async ({ page, request, baseURL }) => {
        test.setTimeout(60000);
        await page.setViewportSize({ width: 375, height: 812 });
        await loginAsAdmin(page, request, baseURL);
        await page.goto('/admin/index.html#/players');
        await page.getByRole('button', { name: /Importer des licences/ }).click({ timeout: 30000 });
        const modal = page.getByTestId('licence-import');
        await expect(modal.getByText('Choisir des fichiers')).toBeVisible();
        await expect(modal.getByText('Glissez vos fichiers PDF ici')).toBeHidden();
        await expect(modal.getByTestId('licence-import-input')).toHaveAttribute('multiple', '');
        // Laisse l'animation d'ouverture de daisyUI finir avant la capture.
        await expect(modal.locator('.modal-box')).toHaveCSS('opacity', '1');
        await page.screenshot({ path: 'test-results/issue-394/mobile.png' });
    });
});
