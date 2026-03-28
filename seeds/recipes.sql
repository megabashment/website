-- =====================================================================
-- SEED: 50 Deutsche Familienrezepte
-- =====================================================================
--
-- Run via phpMyAdmin AFTER the admin user exists.
-- INSERT IGNORE skips already-existing client_ids — safe to re-run.
--
-- Konsistente Zutatenschreibweise:
--   Zwiebel | Knoblauchzehe | Möhre | Sellerie | Olivenöl | Öl
--   Sahne | Milch | Mehl | Butter | Ei / Eier | Parmesan | Gouda | Mozzarella | Emmentaler
--   Dosentomaten | Tomatenmark | Salz | schwarzer Pfeffer | Gemüsebrühe | Rinderbrühe
--   Hähnchenbrustfilet | Rinderhackfleisch | Schweinehackfleisch
--   TK = tiefgekühlt (z.B. Spinat TK, Erbsen TK)
--

SET @admin_id = (SELECT id FROM users WHERE role = 'admin' LIMIT 1);

INSERT IGNORE INTO `recipes` (`client_id`, `name`, `ingredients`, `created_by`) VALUES

-- ─── Pasta & Nudeln ────────────────────────────────────────────────

('seed-001', 'Spaghetti Bolognese',
'Spaghetti (400g)\nRinderhackfleisch (500g)\nZwiebel (1 Stück)\nKnoblauchzehe (2 Stück)\nMöhre (1 Stück)\nDosentomaten (400g)\nTomatenmark (2 EL)\nOlivenöl (2 EL)\nParmesan (50g)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-002', 'Spaghetti Carbonara',
'Spaghetti (400g)\nSpeck (150g)\nParmesan (80g)\nEi (2 Stück)\nEigelb (2 Stück)\nKnoblauchzehe (1 Stück)\nschwarzer Pfeffer (nach Geschmack)\nSalz (nach Geschmack)',
@admin_id),

