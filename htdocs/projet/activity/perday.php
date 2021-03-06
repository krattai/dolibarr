<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010      François Legastelois <flegastelois@teclib.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/projet/activity/pertime.php
 *	\ingroup    projet
 *	\brief      List activities of tasks (per time entry)
 */

require ("../../main.inc.php");
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

$langs->load('projects');

$action=GETPOST('action');
$mode=GETPOST("mode");
$id=GETPOST('id','int');

$mine=0;
if ($mode == 'mine') $mine=1;

$projectid='';
$projectid=isset($_GET["id"])?$_GET["id"]:$_POST["projectid"];

// Security check
$socid=0;
if ($user->societe_id > 0) $socid=$user->societe_id;
$result = restrictedArea($user, 'projet', $projectid);

$now=dol_now();


/*
 * Actions
 */

if ($action == 'addtime' && $user->rights->projet->creer)
{
    $task = new Task($db);



}



/*
 * View
 */

$form=new Form($db);
$formother=new FormOther($db);
$projectstatic=new Project($db);
$project = new Project($db);
$taskstatic = new Task($db);

$title=$langs->trans("TimeSpent");
if ($mine) $title=$langs->trans("MyTimeSpent");

//$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,$mine,1);
$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,0,1);  // Return all project i have permission on. I want my tasks and some of my task may be on a public projet that is not my project

if ($id)
{
    $project->fetch($id);
    $project->fetch_thirdparty();
}

$onlyopened=1;	// or -1
$tasksarray=$taskstatic->getTasksArray(0,0,($project->id?$project->id:$projectsListId),$socid,0,'',$onlyopened);    // We want to see all task of opened project i am allowed to see, not only mine. Later only mine will be editable later.
$projectsrole=$taskstatic->getUserRolesForProjectsOrTasks($user,0,($project->id?$project->id:$projectsListId),0);
$tasksrole=$taskstatic->getUserRolesForProjectsOrTasks(0,$user,($project->id?$project->id:$projectsListId),0);
//var_dump($tasksarray);
//var_dump($projectsrole);
//var_dump($taskrole);


llxHeader("",$title,"",'','','',array('/core/js/timesheet.js'));

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, "", $num);


print '<form name="addtime" method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$project->id.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="addtime">';
print '<input type="hidden" name="mode" value="'.$mode.'">';

$head=project_timesheet_prepare_head($mode);
dol_fiche_head($head, 'inputperday', '', 0, 'task');

// Show description of content
if ($mine) print $langs->trans("MyTasksDesc").($onlyopened?' '.$langs->trans("OnlyOpenedProject"):'').'<br><br>';
else
{
	if ($user->rights->projet->all->lire && ! $socid) print $langs->trans("ProjectsDesc").($onlyopened?' '.$langs->trans("OnlyOpenedProject"):'').'<br><br>';
	else print $langs->trans("ProjectsPublicTaskDesc").($onlyopened?' '.$langs->trans("AlsoOnlyOpenedProject"):'').'<br><br>';
}
print "\n";

// Filter on user
/*	dol_fiche_head('');
	print '<table class="border" width="100%"><tr><td width="25%">'.$langs->trans("User").'</td>';
	print '<td>';
	if ($mine) print $user->getLoginUrl(1);
	print '</td>';
	print '</tr></table>';
	dol_fiche_end();
*/

// Filter on user
/*	dol_fiche_head('');
	print '<table class="border" width="100%"><tr><td width="25%">'.$langs->trans("User").'</td>';
	print '<td>';
	if ($mine) print $user->getLoginUrl(1);
	print '</td>';
	print '</tr></table>';
	dol_fiche_end();
*/

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Project").'</td>';
print '<td>'.$langs->trans("RefTask").'</td>';
print '<td>'.$langs->trans("LabelTask").'</td>';
print '<td align="right">'.$langs->trans("PlannedWorkload").'</td>';
print '<td align="right">'.$langs->trans("ProgressDeclared").'</td>';
//print '<td align="right">'.$langs->trans("TimeSpent").'</td>';

$tmp=dol_getdate($now);
$startdayarray=dol_get_first_day_week($tmp['mday'], $tmp['mon'], $tmp['year']);
$startday=dol_mktime(12, 0, 0, $startdayarray['first_month'], $startdayarray['first_day'], $startdayarray['first_year']);

for($i=0;$i<7;$i++)
{
	print '<td width="7%" align="center">'.dol_print_date($startday + ($i * 3600 * 24), '%a').'<br>'.dol_print_date($startday + ($i * 3600 * 24), 'day').'</td>';
}

print "</tr>\n";

// By default, we can edit only tasks we are assigned to
$restricteditformytask=(empty($conf->global->PROJECT_TIME_ON_ALL_TASKS_MY_PROJECTS)?1:0);

if (count($tasksarray) > 0)
{
	$j=0;
	projectLinesPerDay($j, 0, $tasksarray, $level, $projectsrole, $tasksrole, $mine, $restricteditformytask);

	print '<tr class="liste_total">
                <td class="liste_total" colspan="5" align="right">'.$langs->trans("Total").'</td>
                <td class="liste_total" width="7%" align="center"><div id="totalDay[0]">&nbsp;</div></td>
                <td class="liste_total" width="7%" align="center"><div id="totalDay[1]">&nbsp;</div></td>
                <td class="liste_total" width="7%" align="center"><div id="totalDay[2]">&nbsp;</div></td>
                <td class="liste_total" width="7%" align="center"><div id="totalDay[3]">&nbsp;</div></td>
                <td class="liste_total" width="7%" align="center"><div id="totalDay[4]">&nbsp;</div></td>
                <td class="liste_total" width="7%" align="center"><div id="totalDay[5]">&nbsp;</div></td>
                <td class="liste_total" width="7%" align="center"><div id="totalDay[6]">&nbsp;</div></td>
    </tr>';
}
else
{
	print '<tr><td colspan="10">'.$langs->trans("NoTasks").'</td></tr>';
}
print "</table>";

print '<input type="hidden" name="timestamp" value="1425423513"/>'."\n";
print '<input type="hidden" id="numberOfLines" name="numberOfLines" value="'.count($tasksarray).'"/>'."\n";

dol_fiche_end();

print '<div class="center">';
print '<input type="button" class="button" name="save" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
print '</div>';

print '</form>';


llxFooter();

$db->close();
