<?php

class rex_focuspoint {
  static public function set_media($media)
  {
    $file_id = $_GET["file_id"];
    $s = rex_sql::factory()->getArray('select * from rex_media file where id="'.$file_id.'"');
  }

  static public function show_form_info($media)
  {
    $file_id = $_GET["file_id"];

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
                display: inline-block;
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
                width: 102px;
                height: 102px;
                -webkit-transform: translate(-50%, -50%);
                -ms-transform: translate(-50%, -50%);
                transform: translate(-50%, -50%);
                top: 50%;
                left: 50%;
                transition: all 500ms ease-in-out;
                -webkit-transition: all 500ms ease-in-out;
                -moz-transition: all 500ms ease-in-out;
      }

      </style>

      <script type="text/javascript" src="./../assets/addons/focuspoint/jquery_focuspoint.js" ></script>
    ';

    $vars = rex_sql::factory()->getArray('select * from rex_media where id='.$file_id);

    $saved_css_data = explode(",",  $vars[0]['med_focuspoint_css'] , 2);

    $css_x = '';
    $css_y = '';
    if (count($saved_css_data) == 2) {
        $css_x = $saved_css_data[0];
        $css_y = $saved_css_data[1];
    }

	   echo '
    	<style>
    		.helper-tool-target img.target-overlay, .helper-tool-target img.reticle  {
            	 top: '.$css_y.';
    			     left: '.$css_x.';
    		}
    	</style>
	   ';

    $filename = $vars[0]['filename'];
    $dateiart = substr($filename, -3);

    if ($dateiart == 'jpg' OR $dateiart == 'png' OR $dateiart == 'gif') {

      $html = '<div class="rex-mediapool-detail-image col-sm-4"><div id="fwidth" class="helper-tool-target"><img class="helper-tool-img" src="'.rex_url::media($filename).'" ><img class="reticle" src="./../assets/addons/focuspoint/focuspoint-target.png"><img class="target-overlay" src="'.rex_url::media($filename).'" ></div></div>';

      echo "
        <script>
          $(document).on('ready pjax:success',function(){
            $('.panel-body .col-sm-4').replaceWith('$html');
          });
        </script>
      ";
    }
  }
}
?>