('seed-003', 'Nudeln mit Tomatensauce',
'Nudeln (400g)\nDosentomaten (800g)\nZwiebel (1 Stück)\nKnoblauchzehe (2 Stück)\nOlivenöl (3 EL)\nBasilikum (1 Bund)\nZucker (1 TL)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-004', 'Lasagne Bolognese',
'Lasagneplatten (250g)\nRinderhackfleisch (500g)\nDosentomaten (400g)\nTomatenmark (2 EL)\nZwiebel (1 Stück)\nKnoblauchzehe (2 Stück)\nOlivenöl (2 EL)\nButter (50g)\nMehl (50g)\nMilch (500ml)\nParmesan (100g)\nMozzarella (125g)\nMuskatnuss (1 Prise)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-005', 'Tortellini in Sahnesauce',
'Tortellini (500g)\nSahne (200ml)\nParmesan (60g)\nKnoblauchzehe (2 Stück)\nButter (20g)\nSpinat TK (100g)\nMuskatnuss (1 Prise)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-006', 'Pasta mit Pesto',
'Pasta (400g)\nBasilikum (2 Bund)\nParmesan (60g)\nPinienkerne (40g)\nKnoblauchzehe (2 Stück)\nOlivenöl (100ml)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-007', 'Spaghetti Aglio e Olio',
'Spaghetti (400g)\nKnoblauchzehe (4 Stück)\nOlivenöl (6 EL)\nChilischote (1 Stück)\nPetersilie (½ Bund)\nParmesan (40g)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-008', 'Thunfischpasta',
'Nudeln (400g)\nThunfisch in Öl (2 Dosen à 185g)\nDosentomaten (400g)\nZwiebel (1 Stück)\nKnoblauchzehe (2 Stück)\nKapern (2 EL)\nOlivenöl (2 EL)\nPetersilie (½ Bund)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

-- ─── Fleischgerichte ────────────────────────────────────────────────

('seed-009', 'Schnitzel mit Bratkartoffeln',
'Schweineschnitzel (4 Stück à 150g)\nKartoffel (800g)\nMehl (4 EL)\nEi (2 Stück)\nSemmelbrösel (100g)\nButter (30g)\nÖl (4 EL)\nZitrone (1 Stück)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-010', 'Hähnchen in Sahnesauce',
'Hähnchenbrustfilet (600g)\nSahne (200ml)\nChampignons (250g)\nZwiebel (1 Stück)\nKnoblauchzehe (2 Stück)\nButter (20g)\nOlivenöl (1 EL)\nPetersilie (½ Bund)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-011', 'Rindergulasch',
'Rindfleisch (800g)\nZwiebel (3 Stück)\nPaprika rot (2 Stück)\nTomatenmark (2 EL)\nRinderbrühe (500ml)\nÖl (3 EL)\nPaprikapulver (2 EL)\nLorbeerblatt (2 Stück)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-012', 'Schweinebraten',
'Schweinebraten (1,5 kg)\nZwiebel (2 Stück)\nMöhre (2 Stück)\nSellerie (150g)\nKnoblauchzehe (3 Stück)\nRinderbrühe (300ml)\nÖl (2 EL)\nLorbeerblatt (2 Stück)\nKümmel (1 TL)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-013', 'Hackbraten',
'Rinderhackfleisch (600g)\nSchweinehackfleisch (400g)\nEi (2 Stück)\nZwiebel (1 Stück)\nSemmelbrösel (50g)\nSenf (1 EL)\nPetersilie (½ Bund)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-014', 'Königsberger Klopse',
'Rinderhackfleisch (500g)\nSchweinehackfleisch (300g)\nEi (1 Stück)\nZwiebel (1 Stück)\nSemmelbrösel (40g)\nSardellen (4 Stück)\nRinderbrühe (800ml)\nSahne (150ml)\nButter (30g)\nMehl (2 EL)\nKapern (3 EL)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-015', 'Hähnchenschenkel aus dem Ofen',
'Hähnchenschenkel (4 Stück)\nKnoblauchzehe (3 Stück)\nOlivenöl (3 EL)\nRosmarin (2 Zweige)\nThymian (2 Zweige)\nZitrone (1 Stück)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-016', 'Bratwurst mit Sauerkraut',
'Bratwurst (4 Stück)\nSauerkraut (500g)\nKartoffel (600g)\nZwiebel (1 Stück)\nÖl (2 EL)\nLorbeerblatt (2 Stück)\nKümmel (1 TL)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-017', 'Rinderbraten mit Soße',
'Rinderbraten (1,2 kg)\nZwiebel (2 Stück)\nMöhre (2 Stück)\nSellerie (150g)\nRotwein (200ml)\nRinderbrühe (400ml)\nTomatenmark (1 EL)\nÖl (3 EL)\nLorbeerblatt (2 Stück)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-018', 'Sauerbraten',
'Rindfleisch (1,2 kg)\nRotweinessig (250ml)\nRotwein (250ml)\nZwiebel (2 Stück)\nMöhre (2 Stück)\nLorbeerblatt (3 Stück)\nPfefferkörner (10 Stück)\nNelken (3 Stück)\nÖl (2 EL)\nZucker (1 EL)\nSalz (nach Geschmack)',
@admin_id),

('seed-019', 'Zucchini-Hackfleisch-Pfanne',
'Rinderhackfleisch (400g)\nZucchini (2 Stück)\nDosentomaten (400g)\nZwiebel (1 Stück)\nKnoblauchzehe (2 Stück)\nOlivenöl (2 EL)\nPaprikapulver (1 TL)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-020', 'Chili con Carne',
'Rinderhackfleisch (500g)\nKidneybohnen (2 Dosen à 400g)\nDosentomaten (400g)\nMais (1 Dose à 285g)\nZwiebel (2 Stück)\nKnoblauchzehe (3 Stück)\nChilischote (1 Stück)\nTomatenmark (2 EL)\nOlivenöl (2 EL)\nPaprikapulver (2 TL)\nKreuzkümmel (1 TL)\nSalz (nach Geschmack)',
@admin_id),

-- ─── Suppen & Eintöpfe ─────────────────────────────────────────────

('seed-021', 'Kartoffelsuppe',
'Kartoffel (800g)\nMöhre (2 Stück)\nSellerie (150g)\nZwiebel (1 Stück)\nGemüsebrühe (1 L)\nSahne (100ml)\nSpeck (100g)\nButter (20g)\nPetersilie (½ Bund)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-022', 'Linsensuppe',
'Rote Linsen (300g)\nMöhre (2 Stück)\nSellerie (150g)\nZwiebel (1 Stück)\nKnoblauchzehe (2 Stück)\nGemüsebrühe (1,2 L)\nOlivenöl (2 EL)\nPaprikapulver (1 TL)\nKreuzkümmel (1 TL)\nZitrone (½ Stück)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-023', 'Erbsensuppe mit Kasseler',
'Getrocknete Erbsen (400g)\nKasseler (300g)\nMöhre (2 Stück)\nSellerie (150g)\nZwiebel (1 Stück)\nSpeck (80g)\nGemüsebrühe (1,5 L)\nBohnenkraut (1 TL)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-024', 'Minestrone',
'Zucchini (1 Stück)\nMöhre (2 Stück)\nSellerie (150g)\nZwiebel (1 Stück)\nKnoblauchzehe (2 Stück)\nDosentomaten (400g)\nKidneybohnen (1 Dose à 400g)\nGemüsebrühe (1 L)\nNudeln (100g)\nOlivenöl (3 EL)\nParmesan (50g)\nBasilikum (½ Bund)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-025', 'Tomatensuppe',
'Dosentomaten (800g)\nZwiebel (1 Stück)\nKnoblauchzehe (2 Stück)\nGemüsebrühe (500ml)\nSahne (100ml)\nOlivenöl (2 EL)\nBasilikum (½ Bund)\nZucker (1 TL)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-026', 'Hühnersuppe',
'Hähnchenschenkel (2 Stück)\nMöhre (3 Stück)\nSellerie (200g)\nZwiebel (1 Stück)\nLauch (1 Stück)\nSuppennudeln (100g)\nPetersilie (½ Bund)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-027', 'Kürbissuppe',
'Hokkaido-Kürbis (1 kg)\nZwiebel (1 Stück)\nKnoblauchzehe (2 Stück)\nIngwer (20g)\nGemüsebrühe (800ml)\nSahne (100ml)\nOlivenöl (2 EL)\nKürbiskernöl (2 EL)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-028', 'Möhrensuppe mit Ingwer',
'Möhre (600g)\nZwiebel (1 Stück)\nKnoblauchzehe (1 Stück)\nIngwer (20g)\nGemüsebrühe (800ml)\nSahne (100ml)\nOrangensaft (100ml)\nOlivenöl (2 EL)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

-- ─── Vegetarisch ────────────────────────────────────────────────────

('seed-029', 'Gemüsecurry mit Reis',
'Süßkartoffel (400g)\nKichererbsen (1 Dose à 400g)\nDosentomaten (400g)\nKokosmilch (400ml)\nZwiebel (1 Stück)\nKnoblauchzehe (2 Stück)\nIngwer (20g)\nCurrypulver (2 EL)\nOlivenöl (2 EL)\nBasmati-Reis (300g)\nSalz (nach Geschmack)',
@admin_id),

('seed-030', 'Ratatouille',
'Zucchini (2 Stück)\nAubergine (1 Stück)\nPaprika rot (1 Stück)\nPaprika gelb (1 Stück)\nDosentomaten (400g)\nZwiebel (1 Stück)\nKnoblauchzehe (3 Stück)\nOlivenöl (4 EL)\nThymian (2 Zweige)\nRosmarin (1 Zweig)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-031', 'Käsespätzle',
'Spätzle (500g)\nEmmentaler (250g)\nZwiebel (2 Stück)\nButter (40g)\nSchnittlauch (½ Bund)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-032', 'Spinat-Ricotta-Cannelloni',
'Cannelloni (250g)\nRicotta (500g)\nSpinat TK (400g)\nParmesan (80g)\nDosentomaten (800g)\nMozzarella (125g)\nKnoblauchzehe (2 Stück)\nEi (1 Stück)\nOlivenöl (2 EL)\nMuskatnuss (1 Prise)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-033', 'Vegetarische Lasagne',
'Lasagneplatten (250g)\nZucchini (1 Stück)\nAubergine (1 Stück)\nPaprika rot (1 Stück)\nDosentomaten (400g)\nMozzarella (125g)\nParmesan (80g)\nButter (50g)\nMehl (50g)\nMilch (500ml)\nOlivenöl (2 EL)\nMuskatnuss (1 Prise)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-034', 'Kartoffelgratin',
'Kartoffel (1 kg)\nSahne (300ml)\nKnoblauchzehe (2 Stück)\nEmmentaler (150g)\nButter (20g)\nMuskatnuss (1 Prise)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-035', 'Gemüseauflauf',
'Zucchini (2 Stück)\nPaprika rot (1 Stück)\nPaprika gelb (1 Stück)\nTomate (3 Stück)\nZwiebel (1 Stück)\nGouda (150g)\nSahne (200ml)\nEi (2 Stück)\nOlivenöl (2 EL)\nKräuter der Provence (1 TL)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-036', 'Spinatauflauf mit Feta',
'Spinat TK (600g)\nFeta (200g)\nEi (3 Stück)\nSahne (150ml)\nZwiebel (1 Stück)\nKnoblauchzehe (2 Stück)\nButter (20g)\nMuskatnuss (1 Prise)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-037', 'Quiche Lorraine',
'Mürbeteig (1 Packung)\nSpeck (150g)\nSahne (200ml)\nEi (3 Stück)\nEmmentaler (100g)\nZwiebel (1 Stück)\nMuskatnuss (1 Prise)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

-- ─── Fisch ──────────────────────────────────────────────────────────

('seed-038', 'Lachs mit Ofengemüse',
'Lachsfilet (4 Stück à 150g)\nZucchini (1 Stück)\nPaprika rot (1 Stück)\nMöhre (2 Stück)\nKnoblauchzehe (2 Stück)\nOlivenöl (3 EL)\nZitrone (1 Stück)\nDill (½ Bund)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-039', 'Fischstäbchen mit Kartoffelpüree',
'Fischstäbchen (16 Stück)\nKartoffel (800g)\nMilch (100ml)\nButter (40g)\nMuskatnuss (1 Prise)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-040', 'Forelle mit Kräuterbutter',
'Forelle (2 Stück)\nButter (80g)\nPetersilie (½ Bund)\nSchnittlauch (½ Bund)\nKnoblauchzehe (1 Stück)\nZitrone (1 Stück)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

-- ─── Reis & Getreide ────────────────────────────────────────────────

('seed-041', 'Risotto mit Champignons',
'Risottoreis (300g)\nChampignons (400g)\nZwiebel (1 Stück)\nKnoblauchzehe (2 Stück)\nWeißwein (150ml)\nGemüsebrühe (1 L)\nParmesan (80g)\nButter (40g)\nOlivenöl (2 EL)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-042', 'Gebratener Reis',
'Reis (300g)\nEi (3 Stück)\nMöhre (2 Stück)\nErbsen TK (150g)\nFrühlingszwiebel (4 Stück)\nKnoblauchzehe (2 Stück)\nIngwer (15g)\nSojasoße (3 EL)\nSesamöl (1 EL)\nÖl (2 EL)',
@admin_id),

('seed-043', 'Hähnchen-Reis-Auflauf',
'Hähnchenbrustfilet (600g)\nReis (250g)\nPaprika rot (1 Stück)\nPaprika gelb (1 Stück)\nZwiebel (1 Stück)\nHühnerbrühe (600ml)\nSahne (150ml)\nOlivenöl (2 EL)\nPaprikapulver (1 TL)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-044', 'Gefüllte Paprika mit Hackfleisch',
'Paprika rot (4 Stück)\nRinderhackfleisch (400g)\nReis (150g)\nDosentomaten (400g)\nZwiebel (1 Stück)\nKnoblauchzehe (2 Stück)\nOlivenöl (2 EL)\nPaprikapulver (1 TL)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

-- ─── Pfannengerichte & Klassiker ────────────────────────────────────

('seed-045', 'Bratkartoffeln mit Speck und Ei',
'Kartoffel (800g)\nSpeck (150g)\nEi (4 Stück)\nZwiebel (1 Stück)\nÖl (3 EL)\nSchnittlauch (½ Bund)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-046', 'Pfannkuchen',
'Mehl (250g)\nEi (3 Stück)\nMilch (500ml)\nButter (30g)\nZucker (1 EL)\nSalz (1 Prise)',
@admin_id),

('seed-047', 'Omelett mit Champignons',
'Ei (6 Stück)\nChampignons (200g)\nGouda (80g)\nButter (20g)\nMilch (4 EL)\nSchnittlauch (¼ Bund)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-048', 'Flammkuchen',
'Flammkuchenteig (2 Stück)\nCrème fraîche (200g)\nSpeck (120g)\nZwiebel (2 Stück)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-049', 'Pizza Margherita',
'Pizzateig (2 Stück)\nDosentomaten (400g)\nMozzarella (250g)\nBasilikum (½ Bund)\nOlivenöl (2 EL)\nKnoblauchzehe (1 Stück)\nSalz (nach Geschmack)\nschwarzer Pfeffer (nach Geschmack)',
@admin_id),

('seed-050', 'Rotkohl mit Kasseler',
'Kasseler (600g)\nRotkohl (1 kg)\nApfel (2 Stück)\nZwiebel (1 Stück)\nRotweinessig (3 EL)\nZucker (2 EL)\nLorbeerblatt (2 Stück)\nNelken (3 Stück)\nÖl (2 EL)\nKartoffel (600g)\nSalz (nach Geschmack)',
@admin_id);
