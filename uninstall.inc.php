<?php

// $u = rex_sql::factory();
// $u->setQuery('ALTER TABLE rex_file DROP med_focuspoint_data, DROP med_focuspoint_css;');

a62_delete_field('med_focuspoint_data');
a62_delete_field('med_focuspoint_css');



$REX['ADDON']['install']['focuspoint'] = 0;

?>
