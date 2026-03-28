// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Live Score (issue #217)
 *
 * Scénario :
 *  1. Créer un match dont la date de réception est aujourd'hui
 *  2. Démarrer le live score
 *  3. Vérifier que les boutons +1/-1 sont dans le même bloc que le score (« collés »)
 *  4. Saisir 2 sets complets
 *  5. Enregistrer dans le match via « Renseigner les scores »
 *  6. Vérifier en base que les scores des 2 sets sont bien reportés
 */

let codeMatch = null;

// Créer le match en base avant tous les tests (via APIRequestContext sans navigateur)
test.beforeAll(async ({ request }) => {
    const res = await request.get('/e2e/helpers/test_setup.php');
    expect(res.status(), 'Le script de setup doit répondre 200').toBe(200);

    const body = await res.json();
    expect(body.error, `Erreur setup : ${body.error}`).toBeUndefined();
    expect(body.code_match).toBeTruthy();
    codeMatch = body.code_match;
});

// Nettoyer le match de test après tous les tests
test.afterAll(async ({ request }) => {
    if (codeMatch) {
        await request.get(`/e2e/helpers/test_teardown.php?code_match=${codeMatch}`);
    }
});

test('live score : boutons collés au score + 2 sets reportés dans le match', async ({ page }) => {
    // ------------------------------------------------------------------ //
    // 1. Établir la session admin en naviguant vers le script de setup.
    //    PHP retourne un Set-Cookie PHPSESSID → le browser le stocke.
    // ------------------------------------------------------------------ //
    await page.goto('/e2e/helpers/test_setup.php');
    const setupData = JSON.parse(await page.locator('body').innerText());
    expect(setupData.error, `Erreur setup : ${setupData.error}`).toBeUndefined();
    // Le goto recrée un nouveau match — on met à jour codeMatch
    codeMatch = setupData.code_match;

    // ------------------------------------------------------------------ //
    // 2. Ouvrir la page live en mode scoreur
    // ------------------------------------------------------------------ //
    await page.goto(`/live.php?id_match=${codeMatch}&mode=scorer`);

    // La page doit afficher le badge de compétition (app chargée)
    await expect(page.locator('.badge-info').first()).toBeVisible();

    // ------------------------------------------------------------------ //
    // 3. Démarrer le live score
    // ------------------------------------------------------------------ //
    const startBtn = page.getByRole('button', { name: /Démarrer le Live Score/i });
    await expect(startBtn).toBeVisible();

    const [startResponse] = await Promise.all([
        page.waitForResponse(resp =>
            resp.url().includes('/ajax/live_score.php') && resp.request().method() === 'POST'
        ),
        startBtn.click(),
    ]);
    const startJson = await startResponse.json();
    expect(startJson.success, 'startLiveScore doit réussir').toBe(true);

    // ------------------------------------------------------------------ //
    // 4. Vérifier que les boutons +1/-1 sont « collés » au score
    //    → ils doivent se trouver dans le même .card que l'affichage .text-5xl
    // ------------------------------------------------------------------ //
    const scoreCard = page.locator('.card').filter({ has: page.locator('.text-5xl') }).first();
    await expect(scoreCard).toBeVisible();

    const plusLeftBtn  = scoreCard.locator('button.btn-primary').filter({ hasText: '+1' });
    const plusRightBtn = scoreCard.locator('button.btn-secondary').filter({ hasText: '+1' });

    await expect(plusLeftBtn,  'Bouton +1 gauche dans le bloc score').toBeVisible();
    await expect(plusRightBtn, 'Bouton +1 droite dans le bloc score').toBeVisible();

    // Capture : preuve que les boutons sont collés au score
    await page.screenshot({ path: 'test-results/issue-217/proof-1-boutons-colles-au-score.png', fullPage: false });

    // Les boutons doivent être visibles sans scroller (dans la viewport initiale)
    const scoreBox   = await scoreCard.boundingBox();
    const plusLeftBox = await plusLeftBtn.boundingBox();
    const viewportHeight = page.viewportSize()?.height ?? 800;

    expect(
        plusLeftBox.y + plusLeftBox.height,
        'Le bouton +1 gauche doit être visible dans la viewport (pas de scroll)'
    ).toBeLessThanOrEqual(viewportHeight);

    expect(
        scoreBox.y,
        'Le bloc score ne doit pas dépasser le bas de la viewport'
    ).toBeLessThan(viewportHeight);

    // ------------------------------------------------------------------ //
    // 5. Saisir le set 1 : 3 points dom, 1 point ext → gagné par la gauche
    // ------------------------------------------------------------------ //
    await clickNTimes(plusLeftBtn, 3);
    await clickNTimes(plusRightBtn, 1);

    // L'affichage du score courant doit refléter 3-1
    await expect(scoreCard.locator('.text-5xl')).toContainText('3');
    await expect(scoreCard.locator('.text-5xl')).toContainText('1');

    const nextSetLeftBtn = page.getByRole('button', { name: /Set gagné gauche/i });
    await expect(nextSetLeftBtn).toBeVisible();
    await nextSetLeftBtn.click();

    // ------------------------------------------------------------------ //
    // 6. Vérifier que "Renseigner les scores du match" apparaît dès 1 set joué
    //    (correction de l'issue #217 : was hidden until 3 sets)
    // ------------------------------------------------------------------ //
    const saveToMatchBtn = page.getByRole('button', { name: /Renseigner les scores du match/i });
    await expect(saveToMatchBtn, '"Renseigner" visible après 1 set').toBeVisible();

    // ------------------------------------------------------------------ //
    // 7. Saisir le set 2 : 2 points dom, 4 points ext → gagné par la droite
    // ------------------------------------------------------------------ //
    await clickNTimes(plusLeftBtn, 2);
    await clickNTimes(plusRightBtn, 4);

    const nextSetRightBtn = page.getByRole('button', { name: /Set gagné droite/i });
    await nextSetRightBtn.click();

    // ------------------------------------------------------------------ //
    // 8. Enregistrer dans le match
    //    saveToMatch() : flush via upsert (si unsaved) + save_to_match
    // ------------------------------------------------------------------ //

    // Accepter automatiquement le confirm() natif du navigateur
    page.once('dialog', dialog => dialog.accept());

    // On attend les 2 requêtes AJAX : upsert (flush) + save_to_match
    const ajaxDone = page.waitForResponse(
        resp => resp.url().includes('/ajax/live_score.php') && resp.status() === 200,
        { timeout: 15000 }
    );
    await saveToMatchBtn.click();
    await ajaxDone;

    // Le toast de confirmation doit apparaître
    await expect(
        page.getByText('Scores enregistrés dans le match'),
        'Toast de confirmation attendu après saveToMatch'
    ).toBeVisible({ timeout: 10000 });

    // ------------------------------------------------------------------ //
    // 9. Vérifier en base que les 2 sets sont bien reportés dans `matches`
    // ------------------------------------------------------------------ //
    const verifyRes = await page.request.get(
        `/e2e/helpers/test_verify.php?code_match=${codeMatch}`
    );
    expect(verifyRes.status()).toBe(200);

    const scores = await verifyRes.json();
    expect(String(scores.set_1_dom), 'set_1_dom = 3').toBe('3');
    expect(String(scores.set_1_ext), 'set_1_ext = 1').toBe('1');
    expect(String(scores.set_2_dom), 'set_2_dom = 2').toBe('2');
    expect(String(scores.set_2_ext), 'set_2_ext = 4').toBe('4');

    // Sets 3-5 doivent rester à 0 / null
    const set3dom = Number(scores.set_3_dom ?? 0);
    expect(set3dom, 'set_3_dom doit être 0 (non renseigné)').toBe(0);

    // Capture : preuve que les 2 sets sont reportés dans le match
    await page.screenshot({ path: 'test-results/issue-217/proof-2-sets-reportes-dans-match.png', fullPage: true });
});

// ------------------------------------------------------------------ //
// Utilitaire : cliquer N fois sur un locator
// ------------------------------------------------------------------ //
async function clickNTimes(locator, n) {
    for (let i = 0; i < n; i++) {
        await locator.click();
    }
}
