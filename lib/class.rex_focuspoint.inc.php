<?php



class rex_focuspoint {
  static public function set_media($media)
  {

    $file_id = rex_request('file_id', 'int');
    $s = rex_sql::factory()->getArray('select * from rex_media file where id="'.$file_id.'"');
  }

  static public function show_form_info($media)
  {


   $file_id = rex_request('file_id', 'int');

    echo'
      <style>
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
                position: relative;
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
                top: 0 !important;
                left: 0 !important;
                cursor:pointer;
                opacity: 0.0;
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

            $('img').click(function(e){

              var imageW = $(this).width();
              var imageH = $(this).height();

              //Calculate FocusPoint coordinates
              var offsetX = e.pageX - $(this).offset().left;
              var offsetY = e.pageY - $(this).offset().top;
              var focusX = (offsetX/imageW - .5)*2;
              var focusY = (offsetY/imageH - .5)*-2;

              //Calculate CSS Percentages
              var percentageX = (offsetX/imageW)*100;
              var percentageY = (offsetY/imageH)*100;
              var backgroundPositionCSS = percentageX.toFixed(0) + '%, ' + percentageY.toFixed(0) + '%';

              $('#Focuspoint_Data' ).val(focusX.toFixed(2) + ',' + focusY.toFixed(2));
              $('#Focuspoint_CSS' ).val(backgroundPositionCSS);

              $('.reticle').css({
                'top':percentageY+'%',
                'left':percentageX+'%'
              });


             // window.alert('FocusX:' + focusX.toFixed(2) + ', FocusY:' + focusY.toFixed(2) + ' (For CSS version: ' + backgroundPositionCSS + ')');

            });

          });

        </script>
      ";
    }
  }
}

?>
