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
      global $REX;


echo'
<style>
  .more { display: none; }
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
          float: left;
          width: auto;
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
          border: 1px solid #fff;
}


</style>

<script type="text/javascript" src="/../../redaxo/media/jquery.min.js"></script>
<script type="text/javascript" src="'.$REX["HTDOCS_PATH"].'files/addons/focuspoint/jquery_focuspoint.js" ></script>
';

   $vars = rex_sql::factory()->getArray('select * from rex_file where file_id='.$params["file_id"]);
   $saved_css_data = explode(",",  $vars[0]['med_focuspoint_css'] , 2);;

    if (count($saved_css_data) == 2) {
        $css_x = $saved_css_data[0];
        $css_y = $saved_css_data[1];

	echo '
	<style>
		.helper-tool-target img.target-overlay, .helper-tool-target img.reticle  {
        	top: '.$css_y.';
			left: '.$css_x.';
		}
	</style>
	';

    }

    $dateiart = substr($vars[0]['filename'], -3);
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
     <label for="fwidth">Focuspoint</label>
       <span id="fwidth" class="rex-form-read">
        <a href="#"  class="showLink" onclick="showHide(\'focuspointinfo\');return false;">Anzeigen</a>
       </span>
     </p>
     </div>

    <div id="focuspointinfo" class="more">
     <div class="rex-form-row">
     <p class="rex-form-read">
        <label for="fwidth">Focuspoint</label>
      <span id="fwidth" class="rex-form-read">
        <a href="#" id="iptc-hide" class="hideLink" onclick="showHide(\'focuspointinfo\');return false;">Verbergen</a>
       </span>
     </p>
     </div>


   <div class="rex-form-row">
      
       <div id="fwidth" class="helper-tool-target">
	            <img class="helper-tool-img" src="'.$REX["HTDOCS_PATH"].'files/'.$vars[0]['filename'].'" >
            <img class="reticle" src="'.$REX["HTDOCS_PATH"].'files/addons/focuspoint/focuspoint-target.png">
            <img class="target-overlay" src="'.$REX["HTDOCS_PATH"].'files/'.$vars[0]['filename'].'" >
        </div>
     </div>

    </div>';
     }
    }
}

?>
