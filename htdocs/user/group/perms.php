<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *       \file       htdocs/user/group/perms.php
 *       \brief      Onglet user et permissions de la fiche utilisateur
 *       \version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/usergroups.lib.php");

$langs->load("users");

$module=isset($_GET["module"])?$_GET["module"]:$_POST["module"];

// Defini si peux modifier utilisateurs et permisssions
$caneditperms=($user->admin || $user->rights->user->user->creer);


/**
 * Actions
 */
if ($_GET["action"] == 'addrights' && $caneditperms)
{
    $editgroup = new Usergroup($db,$_GET["id"]);
    $editgroup->addrights($_GET["rights"],$module);
}

if ($_GET["action"] == 'delrights' && $caneditperms)
{
    $editgroup = new Usergroup($db,$_GET["id"]);
    $editgroup->delrights($_GET["rights"],$module);
}


/* ************************************************************************** */
/*                                                                            */
/* Visu et edition                                                            */
/*                                                                            */
/* ************************************************************************** */

$form = new Form($db);

llxHeader('',$langs->trans("Permissions"));

if ($_GET["id"])
{
    $fgroup = new Usergroup($db, $_GET["id"]);
    $fgroup->fetch($_GET["id"]);
    $fgroup->getrights();

	/*
	 * Affichage onglets
	 */
	$head = group_prepare_head($fgroup);
	$title = $langs->trans("Group");
	dol_fiche_head($head, 'rights', $title);


    $db->begin();

    // Charge les modules soumis a permissions
    $modules = array();
    foreach ($conf->file->dol_document_root as $dirroot)
	{
		$dir = $dirroot . "/includes/modules/";

		// Load modules attributes in arrays (name, numero, orders) from dir directory
		//print $dir."\n<br>";
		$handle=@opendir($dir);
		if ($handle)
		{
		    while (($file = readdir($handle))!==false)
		    {
		        if (is_readable($dir.$file) && substr($file, 0, 3) == 'mod'  && substr($file, strlen($file) - 10) == '.class.php')
		        {
		            $modName = substr($file, 0, strlen($file) - 10);

		            if ($modName)
		            {
		                include_once($dir."/".$file);
		                $objMod = new $modName($db);
		                if ($objMod->rights_class) {

		                    $ret=$objMod->insert_permissions();

		                    $modules[$objMod->rights_class]=$objMod;
		                    //print "modules[".$objMod->rights_class."]=$objMod;";
		                }
		            }
		        }
		    }
		}
	}

    $db->commit();

    // Lecture des droits groupes
    $permsgroup = array();

    $sql = "SELECT r.id, r.libelle, r.module ";
    $sql.= " FROM ".MAIN_DB_PREFIX."rights_def as r";
    $sql.= ", ".MAIN_DB_PREFIX."usergroup_rights as ugr";
    $sql.= " WHERE ugr.fk_id = r.id";
    $sql.= " AND r.entity = ".$conf->entity;
    $sql.= " AND ugr.fk_usergroup = ".$fgroup->id;

    $result=$db->query($sql);

    if ($result)
    {
        $num = $db->num_rows($result);
        $i = 0;
        while ($i < $num)
        {
            $obj = $db->fetch_object($result);
            array_push($permsgroup,$obj->id);
            $i++;
        }
        $db->free($result);
    }
    else
    {
        dol_print_error($db);
    }


    /*
     * Ecran ajout/suppression permission
     */

    print '<table class="border" width="100%">';

    // Ref
    print '<tr><td width="25%" valign="top">'.$langs->trans("Ref").'</td>';
    print '<td colspan="2">';
    print $form->showrefnav($fgroup,'id','',$user->rights->user->user->lire || $user->admin);
    print '</td>';
    print '</tr>';

    // Nom
    print '<tr><td width="25%" valign="top">'.$langs->trans("Name").'</td>';
    print '<td colspan="2">'.$fgroup->nom.'';
    if (! $fgroup->entity)
    {
    	print img_redstar($langs->trans("GlobalGroup"));
    }
    print "</td></tr>\n";

    // Note
    print '<tr><td width="25%" valign="top">'.$langs->trans("Note").'</td>';
    print '<td class="valeur">'.nl2br($fgroup->note).'</td>';
    print "</tr>\n";

    print '</table><br>';

    if ($user->admin) print info_admin($langs->trans("WarningOnlyPermissionOfActivatedModules"));

    print '<table width="100%" class="noborder">';
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("Module").'</td>';
    if ($caneditperms) print '<td width="24">&nbsp</td>';
    print '<td align="center" width="24">&nbsp;</td>';
    print '<td>'.$langs->trans("Permissions").'</td>';
    print '</tr>';

    $sql = "SELECT r.id, r.libelle, r.module";
    $sql.= " FROM ".MAIN_DB_PREFIX."rights_def as r";
    $sql.= " WHERE r.libelle NOT LIKE 'tou%'";    // On ignore droits "tous"
    $sql.= " AND r.entity = ".$conf->entity;
    $sql.= " ORDER BY r.module, r.id";

    $result=$db->query($sql);
    if ($result)
    {
        $num = $db->num_rows($result);
        $i = 0;
        $var = True;
        while ($i < $num)
        {
            $obj = $db->fetch_object($result);

            // Si la ligne correspond a un module qui n'existe plus (absent de includes/module), on l'ignore
            if (! $modules[$obj->module])
            {
                $i++;
                continue;
            }

            if ($oldmod <> $obj->module)
            {
                $oldmod = $obj->module;
                $var = !$var;

                // Rupture d?tect?e, on r?cup?re objMod
                $objMod = $modules[$obj->module];
                $picto=($objMod->picto?$objMod->picto:'generic');

                if ($caneditperms)
                {
                   print '<tr '. $bc[$var].'>';
                   print '<td nowrap="nowrap">'.img_object('',$picto).' '.$objMod->getName();
                   print '<a name="'.$objMod->getName().'">&nbsp;</a></td>';
                   print '<td align="center" nowrap="nowrap">';
                   print '<a title='.$langs->trans("All").' alt='.$langs->trans("All").' href="perms.php?id='.$fgroup->id.'&amp;action=addrights&amp;module='.$obj->module.'#'.$objMod->getName().'">'.$langs->trans("All")."</a>";
                   print '/';
                   print '<a title='.$langs->trans("None").' alt='.$langs->trans("None").' href="perms.php?id='.$fgroup->id.'&amp;action=delrights&amp;module='.$obj->module.'#'.$objMod->getName().'">'.$langs->trans("None")."</a>";
                   print '</td>';
                   print '<td colspan="2">&nbsp;</td>';
                   print '</tr>';
                }
            }

            print '<tr '. $bc[$var].'>';

			// Module
            print '<td nowrap="nowrap">'.img_object('',$picto).' '.$objMod->getName().'</td>';

            if (in_array($obj->id, $permsgroup))
            {
                // Own permission by group
                if ($caneditperms)
                {
                    print '<td align="center"><a href="perms.php?id='.$fgroup->id.'&amp;action=delrights&amp;rights='.$obj->id.'#'.$objMod->getName().'">'.img_edit_remove($langs->trans("Remove")).'</a></td>';
                }
                print '<td align="center">';
                print img_tick();
                print '</td>';
            }
            else
            {
                // Do not own permission
                if ($caneditperms)
                {
                    print '<td align="center"><a href="perms.php?id='.$fgroup->id.'&amp;action=addrights&amp;rights='.$obj->id.'#'.$objMod->getName().'">'.img_edit_add($langs->trans("Add")).'</a></td>';
                }
                print '<td>&nbsp</td>';
            }

            $perm_libelle=(($langs->trans("Permission".$obj->id)!=("Permission".$obj->id))?$langs->trans("Permission".$obj->id):$obj->libelle);
            print '<td>'.$perm_libelle. '</td>';

            print '</tr>';

            $i++;
        }
    }
    print '</table>';
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
