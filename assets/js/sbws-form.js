/* global _sbWishlist */

(function( $ ) {
    'use_strict';
    var sbws_form = {
        cache : {},
        init: function(){
            this.cacheElements();
            this.bindEvents();
        },
        cacheElements: function(){
            this.cache = {
                $window: $( window ),
                $document: $( document )
            };
        },
        bindEvents: function(){
            var self = this;
            self.cache.$document.on('ready', function(){
                self.loadForm();
                self.runScripts();
                
                self.nextStep();
                self.skipStep();
                
                self.submitForm();
            });
        },
        runScripts: function(){
            /*const target = document.getElementById('sbws-form');
            const config = { attributes: false, childList: true, subtree: true };
            const callback = function(mutationsList, observer){
                _.each(mutationsList, function(mutation){
                   $('.sbws-form').fadeIn(1000);
                });
            }
            const observer = new MutationObserver(callback);
            
            observer.observe(target, config);*/
            $('.field-options-select').select2({
                width: 'resolve'
            });
        },
        loadForm: function(){
            var self = this;
            $(window).on('load', function(){
                var request;
                
                request = wp.ajax.post( _sbWishlist.action + '_get_form',{
                    nonce: _sbWishlist.nonce
                });
                request.done(function( response ){
                    var data = response.data, count = data.length;
                    item = wp.template( 'sbws-form-step' );
                    _.each(data, function(el){
                        $('.sbws-form-wrap').append(item({
                            id : el['option_id']
                        }));
                        self.loadFields(el['option_id']);
                        if(count === 1){
                            $('.sbws-form-controls-points').prepend('<div class="point active" data-step_id="'+ count +'">' + count-- + '</div>');
                        }else{
                            $('.sbws-form-controls-points').prepend('<div class="point" data-step_id="'+ count +'">' + count-- + '</div>');
                        }
                    });       
                });
            });
        },
        loadFields: function(stepId){
            var request;
            request = wp.ajax.post( _sbWishlist.action + '_get_fields',{
                nonce: _sbWishlist.nonce,
                step_id: stepId
            });
            request.done(function(response){
                var data = response,
                item = wp.template( 'sbws-form-field' );
                _.each(data, function(el){
                    $('.sbws-form-step[data-id='+ stepId +'] .sbws-step-content').append(item({
                        field_id: el.meta_id,
                        step_id: el.form_id,
                        type: el.meta_type,
                        name: el.meta_name,
                        order: el.meta_order,
                        category: el.meta_category,
                        meta: el.meta_category_data
                    }));
                });
            });
        },
        nextStep: function(){
            $('body').on('click', '.btn-next', function(e){
                e.preventDefault();

                var points = $(this).closest('.sbws-form-controls').find('.sbws-form-controls-points'), 
                total = $(this).closest('.sbws-form-controls').find('.sbws-form-controls-points .point').length, 
                current = points.children('.point.active').data('step_id'),
                width = (100/(total-1))*current;
                checked = 1;
                step = $('.sbws-form-step[data-id="'+current+'"] .sbws-step-content');
                _.each(step.children(), function(el){
                    if($(el).data('type') != 'plain_text'){
                        elID = $(el).data('field_id');
                        if($('input[name="field_option_'+ elID +'[]"]:checked').length > 0 || $('input[name="field_option_'+ elID +'"]:checked').length > 0 || $('textarea[name="field_option_'+ elID +'"]').val() !== undefined){
                            checked = checked * 1;
                        }else{
                            checked = checked * 0; 
                        }
                    }
                });
                
                if(checked === 1 && current < total){
                    step.next('.alert-error').hide();
                    points.children('.point.active').removeClass('active').addClass('finished').next('').addClass('active');
                    points.children('.progress').css('width', width+'%');
                    pos = 100 * (current);
                    $('.sbws-form-wrap').css('transform', 'translateX(-'+pos+'%)');
                    if(current + 1 == total){
                        $(this).hide();
                        $(this).prev('.btn-skip').hide();
                        $('.sbws-form-wrap').submit();
                    }
                    if(current + 2 == total){
                        $(this).html('Done').attr('type','submit');
                        $(this).prev().attr('type','submit');
                    }
                }else{
                    step.next('.alert-error').show();
                }
            });
        },
        skipStep: function(){
            $('body').on('click', '.btn-skip', function(e){
                e.preventDefault();               
                var points = $(this).closest('.sbws-form-controls').find('.sbws-form-controls-points'), 
                total = $(this).closest('.sbws-form-controls').find('.sbws-form-controls-points .point').length, 
                current = points.children('.point.active').data('step_id'),
                width = (100/(total-1))*current;
                step = $('.sbws-form-step[data-id="'+current+'"] .sbws-step-content');
                if(current < total){
                    points.children('.point.active').removeClass('active').addClass('finished').next('').addClass('active');
                    points.children('.progress').css('width', width+'%');
                    pos = 100 * (current);
                    $('.sbws-form-wrap').css('transform', 'translateX(-'+pos+'%)');
                    if(current + 1 == total){
                        $(this).hide();
                        $(this).next('.btn-next').hide();
                        $('.sbws-form-wrap').submit();
                    }
                    if(current + 2 == total){
                        $(this).attr('type','submit');
                        $(this).next().html('Done').attr('type','submit');
                    }
                }
            });
        },
        submitForm: function(){
            $('.sbws-form-styling').on('submit', function(e){
                e.preventDefault();

                $('#save-profile span').text('Save process');
                 var request, data = $(this).serializeArray();

                var k;

                for ( k=0; k<data.length; k++ ) {
                    if ( data[k]['name'] === "field_option_7" ) {
                        if ( $('#field_id_7_1').is(':checked') ) {
                            data[k]['value'] = '1';
                        } else {
                            data[k]['value'] = '0';
                        }
                    }

                    if ( data[k]['name'] === "field_option_8" ) {
                        if ( $('#field_id_8_1').is(':checked') ) {
                            data[k]['value'] = '1';
                        } else {
                            data[k]['value'] = '0';
                        }
                    }
                }

                   //  console.log( data );

                 request = wp.ajax.post(_sbWishlist.action + '_submit_form',{
                     nonce: _sbWishlist.nonce,
                     data: data
                 });
                 request.done( function(response){

                     if( response.success ){
                         $('#save-profile span').text('Your styling saved!');
                         setTimeout( sayButton, 3000 );
                     }
                 });
            });
        }
    }
    sbws_form.init();


    function sayButton() {
        $('#save-profile span').text('Save profile');
    }

    $('body').on( 'click', '.product-dislike', function( e ) {
        var t = $(this);

        e.preventDefault();


        //console.log( t.data( 'product-id' ) );


        var product_id = t.data( 'product-id' );

        //alert( product_id );

        var request,
        data = {
              product_id: product_id
            };

        //  console.log( data );
        //request = wp.ajax.post(_sbWishlist.action + '_submit_form',{
        request = wp.ajax.post( _sbWishlist.action + '_product_dislike',{
            data: data
        });
        request.done( function(response){

            console.log( response );

            if( response.success ){
                t.addClass("done");

            }
        });


    } );


    $( document ).ready( function() {

        var getUrlParameter = function getUrlParameter(sParam) {
            var sPageURL = window.location.search.substring(1),
                sURLVariables = sPageURL.split('&'),
                sParameterName,
                i;

            for (i = 0; i < sURLVariables.length; i++) {
                sParameterName = sURLVariables[i].split('=');

                if (sParameterName[0] === sParam) {
                    return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
                }
            }
        };


        var size = getUrlParameter( 'size' );

        if ( size ) {

            $("#pa_storlek").val( size );

            //alert( 'size = ' + size);
            //$(".traffic-plan-" + plan ).trigger("click");
        }
    });


})( jQuery );