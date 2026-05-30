/**
 *  This file is part of the REDAXO-AddOn "focuspoint".
 *
 *  @author      FriendsOfREDAXO @ GitHub <https://github.com/FriendsOfREDAXO/focuspoint>
 *  @version     2.0
 *  @copyright   FriendsOfREDAXO <https://friendsofredaxo.github.io/>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */
function fpCreatePosition( x,y )
{
    return {
        x : Math.max( 0, Math.min( 100, x ) ),
        y : Math.max( 0, Math.min( 100, y ) ),
        X : function() { return this.x.toFixed(1); },
        Y : function() { return this.y.toFixed(1); },
        asData: function() { return this.X() + ',' + this.Y(); },
        asInfo: function() { return this.X() + '% | ' + this.Y() + '%'; },
        asCss: function () { return {'top':this.Y()+'%','left':this.X()+'%'}; },
    }
}

function fpCreatePositionFromEvent( event )
{
    var that = $(event.currentTarget);
    return fpCreatePosition( (event.pageX - that.offset().left + 1)/that.width() * 100, (event.pageY - that.offset().top + 1)/that.height() * 100 );
}

function fpCreatePositionFromData( data )
{
    data = data.match( /^((100|[1-9]?[0-9])[.][0-9]),((100|[1-9]?[0-9])[.][0-9])$/ );
    if( data == null ) return fpCreatePosition( 50, 50 );
    return fpCreatePosition( data[1], data[3] );
}

