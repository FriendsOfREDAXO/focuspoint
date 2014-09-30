<?php

$example_content = file_get_contents(rex_path::addon("focuspoint","module/example_out.php"));
$example_content = highlight_string($example_content, true);

$example_script = '<link rel="stylesheet" href="/files/addons/focuspoint/focuspoint.css" />
<script type="text/javascript" src="http://code.jquery.com/jquery-2.1.0.min.js" /></script>
<script type="text/javascript" src="/files/addons/focuspoint/jquery_focuspoint.js"></script>
<script type="text/javascript">
$( document ).ready(function() {
  $(\'.focuspoint\').focusPoint();
});
</script>
';
$example_script = highlight_string($example_script, true);

echo '
<div class="rex-addon-output">

    <h3 class="rex-hl2">'.$I18N->msg("fp_help").'</h3>
    <div class="rex-area-content">

      <p class="rex-tx1">'.$I18N->msg("fp_module_script_info").'</p>
      <p class="rex-code">'.$example_script.'</p>


      <p class="rex-tx1"><br />'.$I18N->msg("fp_effect_info").'</p>
      <p class="rex-tx1">'.$I18N->msg("fp_module_info").'</p>
      <p class="rex-code">'.$example_content.'</p>



    </div>

</div>';


?>



