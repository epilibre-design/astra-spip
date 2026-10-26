# Navigation et Menus Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Navigation sans JavaScript utilisable et menus libres intégrés au builder.
**Architecture:** Repli CSS limité à noscript ; source Menus et adaptation de son HTML sans réimplémenter les entrées.
**Tech Stack:** SPIP 4.4, Menus 3.1.2, PHP, PHPUnit, Playwright.
**Spec:** docs/superpowers/specs/2026-10-26-navigation-menus-design.md

## Global Constraints

SPIP `[4.4.0;4.4.*]`. Menus obligatoire. Ne pas ajouter ni exécuter de tests de migration. Conserver les autres sources comme fonctions disponibles.

## Review Focus

Menus supprimés ; sous-menus et liens externes ; titres accentués ; classes personnalisées ; état actif et commandes accessibles.

### Task 1 — Repli sans JavaScript

- [x] Ajouter un test navigateur reproduisant le recouvrement du contenu et la disparition à 922 px ; constater son échec.
- [x] Ajouter une feuille CSS uniquement dans noscript, forçant le flux normal et l’en-tête mobile jusqu’à 922 px, avec liens et sous-liens visibles.
- [x] Vérifier avec et sans JS à 320,390,768,921,922,923,1280 px et en mode transparent.

### Task 2 — Source Menus obligatoire

- [x] Ajouter tests d’aller-retour `menus:<id>` et adaptation de rendu (arbre, actif, URL, accents, classes et widget) ; constater l’échec.
- [x] Étendre `inc/astra_hfb_saisie.php`, `astra_hfb_sources_menu`, les inclusions `menu-items` et `menu-liens-sans-js` ; ajouter `inc/astra_menus.php` pour adapter le HTML natif.
- [x] Déclarer Menus obligatoire et la cible SPIP 4.4 ; installer ces dépendances dans le script de test avant activation d’Astra.
- [x] Vérifier les vrais squelettes Menus, les widgets, le pied de page et le navigateur.

### Task 3 — Qualification et revue

- [x] Exécuter la suite unitaire et d’intégration, lint et vérifications navigateur pertinentes.
- [x] Documenter l’installation et l’utilisation, les résultats et limites.
- [x] Revue indépendante, corrections nécessaires, commit et PR.
