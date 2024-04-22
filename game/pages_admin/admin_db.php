<?php

// Admin Area: Database integrity (check and fix tables)

// The purpose of this admin module is to at least slightly reduce the pain when changes are made to the format of database tables and the server database needs to be corrected "live" (without a clean reinstallation).
// First we compare the tables from install.php with what is in the real database at the moment.
// Then the reverse comparison is made - what is in the database now, with what is in the tables of install.php

// TODO: We don't know how to fix tables yet. An admin must manually fix tables by himself using phpMyAdmin or similar tool.

function DiffTab ($tabname, $src, $dst)
{
    $error = false;
    $res = "";

    $res .= "<table>\n";
    $res .= "<tr><td class=\"c\" colspan=3>$tabname</td></tr>";

    foreach ($src as $key=>$value) {
        
        $res .= "<tr><td>$key</td>";
        if (key_exists($key, $dst)) {
            $res .= "<td>".$dst[$key]."</td>";
        }
        else {
            $res .= "<td><font color=red>".loca("ADM_DB_COLUMN_NOT_FOUND")."</font></td>";
            $error = true;
        }
        $res .= "<td>$value</td></tr>";

    }

    $res .= "</table>\n";

    return $error ? $res : "";
}

function Admin_DB ()
{
    global $session;
    global $db_prefix;
    global $db_name;
    global $GlobalUser;

    include "install_tabs.php";

    $text_out = "";

    // DEBUG: dump the game tables from install
    //print_r ($tabs);
    //echo "<br/><br/>";

    // POST request processing.
    if ( method () === "POST" )
    {
        // TODO: We don't do anything yet.
    }

    // Get list of tables

    $query = "SHOW TABLES;";
    $result = dbquery ($query);
    $db_tabs = array();
    $rows = dbrows ($result);
    while ($rows--) {

        $row = dbarray ($result);
        $tablename = $row["Tables_in_" . $db_name];
        $tablename = str_replace ($db_prefix, "", $tablename);
        $db_tabs[$tablename] = array();
    }
    dbfree ($result);

    // Get a list of columns for each table

    foreach ($db_tabs as $i=>$tab) {
        
        $query = "SHOW COLUMNS FROM $db_prefix$i;";
        $result = dbquery($query);

        $rows = dbrows ($result);
        while ($rows--) {

            $row = dbarray ($result);
            
            // Provide a description of the column type by analogy with the table from install
            $column = $row['Type'];
            $column = str_replace ("int(10)", "int", $column);
            $column = str_replace ("int(11)", "int", $column);
            $column = str_replace ("bigint(20)", "bigint", $column);
            if ($row['Extra'] == "auto_increment") $column .= " AUTO_INCREMENT";
            if ($row['Key'] == "PRI") $column .= " PRIMARY KEY";
            $column = strtoupper ($column);

            $db_tabs[$i][$row['Field']] = $column;
        }
        dbfree ($result);
    }

    // DEBUG: dump actual database tables
    //print_r ($db_tabs);

    // Output the game tables from install, comparing their format with the format obtained from the database

    $text_out .= "<h2>".loca("ADM_DB_INSTALL_VS_DB")."</h2>";

    $res = "";
    foreach ($tabs as $i=>$cols) {
        
        if (key_exists($i, $db_tabs)) {
            $res .= DiffTab ($i, $tabs[$i], $db_tabs[$i]);
        }
        else {
            $res .= "<font color=red>".va(loca("ADM_DB_DB_TABLE_MISSING"), $i)."</font><br/>";
        }
    }
    if ($res == "") {
        $text_out .= "<font color=green>".loca("ADM_DB_SAME")."</font><br/>";
    }
    else $text_out .= $res;

    // Output tables from the database, comparing their format with tables from install

    $text_out .= "<h2>".loca("ADM_DB_DB_VS_INSTALL")."</h2>";

    $res = "";
    foreach ($db_tabs as $i=>$cols) {
        
        if (key_exists($i, $tabs)) {
            $res .= DiffTab ($i, $db_tabs[$i], $tabs[$i]);
        }
        else {
            $res .= "<font color=red>".va(loca("ADM_DB_INSTALL_TABLE_MISSING"), $i)."</font><br/>";
        }
    }
    if ($res == "") {
        $text_out .= "<font color=green>".loca("ADM_DB_SAME")."</font><br/>";
    }
    else $text_out .= $res;

?>

<?=AdminPanel();?>

<?=$text_out;?>

<?php
}
?>