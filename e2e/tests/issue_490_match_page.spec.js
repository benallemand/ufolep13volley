// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Page de match unique (issue #490), sur un écran de smartphone.
 *
 * Les deux responsables, dans le même navigateur (le helper bascule la
 * session) : présents communs → fiche équipes (la première signature fige les
 * présents, et s'annule) → score saisi par l'autre équipe → feuille de match
 * (la première signature fige le score) → sondage. Puis le conflit : une
 * liste de présents périmée ne peut pas effacer la saisie de l'autre équipe.
 *
 * Helper : e2e/helpers/match_flow_setup.php (deux équipes de 4 joueurs avec photo).
 */
test.use({ viewport: { width: 390, height: 844 } });

const HELPER = '/e2e/helpers/match_flow_setup.php';
const shot = (page, name) => page.screenshot({ path: `test-results/issue-490/${name}.png`, fullPage: true });

test.describe('Issue #490 — page de match unique', () => {
    let setup = null;

    test.beforeEach(async ({ page }) => {
        await page.goto(HELPER);
        setup = JSON.parse(await page.locator('body').innerText());
        if (setup.error) throw new Error(`Setup failed: ${setup.error}`);
    });

    test.afterEach(async ({ page }) => {
        await page.request.get(`${HELPER}?action=teardown`);
        setup = null;
    });

    const as = async (page, side) => {
        const r = await page.request.get(`${HELPER}?action=as&side=${side}`);
        expect(r.ok(), await r.text()).toBe(true);
    };
    const openPage = async (page) => {
        await page.goto(`/match.html?id_match=${setup.id_match}`);
        await expect(page.getByTestId('match-steps')).toBeVisible({ timeout: 15000 });
    };
    const toggleStep = (page, key) => page.getByTestId(`step-${key}`).getByRole('button').first().click();
    const todo = (page) => page.getByTestId('next-step');
    /** Clique sur un bouton et attend la réponse de l'action REST, en succès. */
    const act = async (page, action, click) => {
        const response = page.waitForResponse(r => r.url().includes(`/matchmgr/${action}`) && r.request().method() === 'POST');
        await click();
        const r = await response;
        expect(r.ok(), await r.text()).toBe(true);
        // la page a fini de se recharger (l'overlay, pas la liste des joueurs)
        await expect(page.locator('div.fixed .loading-spinner')).toHaveCount(0, { timeout: 20000 });
    };
    const confirmSign = async (page, action, label) => {
        await page.getByRole('button', { name: label }).click();
        await expect(page.getByTestId('confirm-sign')).toBeVisible();
        await act(page, action, () => page.getByRole('button', { name: 'Je signe' }).click());
    };

    test('parcours complet des deux équipes, annulation comprise', async ({ page }) => {
        // une vingtaine d'enregistrements, chacun relisant le match (matchs_view)
        test.setTimeout(240000);
        const { teams, players } = setup;

        // 1. Équipe à domicile : la liste des présents est commune aux deux équipes
        await openPage(page);
        await expect(todo(page)).toContainText('Il vous reste à renseigner les présents.');
        await expect(page.getByTestId('step-presents')).toHaveAttribute('data-state', 'current');
        await expect(page.getByTestId('step-score')).toHaveAttribute('data-state', 'locked');
        for (const side of ['dom', 'ext']) {
            for (const player of players[side].slice(0, 3)) {
                await page.getByRole('button', { name: `Ajouter ${player.name}` }).click();
            }
        }
        await shot(page, '1-presents-des-deux-equipes');
        await act(page, 'manage_match_players', () => page.getByTestId('save-players').click());

        // 2. Signer la fiche équipes : confirmation dans la page, puis présents figés
        await expect(todo(page)).toContainText('Il vous reste à signer la fiche équipes.');
        await page.getByRole('button', { name: 'Signer la fiche équipes' }).click();
        await expect(page.getByTestId('confirm-sign')).toBeVisible();
        await shot(page, '2-confirmation-de-signature');
        await act(page, 'sign_team_sheet', () => page.getByRole('button', { name: 'Je signe' }).click());
        await expect(todo(page)).toContainText('Après le match : saisissez le score.');
        await expect(page.getByTestId('step-score')).toHaveAttribute('data-state', 'current');
        await toggleStep(page, 'presents');
        await expect(page.getByTestId('players-frozen')).toContainText(`signés par ${teams.dom}`);
        await shot(page, '3-presents-figes-score-debloque');

        // 3. Annuler sa signature : sans autre signature, les présents redeviennent modifiables
        await toggleStep(page, 'fiche');
        await page.getByRole('button', { name: 'Annuler ma signature' }).click();
        await expect(page.getByTestId('confirm-unsign')).toContainText('redeviendront modifiables');
        await shot(page, '4-annulation-de-signature');
        await act(page, 'unsign_team_sheet', () => page.getByRole('button', { name: "Confirmer l'annulation" }).click());
        await expect(todo(page)).toContainText('Il vous reste à signer la fiche équipes.');
        await confirmSign(page, 'sign_team_sheet', 'Signer la fiche équipes');

        // 4. Équipe extérieure : elle confirme les présents, et peut déjà saisir le score
        await as(page, 'ext');
        await openPage(page);
        await expect(todo(page)).toContainText('Il vous reste à confirmer les présents.');
        await shot(page, '5-exterieur-confirme-les-presents');
        await toggleStep(page, 'score');
        const sets = [[25, 20], [25, 18], [22, 25], [25, 23]];
        for (const [i, [dom, ext]] of sets.entries()) {
            await page.getByLabel(`Set ${i + 1} ${teams.dom}`).fill(String(dom));
            await page.getByLabel(`Set ${i + 1} ${teams.ext}`).fill(String(ext));
        }
        await expect(page.getByTestId('sets-dom')).toHaveText('3');
        await shot(page, '6-saisie-du-score');
        await act(page, 'save_match', () => page.getByTestId('save-score').click());

        // 5. L'extérieur signe la feuille de match : le score est figé
        await expect(todo(page)).toContainText('Il vous reste à signer la feuille de match.');
        await confirmSign(page, 'sign_match_sheet', 'Signer la feuille de match');
        await toggleStep(page, 'score');
        await expect(page.getByTestId('score-locked')).toContainText('Score figé');
        await expect(page.getByLabel(`Set 1 ${teams.dom}`)).toBeDisabled();
        await shot(page, '7-score-fige-par-la-feuille');

        // 6. Équipe à domicile : sa fiche ne s'annule plus, elle signe la feuille puis le sondage
        await as(page, 'dom');
        await openPage(page);
        await expect(todo(page)).toContainText('Il vous reste à signer la feuille de match.');
        await toggleStep(page, 'fiche');
        await expect(page.getByTestId('signatures-team')).toContainText('Annulation impossible');
        await toggleStep(page, 'feuille');
        await confirmSign(page, 'sign_match_sheet', 'Signer la feuille de match');
        await expect(todo(page)).toContainText('Il vous reste à remplir le sondage fair-play.');
        await page.getByRole('radiogroup', { name: 'Ponctualité' }).getByRole('radio', { name: '+', exact: true }).check();
        await page.getByRole('radiogroup', { name: 'Global' }).getByRole('radio', { name: '++', exact: true }).check();
        await shot(page, '8-sondage');
        await act(page, 'save_survey', () => page.getByTestId('save-survey').click());
        await expect(todo(page)).toContainText('Tout est fait pour ce match, merci !');
        await shot(page, '9-tout-est-fait');
    });

    test("une liste périmée n'efface pas la saisie de l'autre équipe", async ({ page }) => {
        const { teams, players } = setup;
        await openPage(page);
        for (const side of ['dom', 'ext']) {
            for (const player of players[side].slice(0, 3)) {
                await page.getByRole('button', { name: `Ajouter ${player.name}` }).click();
            }
        }
        await act(page, 'manage_match_players', () => page.getByTestId('save-players').click());

        // pendant que la page est ouverte, l'autre équipe ajoute son 4e joueur
        const touch = await page.request.get(`${HELPER}?action=touch`);
        expect(touch.ok(), await touch.text()).toBe(true);

        await toggleStep(page, 'presents');
        await page.getByRole('button', { name: `Retirer ${players.dom[2].name}` }).click();
        const refus = page.waitForResponse(r => r.url().includes('/matchmgr/manage_match_players'));
        await page.getByTestId('save-players').click();
        expect((await refus).status()).toBe(409);
        await expect(page.locator('.toastify').filter({ hasText: 'modifié entre-temps' }).first()).toBeVisible();

        // la page s'est rechargée : la saisie de l'autre équipe est là, la nôtre n'a rien écrasé
        await expect(page.getByTestId('roster-ext')).toContainText(`${teams.ext} · 4 présent(s)`, { timeout: 10000 });
        await expect(page.getByTestId('roster-dom')).toContainText(`${teams.dom} · 3 présent(s)`);
        await shot(page, '10-conflit-recharge');
    });

    test('les anciennes adresses mènent à la bonne étape', async ({ page }) => {
        await page.goto(`/survey.html?id_match=${setup.id_match}`);
        await expect(page).toHaveURL(new RegExp(`match\\.html\\?id_match=${setup.id_match}#sondage`), { timeout: 10000 });
        await page.goto(`/team_sheets.html?id_match=${setup.id_match}`);
        await expect(page).toHaveURL(new RegExp(`match\\.html\\?id_match=${setup.id_match}#presents`), { timeout: 10000 });
        await expect(page.getByTestId('step-presents')).toBeVisible({ timeout: 15000 });
    });
});
