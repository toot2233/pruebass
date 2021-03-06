<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
    \file       htdocs/includes/boxes/box_osc_client.php
    \ingroup    osc
    \brief      Module de g?n?ration de l'affichage de la box osc client
	\version	$Id$
*/

include_once(DOL_DOCUMENT_ROOT."/includes/boxes/modules_boxes.php");


class box_osc_clients extends ModeleBoxes {

    var $boxcode="nbofcustomers";
    var $boximg="object_company";
    var $boxlabel;
    var $depends = array("boutique");

	var $db;
	var $param;

    var $info_box_head = array();
    var $info_box_contents = array();

    /**
     *      \brief      Constructeur de la classe
     */
    function box_osc_clients()
    {
        global $langs;
        $langs->load("boxes");

        $this->boxlabel=$langs->trans("BoxNbOfCustomers");
    }

    /**
     *      \brief      Charge les donn?es en m?moire pour affichage ult?rieur
     *      \param      $max        Nombre maximum d'enregistrements ? charger
     */
    function loadBox($max=5)
    {
        global $conf, $user, $langs, $db;
        $langs->load("boxes");

		$this->max=$max;

		$this->info_box_head = array('text' => $langs->trans("BoxTitleNbOfCustomers",$max));

        if ($user->rights->boutique->lire)
        {
            $sql = "SELECT count(*) as cus FROM ".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."customers";

            $resql = $db->query($sql);
            if ($resql)
            {
                $num = $db->num_rows($resql);

                $i = 0;

                while ($i < $num)
                {
                    $objp = $db->fetch_object($resql);

                    $this->info_box_contents[$i][0] = array('td' => 'align="center" width="16"',
                    'logo' => $this->boximg,
                    'url' => DOL_URL_ROOT."/boutique/client/index.php");
                    $this->info_box_contents[$i][1] = array('td' => 'align="center"',
                    'text' => $objp->cus,
                    'url' => DOL_URL_ROOT."/boutique/client/index.php");
                    $i++;
                }
            }
            else {
                dol_print_error($db);
            }
        }
        else {
            $this->info_box_contents[0][0] = array('td' => 'align="left"',
            'text' => $langs->trans("ReadPermissionNotAllowed"));
        }

    }

    function showBox()
    {
        parent::showBox($this->info_box_head, $this->info_box_contents);
    }

}

?>
