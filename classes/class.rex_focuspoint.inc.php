<style>

.more {
  display: none;
     }
        a.showLink, a.hideLink {
           text-decoration: none;
           color: #0092C0;
          }

.helper-tool, .helper-tool * {
  box-sizing:border-box;
}
.helper-tool {
  padding:12px;
  border:1px solid #fcfcfc;
}
.helper-tool input {
  position:relative;
  width:100%;
}

/* !HELPER TOOL TARGETING SYSTEM */
.focuspoint img {
  transition: all 500ms ease-in-out;
  -webkit-transition: all 500ms ease-in-out;
  -moz-transition: all 500ms ease-in-out;
}

/* !HELPER TOOL TARGETING SYSTEM */
.helper-tool-target {
  position: relative;
  width: 470px;
  overflow: hidden;
  margin-bottom:1em;
}
.helper-tool-target img {
  display: block;
  max-width: 100%;
  height:auto;
}
.helper-tool-target img.target-overlay, .helper-tool-target img.reticle {
  position: absolute;
  top: 0;
  left: 0;
}
.helper-tool-target img.target-overlay {
  cursor:pointer;
  opacity: 0.01;
}
.helper-tool-target img.reticle {
  -webkit-transform: translate(-50%, -50%);
  -ms-transform: translate(-50%, -50%);
  transform: translate(-50%, -50%);
  top:50%;
  left:50%;
  transition: all 500ms ease-in-out;
  -webkit-transition: all 500ms ease-in-out;
  -moz-transition: all 500ms ease-in-out;
}
</style>




<script type="text/javascript" src="/../../redaxo/media/jquery.min.js"></script>
<script type="text/javascript" src="/../../files/addons/focuspoint/jquery_focuspoint.js"></script>



<?php

class rex_focuspoint {

  static public function set_media($params)
  {
    global $REX;
    $filename = $params['filename'];
    $filenamepath = $REX["INCLUDE_PATH"].'/../../files/'.$filename;

    $s = rex_sql::factory()->getArray('select * from ' . $REX['TABLE_PREFIX'] . 'file where filename="' . mysql_real_escape_string($filename) . '"');

  }


  static public function show_form_info($params)
  {

    $vars = rex_sql::factory()->getArray('select * from rex_file where file_id='.$params["file_id"]);
    $dateiart = substr($vars[0][filename], -3);
    if ($dateiart == 'jpg' OR $dateiart == 'png' OR $dateiart == 'gif') {
   echo "
   <script>
     function showHide(shID) {
        if (document.getElementById(shID)) {
           if (document.getElementById(shID+'-show').style.display != 'none') {
              document.getElementById(shID+'-show').style.display = 'none';
              document.getElementById(shID).style.display = 'block';
           }
           else {
              document.getElementById(shID+'-show').style.display = 'inline';
              document.getElementById(shID).style.display = 'none';
           }
        }
     }
   </script>
   ";

   echo '
     <div id="focuspointinfo-show" class="rex-form-row">
     <p class="rex-form-read">
     <label for="fwidth">focuspoint</label>
       <span id="fwidth" class="rex-form-read">
        <a href="#"  class="showLink" onclick="showHide(\'focuspointinfo\');return false;">Anzeigen</a>
       </span>
     </p>
     </div>

    <div id="focuspointinfo" class="more">
     <div class="rex-form-row">
     <p class="rex-form-read">
        <label for="fwidth">focuspoint</label>
      <span id="fwidth" class="rex-form-read">
        <a href="#" id="iptc-hide" class="hideLink" onclick="showHide(\'focuspointinfo\');return false;">Verbergen</a>
       </span>
     </p>
     </div>


   <div class="rex-form-row">
       <div id="fwidth" class="helper-tool-target">
            <img src="'.$REX["INCLUDE_PATH"].'/../../files/'.$vars[0][filename].'" class="helper-tool-img">
            <img class="reticle" src="/../../files/addons/focuspoint/focuspoint-target.png">
        </div>
     </div>

    </div>';
     }
    }
}

?>