function fpCreateController ( container, mediafile )
{
    var controller = {
        // Links to DOM-Elements
        domContainer        : container,
        domSelect           : container.children('.focuspoint-panel-select').find('select'),
        domBadge            : container.find('.badge'),
        domImageContainer   : container.children('.focuspoint-panel-image'),
        domImage            : container.children('.focuspoint-panel-image').find('img'),
        domPointer          : container.children('.focuspoint-panel-image').find('div'),
        domPreviewContainer : container.children('div:last-child'),
        domPreviewImage     : container.children('div:last-child').find('img'),
        domPreviewOff       : container.find('button[data-button="schalter"]'),
        domPreviewSelect    : container.find('.focuspoint-panel-typeselect'),
        domInfo             : container.find('small span'),
        domField            : null,
        domInput            : null,
        zoomMode            : false,
        // internal variables
        mediafile           : mediafile,
        mediatypes          : '',
        cPos                : null,
        oPos                : null,
        // Service-Functions
        setPreview          : function()
                                {
                                    if( this.domPreviewContainer.hasClass('hidden') ) return this;
                                    if( this.mediatype == '' ) { this.domPreviewOff.click(); return; }
                                    var previewUrl = new URL(window.location.origin + window.location.pathname);
                                    previewUrl.searchParams.set('rex-api-call', 'focuspoint');
                                    previewUrl.searchParams.set('type', this.mediatype);
                                    previewUrl.searchParams.set('file', this.mediafile);
                                    previewUrl.searchParams.set('xy', this.cPos.asData());
                                    previewUrl.searchParams.set('_fpv', Date.now().toString());
                                    this.domPreviewImage.attr('src', previewUrl.toString());
                                },
        setInfo             : function( pos ) {
                                    this.domInfo.html( pos.asInfo() );
                                },
        updateView          : function() {
                                    this.domPointer.css( this.cPos.asCss() );
                                    this.setInfo( this.cPos );
                                    this.setPreview();
                                },
        previewToggle       : function( toggle ) {
                                    this.domPreviewContainer.toggleClass( 'hidden', !toggle );
                                    this.domPreviewOff.toggleClass( 'hidden', !toggle );
                                },
        setZoomMode         : function( toggle ) {
                                    this.zoomMode = !!toggle;
                                    this.domContainer.toggleClass( 'focuspoint-panel-zoom', this.zoomMode );
                                    $('body').toggleClass( 'focuspoint-overlay-open', this.zoomMode );
                                },
        toggleZoomMode      : function() {
                                    this.setZoomMode( !this.zoomMode );
                                },

        // Event-related functions
        select              : function( field ) {
                                    this.domField = field.closest( '.focuspoint-input-group' );
                                    this.domInput = this.domField.find('input');
                                    var name = this.domInput.attr('name');
                                    this.oPos = this.domField.data( 'fpinitialpos' );
                                    this.cPos = fpCreatePositionFromData( this.domInput.val() );
                                    this.mediatype = this.domField.data( 'fpmediatype');
                                    this.domContainer.find('.focuspoint-panel-enabler').removeClass( 'hidden' );
                                    this.domPointer.removeClass( 'hidden' );
                                    var ct = this.domPreviewSelect.find('>ul').children().addClass('hidden').filter('[data-field~="'+name+'"]').removeClass('hidden');
                                    this.domPreviewSelect.toggleClass('hidden',ct.length==0);
                                    this.updateView();
                                    $('form .focuspoint-input-group button').removeClass( 'btn-info' );
                                    this.domField.find('button').addClass( 'btn-info' );
                                    if( this.mediatype > '' ) this.previewToggle( true );
                                    this.domBadge.text( this.mediatype );
                                    if( this.domSelect.length > 0 ) this.domSelect.get(0).value = name;
                                },
        set                 : function( pos ) {
                                    this.cPos = pos;
                                    this.domInput.val( pos.asData() );
                                    this.updateView();
                                },
        nudge               : function( x, y ) {
                                    if( this.cPos == null ) return;
                                    this.set( fpCreatePosition(this.cPos.x + x, this.cPos.y + y) );
                                },
        reset               : function() {
                                    this.cPos = this.oPos;
                                    this.domInput.val( this.domInput.data('fpinitial') );
                                    this.updateView();
                                },
        remove              : function() {
                                    this.cPos = fpCreatePosition( 50,50 );
                                    this.domInput.val( this.domInput.data('default') );
                                    this.updateView();
                                },
        startPreview        : function( mediatype ) {
                                    this.previewToggle( true );
                                    this.mediatype = mediatype;
                                    this.domBadge.text( mediatype );
                                    this.domField.data( 'fpmediatype', mediatype );
                                    this.setPreview();
                                },
        stopPreview         : function() {
                                    this.previewToggle( false );
                                    this.mediatype = '';
                                    this.domBadge.text( '' );
                                    this.domField.data( 'fpmediatype', '' );
                                },
    }

    // detect and prepare related input-fields, abort if none
    var fieldList = $('form .focuspoint-input-group');
    if( fieldList.length == 0 ) return null;
    fieldList.each( function() {
        var ich = $(this);
        ich.data( 'fpinitialpos', fpCreatePositionFromData( ich.find('input').data( 'fpinitial' ) ) );
        ich.data( 'fpmediatype', '' );
        ich.find('button').click( controller, function( event ) { event.preventDefault(); event.data.select( $(this) ); });
        ich.find('input').change( controller, function( event ) { event.preventDefault(); event.data.select( $(this) ); });
    });
    if( (fieldList.length > 1 ) && (fieldList.closest('.form-group').filter('.hidden').length > 0 ) )
    {
        controller.domSelect.on( 'change', function() {
            $('#rex-metainfo-' + $(this).val() ).closest('.input-group').find('button').click();
        });
    }

    // set event-handler
        container.find('button[data-button="zoom"]').click( function( event ) {
            event.preventDefault();
            controller.toggleZoomMode();
        });
    container.on('click', function( event ) {
            if( controller.zoomMode && event.target === this ) {
                controller.setZoomMode(false);
            }
        });
    $(document).on('keydown.focuspointZoom', function( event ) {
            if( controller.zoomMode && event.key === 'Escape' ) {
                controller.setZoomMode(false);
            }
        });
    controller.domImageContainer.mousemove( controller, function( event ) {
            event.data.setInfo( fpCreatePositionFromEvent( event ) );
        });
    controller.domImageContainer.mouseleave( controller, function( event ) {
            event.data.setInfo( event.data.cPos );
        });
    controller.domImageContainer.click( controller, function( event ) {
            event.data.set( fpCreatePositionFromEvent( event ) );
        });
    controller.domImageContainer.keydown( controller, function( event ) {
            var delta = event.shiftKey ? 2 : 0.5;
            switch (event.key) {
                case 'ArrowUp':
                    event.preventDefault();
                    event.data.nudge( 0, -delta );
                    break;
                case 'ArrowDown':
                    event.preventDefault();
                    event.data.nudge( 0, delta );
                    break;
                case 'ArrowLeft':
                    event.preventDefault();
                    event.data.nudge( -delta, 0 );
                    break;
                case 'ArrowRight':
                    event.preventDefault();
                    event.data.nudge( delta, 0 );
                    break;
            }
        });
    container.find('li[data-button="reset"]').click( controller, function( event ) {
            event.preventDefault();
            event.data.reset( );
        });
    container.find('li[data-button="remove"]').click( controller, function( event ) {
            event.preventDefault();
            event.data.remove( );
        });
    container.find('li[data-ref]').click( controller, function( event ) {
            event.preventDefault();
            event.data.startPreview( $(this).data('ref') );
        });
    controller.domPreviewOff.click( controller, function( event ) {
            event.preventDefault();
            event.data.stopPreview( );
        });

    // activate default-Field or (if missing) first in the list
    var currentField = fieldList.find('#rex-metainfo-med_focuspoint').closest('.focuspoint-input-group');
    if( currentField.length == 0 ) currentField = fieldList.first();
    controller.select( currentField );

    return controller;
}

function fpInitControllers( context )
{
    var root = context ? $(context) : $(document);
    root.find('.focuspoint-panel[data-mediafile]').each(function() {
        var panel = $(this);
        if (panel.data('fpInitialized')) {
            return;
        }
        var controller = fpCreateController( panel, panel.data('mediafile') );
        if (controller !== null) {
            panel.data('fpInitialized', true);
        }
    });
}

$(document).on('rex:ready', function( event, container ) {
    fpInitControllers(container);
});

$(function() {
    fpInitControllers(document);
});
