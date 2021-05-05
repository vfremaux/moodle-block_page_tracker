<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Block Page Tracker Language File
 *
 * @author Valery Fremaux
 * @version $Id: block_page_tracker.php,v 1.5 2012-02-16 19:53:55 vf Exp $
 * @package block_page_tracker
 */

$string['page_tracker:addinstance'] = 'Peut ajouter une instance';
$string['page_tracker:accessallpages'] = 'Voit les liens pour vers toutes les pages';

$string['alllevels'] = 'Tous les niveaux';
$string['allowlinks'] = 'Génère des liens';
$string['blockname'] = 'Sommaire de cours';
$string['configtitle'] = 'Titre du sommaire (laisser vide pour le titre standard).';
$string['depth'] = 'Profondeur';
$string['displayerror'] = 'Une erreur est intervenue lors de la construction du contenu.';
$string['errormissingpage'] = 'La page de départ de ce menu semble avoir été supprimée. Vous devez reconfigurer le sommaire.';
$string['initiallyexpanded'] = 'Niveaux intiallement ouverts';
$string['pluginname'] = 'Sommaire de cours';
$string['root'] = 'Racine du cours';
$string['self'] = '-- La page courante';
$string['selfupper'] = '-- Navigation supérieure et arbre courant';
$string['parent'] = '-- La page parente';
$string['startpage'] = 'Page de départ';
$string['yesonvisited'] = 'Uniquement sur les pages déjà vues';
$string['hidedisabledlinks'] = 'Cacher complètement les liens désactivés';
$string['usemenulabels'] = 'Utiliser les noms de sommaire';
$string['hideaccessbullets'] = 'Cacher les marques d\'accès';
$string['configdefaultallowlinks'] = 'Générer les liens (défaut)';
$string['configdefaultallowlinks_desc'] = 'Valeur par défaut s\'appliquant à toute nouvelle instance';
$string['configdefaulthidedisabledlinks'] = 'Cacher les liens inactifs (défaut)';
$string['configdefaulthidedisabledlinks_desc'] = 'Valeur par défaut s\'appliquant à toute nouvelle instance';
$string['configdefaultdepth'] = 'Profondeur (défaut)';
$string['configdefaultdepth_desc'] = 'Valeur par défaut s\'appliquant à toute nouvelle instance';
$string['configdefaultusemenulabels'] = 'Utiliser les noms de sommaire (défaut)';
$string['configdefaultusemenulabels_help'] = 'Si cette option est active, les noms affichés dans le bloc sont les noms préparés pour les items de sommaire. Sinon, 
ce sera le nom longs de la page';
$string['configdefaultstartpage'] = 'Page de démarrage par défaut (positions generiques)';
$string['configdefaultstartpage_desc'] = 'Les choix génériques pour le début de la hiérarchie de pages';
$string['configdefaultusemenulabels_desc'] = 'Valeur par défaut s\'appliquant à toute nouvelle instance';
$string['configdefaulthideaccessbullets'] = 'Cacher les marques d\'accès (défaut)';
$string['configdefaulthideaccessbullets_desc'] = 'Valeur par défaut s\'appliquant à toute nouvelle instance';
$string['showanyway'] = 'Toujours montrer';

$string['allowlinks_help'] = 'Les liens ne seront générés que sur des pages déjà vues si cette option est active, car les items
pourraient être affichés à des utilisateurs qui n\'en ont pas le droit d\'accès.';

$string['showanyway_help'] = 'Si activé, toutes les entrées de pages seront visibles dans le sommaire, que l\'utilisateur y ait accès ou non.
Pour cette raison, l\'option "Génère des liens" est forcée à "Uniquement sur des pages déjà vues" ou "Non" si cette option est active.';

$string['upgradepagetrackerconfig'] = 'Mise à niveau des configurations des Sommaires de cours';


