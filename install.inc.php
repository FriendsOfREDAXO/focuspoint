<?php

$REX['ADDON']['install']['focuspoint'] = true;

function focus_setup_metainfo()
{
    global $REX;

    if (!isset($REX['USER'])) {
        return;
    }



    $install_metas = array(
        'med_focuspoint_data' => array('Focuspoint Data', 'med_focuspoint_data', 200, '', 1, '', '', '', ''),
        'med_focuspoint_css' => array('Focuspoint CSS', 'med_focuspoint_css', 201, '', 1, '', '', '', ''),
    );


    $db = new rex_sql;
    foreach ($db->getDbArray('SHOW COLUMNS FROM `rex_file` LIKE \'med_focuspoint_%\';') as $column) {
    unset($install_metas[$column['Field']]);
    }

    foreach ($install_metas as $k => $v) {
        $db->setQuery('SELECT `name` FROM `rex_62_params` WHERE `name`=\'' . $k . '\';');

        if ($db->getRows() > 0) {
            // FIELD KNOWN TO METAINFO BUT MISSING IN ARTICLE..
            $db->setQuery('ALTER TABLE `rex_file` ADD `' . $k . '` TEXT NOT NULL;');
            if ($REX['REDAXO']) {
                echo rex_info('Metainfo Feld ' . $k . ' wurde repariert.');
            }
        } else {
            if (!function_exists('a62_add_field')) {
                require_once $REX['INCLUDE_PATH'] . '/addons/metainfo/functions/function_metainfo.inc.php';
            }

            a62_add_field($v[0], $v[1], $v[2], $v[3], $v[4], $v[5], $v[6], $v[7], $v[8]);

            if ($REX['REDAXO']) {
                echo rex_info('Metainfo Feld ' . $k . ' wurde angelegt.');
            }
        }
    }

  rex_file::copy(
      rex_path::addon("focuspoint","classes/class.rex_effect_focuspoint_resize.inc.php"),
      rex_path::addon("image_manager","classes/effects/class.rex_effect_focuspoint_resize.inc.php")
  );

}

focus_setup_metainfo();
