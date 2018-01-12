<?php

/**
 * focuspoint Addon.
 *
 * @author FriendsOfREDAXO
 *
 * @var rex_addon
 */
class rex_focuspoint
{
    public static function set_media($media)
    {
        $file_id = rex_request('file_id', 'int');
        $s = rex_sql::factory()->getArray('select * from ' . rex::getTable('media') . ' file where id="' . $file_id . '"');
    }
    
    public static function show_form_info($media)
    {
        if (rex_post('updatepreview') == "true")
        {
            ob_end_clean();
            rex_media_manager::deleteCache(rex_post('filename'), rex_post('type'));
            
            $sql = rex_sql::factory();
            $sql->setTable(rex::getTable('media'));
            $sql->setWhere(array('id' => rex_request('file_id', 'int')));
            $sql->setValue('med_focuspoint_data', rex_post('med_focuspoint_data'));
            $sql->setValue('med_focuspoint_css', rex_post('med_focuspoint_css'));
            
            try
            {
                $sql->update();
            }
            catch (rex_sql_exception $e)
            {
                $error = $sql->getError();
                throw new Exception($error);
            }

            rex_media_cache::generate(rex_post('filename'));

            exit;
        }
        
        // aufruf ueber mediapool
        if (rex_request('file_id', 'int'))
        {
            $vars = rex_sql::factory()->getArray('select * from ' . rex::getTable('media') . ' where id=' . rex_request('file_id', 'int'));
        }
        // aufruf ueber widget
        else
        {
            $vars = rex_sql::factory()->getArray('select * from ' . rex::getTable('media') . ' where filename="' . rex_request('file_name', 'string') . '"');
        }
        $filename = $vars[0]['filename'];
        if (!rex_media::isImageType(pathinfo($filename, PATHINFO_EXTENSION)))
        {
            return;
        }
        $saved_css_data = array_filter(explode(',', $vars[0]['med_focuspoint_css']));
        $css_x = isset($saved_css_data[0]) && trim($saved_css_data[0]) ? trim($saved_css_data[0]) : '50%';
        $css_y = isset($saved_css_data[1]) && trim($saved_css_data[1]) ? trim($saved_css_data[1]) : '50%';

        echo '
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
        /* das eigentliche bild, sichtbar, unterhalb von reticle und overlay */
        .helper-tool-target img {
            position: relative;
            display: block;
            max-width: 100%;
            height:auto;
        }
        /* kopie des bildes zum klicken, oberhalb von reticle und eigentlichem bild */
        .helper-tool-target img.target-overlay {
            position: absolute;
            top: 0;
            left: 0;
            cursor: pointer;
            opacity: 0.0;
        }
        /* fadenkreuz zwischen sichtbarem bild und klicklayer*/
        .helper-tool-target img.reticle {
            width: 102px;
            height: 102px;
            -webkit-transform: translate(-50%, -50%);
            -ms-transform: translate(-50%, -50%);
            transform: translate(-50%, -50%);
            position: absolute;
            top: ' . $css_y . ';
            left: ' . $css_x . ';
            transition: all 500ms ease-in-out;
            -webkit-transition: all 500ms ease-in-out;
            -moz-transition: all 500ms ease-in-out;
        }
        .focuspoint-reset {
            cursor: pointer;
            display: inline-block;
        }
        #focuspoint-preview {
            padding: 20px 0;
        }
        #preview-container {
            display: inline-block;
            transition: all 300ms ease-in-out;
            -webkit-transition: all 300ms ease-in-out;
            -moz-transition: all 300ms ease-in-out;
        }
        #preview-container img {
            max-width: 100%;
            visibility: hidden;
        }
        </style>
        ';
        
        function in_array_r($needle, $haystack, $strict = false)
        {
            foreach ($haystack as $item)
            {
                if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_array_r($needle, $item, $strict)))
                {
                    return true;
                }
            }

            return false;
        }

        $mediatypesArray = rex_sql::factory()->getArray('select name from ' . rex::getTable('media_manager_type'));
        $html = '<div class="rex-mediapool-detail-image col-sm-4">';
        $html .= '<div id="fwidth" class="helper-tool-target">';
        $html .= '<img class="helper-tool-img" src="index.php?rex_media_type=rex_mediapool_maximized&rex_media_file=' . rex_url::media($filename) . '" >';
        $html .= '<img class="reticle" src="./../assets/addons/focuspoint/focuspoint-target.png">';
        $html .= '<img class="target-overlay" src="index.php?rex_media_type=rex_mediapool_maximized&rex_media_file=' . rex_url::media($filename) . '" >';
        $html .= '</div>';
        $html .= '<div class="btn-group" role="group">';
        $html .= '<button type="button" class="btn btn-primary focuspoint-reset" id="focuspoint-reset"><i class="fa fa-undo"></i>&nbsp;&nbsp;' . rex_i18n::msg('mediapool_focuspoint_reset') . '</button>';
        //focuspoint preview select
        $html .= '<div class="btn-group" role="group">';
        $html .= '<button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">' . rex_i18n::msg("mediapool_focuspoint_preview") . '&nbsp;&nbsp;';
        $html .= '<i class="fa fa-chevron-down"></i></button>';
        $html .= '<ul class="dropdown-menu" id="focuspoint-preview-select"> ';
        foreach ($mediatypesArray as $mediatype)
        {
            $media = rex_media::get($filename);
            $mediaPath = rex_path::media($filename);
            $managedMedia = new rex_managed_media($mediaPath);
            $mediaManager = new rex_media_manager($managedMedia);
            $mediatypeEffectsArray = $mediaManager->effectsFromType($mediatype["name"]);
            $hasFocusFit = in_array_r("focuspoint_fit", $mediatypeEffectsArray) ? true : false;
            $hasFocusResize = in_array_r("focuspoint_resize", $mediatypeEffectsArray) ? true : false;
            $type = "css";
            $name = $mediatype["name"];
            
            if($hasFocusFit || $hasFocusResize)
            {
                $type = "nativ";
            }
            
            $html .= '<li><a href="#" data-name="' . $name . '" data-type="'. $type .'">' . $name . '</a></li>';
        }
        $html .= '</ul> ';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<input type="hidden" name="updatepreview" id="updatepreview" value="false" />';
        $html .= '<div id="focuspoint-preview">';
        $html .= '<div id="preview-container">';
        $html .= '</div>';
        $html .= '</div>';
        
        echo "
        <script>
        $(document).on('ready pjax:success',function(){
            $('.panel-body .col-sm-4').replaceWith('$html');

            var typeSelected = false;
            var jSelect = jQuery('#focuspoint-preview-select');
            var jPreviewContainer = jQuery('#preview-container');
            var jPreviewAnchor = jSelect.find('a');
            var jUpdatePreviewInput = jQuery('#updatepreview');
            var jRexLoader = jQuery('#rex-js-ajax-loader');
            var mediaType = '';
            var focusType = '';
            var preview = false;

            function getPreviewSrc()
            {
                var d = new Date();
                return 'index.php?rex_media_type='+mediaType+'&rex_media_file=" . rex_url::media($filename) . "&t='+d.getTime();
            }

            function getPosition()
            {
                return jQuery('#rex-metainfo-med_focuspoint_css').val().replace(/,/g , '');
            }
            
            function updateNativPreview()
            {
                var img = new Image();
                img.onload = function () 
                {
                    jQuery(img).css('visibility', 'visible');
                    jPreviewContainer.find('img').remove();
                    jPreviewContainer.append(img);
                }
                img.src=getPreviewSrc();
            }
            
            function updateCssPreview()
            {
                jPreviewContainer.css('background-position', getPosition());
            }

            function updatePreview()
            {
                if(!typeSelected || mediaType == '')
                {
                    return false;
                }
                
                jQuery.ajax(
                {
                    type: 'POST',
                    url: '" . filter_input(INPUT_SERVER, 'REQUEST_URI') . "',
                    data: $('.panel-body form').serialize() + '&filename=" . $filename . "' + '&type=' + mediaType,
                    beforeSend: function ()
                    {
                        jRexLoader.addClass('rex-visible');
                    },
                    success: function (data)
                    {
                        if(focusType == 'nativ')
                        {
                            updateNativPreview();
                        }
                        else
                        {
                            updateCssPreview();
                        }

                        jRexLoader.fadeOut(function()
                        {
                            jRexLoader.removeClass('rex-visible');
                        });
                    },
                    error: function (xhr, type, exception)
                    {
                        jRexLoader.removeClass('rex-visible');
                        console.error('ajax error response xhr, type, exception ', xhr, type, exception);
                    }
                });
            }
            
            function setPreview()
            {
                jPreviewContainer.empty();
                jPreviewContainer.css('background-image', '');
                jPreviewContainer.css('background-position', '');
                var src = getPreviewSrc();
                var img = new Image();
                img.onload = function () 
                {
                    var jImg = jQuery(img);
                    if(focusType == 'nativ')
                    {
                        jImg.css('visibility', 'visible');
                    }
                    else
                    {
                        jPreviewContainer.css('background-image', 'url('+src+')');
                        jPreviewContainer.css('background-position', getPosition());
                        jImg.css('visibility', 'hidden');
                    }
                }
                img.src=src;
                jPreviewContainer.append(img);
                preview = true;
            }
            
            function resetFocuspointPreview()
            {
                jPreviewContainer.css('background-image', '');
                jPreviewContainer.css('background-position', '');
                jPreviewContainer.empty();
                
                jQuery.ajax(
                {
                    type: 'POST',
                    url: '" . filter_input(INPUT_SERVER, 'REQUEST_URI') . "',
                    data: $('.panel-body form').serialize() + '&filename=" . $filename . "' + '&type=' + mediaType,
                    beforeSend: function ()
                    {
                        jRexLoader.addClass('rex-visible');
                    },
                    success: function (data)
                    {
                        jRexLoader.fadeOut(function()
                        {
                            jRexLoader.removeClass('rex-visible');
                        });
                    },
                    error: function (xhr, type, exception)
                    {
                        jRexLoader.removeClass('rex-visible');
                        console.error('ajax error response xhr, type, exception ', xhr, type, exception);
                    }
                });
                
                typeSelected = false;
                jUpdatePreviewInput.val(false);
            }

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

                $('#rex-metainfo-med_focuspoint_data' ).val(focusX.toFixed(2) + ',' + focusY.toFixed(2));
                $('#rex-metainfo-med_focuspoint_css' ).val(backgroundPositionCSS);

                $('.reticle').css({
                    'top':percentageY+'%',
                    'left':percentageX+'%'
                });

                if(preview)
                {
                    updatePreview();
                }

                // window.alert('FocusX:' + focusX.toFixed(2) + ', FocusY:' + focusY.toFixed(2) + ' (For CSS version: ' + backgroundPositionCSS + ')');

            });
            $('#focuspoint-reset').on('click', function () {
                $('#rex-metainfo-med_focuspoint_data' ).val('');
                $('#rex-metainfo-med_focuspoint_css' ).val('');
                $('.reticle').css({
                    'top':'50%',
                    'left':'50%'
                });

                resetFocuspointPreview();
            });
            
            jPreviewAnchor.on('click', function(event)
            {
                event.preventDefault();
                
                var jThis = jQuery(this);
                var position = getPosition();
               
                mediaType = jThis.data('name');
                focusType = jThis.data('type');
                    
                if(position === '')
                {
                    alert('" . rex_i18n::msg('mediapool_focuspoint_preview_error') . "');
                    return false;
                }

                setPreview();
                
                typeSelected = true;
                jUpdatePreviewInput.val(true);
            });
        });
        </script>
        ";
    }
    
    public static function remove_inputs()
    {
        echo "
        <script>
        $(document).on('ready',function(){
            var jDataInput = jQuery('#rex-metainfo-med_focuspoint_data');
            var jCssInput = jQuery('#rex-metainfo-med_focuspoint_css');
            
            jDataInput.closest('.rex-form-group').hide();
            jCssInput.closest('.rex-form-group').hide();
        });

        </script>
        ";
    }
}
