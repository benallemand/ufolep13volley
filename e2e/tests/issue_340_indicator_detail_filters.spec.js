// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Trier, filtrer et chercher dans le détail d'un indicateur (issue #340)
 *
 * Les indicateurs tournent sur la base réelle : aucun n'a un contenu connu
 * d'avance. Le test choisit donc lui-même, par l'API, une tuile dont le détail
 * a au moins trois lignes et une première colonne à plusieurs valeurs, puis
 * calcule les résultats attendus à partir de ce même détail.
 */

/** Voir `issue_308_detail_drawer.spec.js` : cookie posé avant toute navigation. */
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

/**
 * Les indicateurs les moins coûteux d'abord : chaque `mode=detail` exécute une
 * requête d'exploitation, inutile de les passer toutes en revue.
 */
const CANDIDATS = ['Equipes', 'Emails des responsables par compétition', 'Créneaux avec une contrainte horaire forte'];

async function choisirUnIndicateur(request) {
    const liste = await request.get('/ajax/indicators.php?mode=list');
    expect(liste.ok(), 'les indicateurs doivent répondre').toBeTruthy();
    const tous = (await liste.json()).results || [];
    const ordonnes = [
        ...CANDIDATS.map((l) => tous.find((i) => i.fieldLabel === l)).filter(Boolean),
        ...tous.filter((i) => !CANDIDATS.includes(i.fieldLabel)),
    ];
    for (const ind of ordonnes) {
        const detail = await (await request.get(`/ajax/indicators.php?mode=detail&id=${ind.id}`)).json();
        const lignes = detail.details || [];
        if (lignes.length < 3) {
            continue;
        }
        const colonnes = Object.keys(lignes[0]);
        const col = colonnes[0];
        const valeurs = new Set(lignes.map((l) => String(l[col] ?? '').trim()).filter((v) => v.length));
        if (colonnes.length >= 2 && valeurs.size >= 2) {
            return { label: ind.fieldLabel, lignes, colonnes, col };
        }
    }
    return null;
}

function echapper(s) {
    return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

test.describe('Issue #340 — détail des indicateurs', () => {

    test('tri, filtre par colonne, recherche rapide et remise à zéro', async ({ page, request, baseURL }) => {
        test.setTimeout(120000);
        await loginAsAdmin(page, request, baseURL);

        const choix = await choisirUnIndicateur(request);
        test.skip(!choix, 'aucun indicateur exploitable dans cette base');
        const { label, lignes, colonnes, col } = choix;
        const total = lignes.length;

        await page.goto('/admin/index.html#/indicators');
        // Ancré des deux côtés : « Equipes » est aussi la fin de
        // « Inscriptions - Terrains vs Equipes ».
        const tuile = page.getByRole('button', { name: new RegExp('^\\d+\\s+' + echapper(label) + '$') });
        await tuile.click({ timeout: 60000 });

        const modal = page.locator('dialog.modal-open');
        await expect(modal).toBeVisible({ timeout: 15000 });
        const compteur = modal.getByTestId('detail-count');
        await expect(compteur).toHaveText(`${total} / ${total} ligne(s)`);

        // Première colonne du corps, empty-state exclu.
        const premiereColonne = () => modal.locator('tbody tr td:first-child:not([colspan])').allTextContents()
            .then((t) => t.map((v) => v.trim()));

        // --- Tri : le second clic inverse exactement le premier.
        // Par position, pas par nom : une fois trié, le glyphe Font Awesome du
        // caret s'ajoute au nom accessible de l'en-tête.
        const entete = modal.locator('thead tr').first().locator('th').nth(colonnes.indexOf(col));
        await expect(entete).toHaveText(col);
        await entete.click();
        await expect(entete.locator('i.fa-caret-up')).toBeVisible();
        const croissant = await premiereColonne();
        await entete.click();
        await expect(entete.locator('i.fa-caret-down')).toBeVisible();
        const decroissant = await premiereColonne();
        expect(croissant).toHaveLength(total);
        expect(decroissant).toEqual([...croissant].reverse());
        expect(decroissant).not.toEqual(croissant);

        // --- Filtre de colonne : sous-chaîne, insensible à la casse.
        const valeur = String(lignes.find((l) => String(l[col] ?? '').trim())[col]).trim();
        const attendues = lignes.filter((l) => String(l[col] ?? '').toLowerCase().includes(valeur.toLowerCase())).length;
        await modal.getByLabel(`Filtrer la colonne ${col}`, { exact: true }).fill(valeur.toUpperCase());
        await expect(compteur).toHaveText(`${attendues} / ${total} ligne(s)`);
        for (const v of await premiereColonne()) {
            expect(v.toLowerCase()).toContain(valeur.toLowerCase());
        }
        await modal.screenshot({ path: 'test-results/issue-340/detail-filtre-et-tri.png' });

        // --- Effacer les filtres : tout revient, le tri reste.
        await modal.getByRole('button', { name: 'Effacer les filtres' }).click();
        await expect(compteur).toHaveText(`${total} / ${total} ligne(s)`);
        await expect(entete.locator('i.fa-caret-down')).toBeVisible();

        // --- Recherche rapide : plusieurs termes, un seul suffit.
        const recherche = modal.getByLabel('Recherche rapide dans le détail');
        await recherche.fill('zz-aucun-resultat-340');
        await expect(compteur).toHaveText(`0 / ${total} ligne(s)`);
        await expect(modal.getByText('Aucune ligne ne correspond aux filtres.')).toBeVisible();
        await expect(modal.getByRole('button', { name: /Export/ })).toBeDisabled();
        await recherche.fill(`zz-aucun-resultat-340, ${valeur}`);
        const avecLaValeur = lignes.filter((l) => colonnes
            .map((c) => String(l[c] ?? '')).join(' ').toLowerCase()
            .includes(valeur.toLowerCase())).length;
        await expect(compteur).toHaveText(`${avecLaValeur} / ${total} ligne(s)`);

        // --- Rouvrir la tuile repart d'un détail vierge.
        await modal.getByRole('button', { name: 'Fermer' }).click();
        await tuile.click();
        await expect(compteur).toHaveText(`${total} / ${total} ligne(s)`);
        await expect(recherche).toHaveValue('');
        await expect(modal.locator('thead i.fa-caret-up, thead i.fa-caret-down')).toHaveCount(0);
    });
});
