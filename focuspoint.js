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
                                    this.domPreviewImage.attr( 'src',
                                        location.pathname +
                                        location.search +
                                        '&rex-api-call=focuspoint&type=' + this.mediatype +
                                        '&file=' + this.mediafile +
                                        '&xy=' + this.cPos.asData()
                                    );
                                },
        setInfo             : function( pos ) {
                                    this.domInfo.html( pos.asInfo() );
                                },
        updateView          : function() {
                                    this.domPointer.css( this.cPos.asCss() );
                                    this.setInfo( this.cPos );
                                    this.setPreview();
                                },
        previewToggle           : function( toggle ) {
                                    this.domPreviewContainer.toggleClass( 'hidden', !toggle );
                                    this.domPreviewOff.toggleClass( 'hidden', !toggle );
                                },

        // Event-related functions
        select              : function( field ) {
                                    this.domField = field.closest( '.focuspoint-input-group' );
                                    this.domInput = this.domField.find('input');
                                    name = this.domInput.attr('name');
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
                                    if( this.domSelect.length > 0 ) this.domSelect.get(0).value = name;
                                },
        set                 : function( pos ) {
                                    this.cPos = pos;
                                    this.domInput.val( pos.asData() );
                                    this.updateView();
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
                                    this.domField.data( 'fpmediatype', mediatype );
                                    this.setPreview();
                                },
        stopPreview         : function() {
                                    this.previewToggle( false );
                                    this.mediatype = '';
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
        ich.find('button').click( controller, function( event ) { event.data.select( $(this) ); });
        ich.find('input').change( controller, function( event ) { event.data.select( $(this) ); });
    });
    if( (fieldList.length > 1 ) && (fieldList.closest('.form-group').filter('.hidden').length > 0 ) )
    {
        container.children('.focuspoint-panel-select').find('option').click( function( event ) {
            $('#rex-metainfo-' + $(this).attr('value') ).closest('.input-group').find('button').click();
        });
    }

    // set event-handler
    container.find('button[data-button="zoom"]').click( function() {
            $(this).closest('.col-sm-4').toggleClass('col-sm-12');
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
    container.find('li[data-button="reset"]').click( controller, function( event ) {
            event.data.reset( );
        });
    container.find('li[data-button="remove"]').click( controller, function( event ) {
            event.data.remove( );
        });
    container.find('li[data-ref]').click( controller, function( event ) {
            event.data.startPreview( $(this).data('ref') );
        });
    controller.domPreviewOff.click( controller, function( event ) {
            event.data.stopPreview( );
        });

    // activate default-Field or (if missing) first in the list
    var currentField = fieldList.find('#rex-metainfo-med_focuspoint').closest('.focuspoint-input-group');
    if( currentField.length == 0 ) currentField = fieldList.first();
    controller.select( currentField );

    return controller;
}
