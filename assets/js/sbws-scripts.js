/* global _sbWishlist, tb_click */

(function( $ ) {
    'use_strict';
    
    var sbws = {
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
                self.loadForm(self);
                self.runScripts(self);
                self.saveTabSettings();
                
                self.addStep();
                self.removeStep();
                self.addField();
                self.removeField();
                
                self.editFieldName();
                self.checkFieldOptions();
                
                self.checkSelectedOptions();
                self.saveSelectedOptions();
                
                self.addImageOption();
                self.saveImageOption();
                self.removeImageOption();
                self.addImageToField();
                
                self.savePlainText();
            });
        },
        runScripts: function(obj){
            if($('#sbws-form').length){
                const target = document.getElementById('sbws-form');
                const config = { attributes: false, childList: true, subtree: true };
                const callback = function(mutationsList, observer){
                    for(let mutation of mutationsList) {
                        if(mutation.target.className === "sbws-field-col" && mutation.type === 'childList'){
                            $('.field-options-select').select2({
                                width: 'resolve'
                            });
                        }
                        if(mutation.target.className === "step-inner-wrap" && mutation.type === 'childList'){

                            $('.step-inner-wrap').sortable({
                                appendTo: document.body,
                                handle: ".move-item-handle",
                                items: '.sbws-form-field',
                                placeholder: "sbws-form-field-placeholder",
                                forceHelperSize: true,
                                forcePlaceholderSize: true,
                                scroll: false, 
                                containment: "parent",
                                update: function(event, ui){
                                    var fields = $(this).children('.sbws-form-field');
                                    fields.each(function(){
                                       var el = $(this);
                                       el.attr('data-order', el.index());
                                       obj.updateField(el);
                                    });
                                }
                            });    
                        }
                        if(mutation.target.dataset.field === "text_editor"){
                            var editor = $(mutation.target).find('.sbws-text-editor');
                            console.log(editor.attr('id'));
                            wp.editor.initialize(editor.attr('id'), {
                                tinymce: {
                                    wpautop  : true,
                                    theme    : 'modern',
                                    skin     : 'lightgray',
                                    toolbar1 : 'bold,italic,strikethrough,underline,forecolor,alignleft,aligncenter,alignright,alignjustify,outdent,indent,undo,redo,spellchecker',
                                    toolbar2 : 'formatselect,bullist,numlist,blockquote,hr,link,unlink,charmap,removeformat'
                                }
                            });
                        }
                        
                    }
                }

                const observer = new MutationObserver(callback);
                observer.observe(target, config);
            }
        },
        saveTabSettings: function(){
            $('.btn-save-settings').on('click', function(e){
                e.preventDefault();
                var tab = $(this).data('tab'), request;
                switch(tab){
                    case 'variables':
                        var items = $('.sbws-variables-content tr'), i = 0, data = {};
                        _.each(items, function(el){
                            option_id = $(el).find('.field_option_id').val();
                            data[i] = {
                                option_id: option_id,
                                option_active: $(el).find('[name=item_active_' + option_id + ']').is(':checked') ? 1 : 0,
                                option_mandatory: $(el).find('[name=item_mandatory_' + option_id + ']').is(':checked') ? 1 : 0,
                                option_score: $(el).find('[name=item_score_' + option_id + ']').val()
                            }
                            i++;
                        });
                        break;
                    default:
                        console.log('Switch error!');
                }
                request = wp.ajax.post(_sbWishlist.action + '_save_settings',{
                    nonce: _sbWishlist.nonce,
                    tab: tab,
                    data: data
                });
                request.done(function(response){
                    console.log(response);
                    if(response.success){
                        alert('Options saved!');
                    }
                });
            });
        },
        addStep: function(){
            $('.sbws-form-add-step-button').on('click', function(e){
                e.preventDefault();
                var request;
                
                request = wp.ajax.post(_sbWishlist.action + '_add_step',{
                    nonce: _sbWishlist.nonce
                });
                
                request.done(function(response){
                    var item = wp.template( 'sbws-form-step' );
                    $('.swbs-wrap').append(item({
                        id : response.id
                    }));
                });  
            });
        },
        removeStep: function(){
            $('body').on('click', '.sbws-form-remove-step-button', function(e){
                e.preventDefault();
                var request, step = $(this).closest('.sbws-form-step');
                
                if(confirm("Are you sure you want to delete the step?")){
                    request = wp.ajax.post(_sbWishlist.action + '_remove_step',{
                        nonce: _sbWishlist.nonce,
                        id: step.data('id')
                    });
                    request.done(function(response){
                        step.remove();
                    }); 
                }
            });
        },
        
        addField: function(){
            var self = this;
            $('body').on('click', '.sbws-form-add-field-button', function(e){
                e.preventDefault();
                var request, fieldType = $(this).data('field'), stepId = $(this).closest('.sbws-form-step').data('id'), orderLast = $(this).closest('.sbws-form-step').find('.sbws-form-field:last-child').data('order'), order = 0;
                if(orderLast !== undefined ){
                    order = orderLast + 1;
                }
                request = wp.ajax.post(_sbWishlist.action + '_add_field',{
                    nonce: _sbWishlist.nonce,
                    field: fieldType,
                    step_id: stepId,
                    order: order
                });
                request.done(function(response){
                    var item = wp.template( 'sbws-form-field' );
                    $('.sbws-form-step[data-id='+ stepId +'] .step-placeholder-text').remove();
                    $('.sbws-form-step[data-id='+ stepId +'] .step-inner-wrap').append(item({
                        field_id : response.field_id,
                        step_id : stepId,
                        type : fieldType,
                        order : order,
                        category: 'product_cat'
                    }));
                    self.getFieldOptions('product_cat', response.field_id);
                });
                
            });
        },
        removeField: function(){
            $('body').on('click', '.sbws-form-remove-field-button', function(e){
                e.preventDefault();
                var request, field = $(this).closest('.sbws-form-field');
                
                if(confirm("Are you sure you want to delete the field?")){
                    request = wp.ajax.post(_sbWishlist.action + '_remove_field',{
                        nonce: _sbWishlist.nonce,
                        id: field.data('field_id')
                    });
                    request.done(function(response){
                        field.remove();
                    }); 
                }
            });
        },
        updateField: function(field){
            var request, data = { id: field.data('field_id'), name: field.find('.field-control').val(), order: field.data('order')};
            request = wp.ajax.post(_sbWishlist.action + '_update_field',{
               nonce: _sbWishlist.nonce,
               data: data
            });
        },
        
        loadForm: function(obj){
            $(window).on('load', function(){
                var request;
                
                request = wp.ajax.post(_sbWishlist.action + '_get_form',{
                    nonce: _sbWishlist.nonce
                });
                request.done(function(response){
                    var data = response.data,
                    item = wp.template( 'sbws-form-step' );
                    _.each(data, function(el){
                        $('.swbs-wrap').append(item({
                            id : el['option_id']
                        }));
                        obj.getFields(el['option_id']);
                    });                
                });
                
            });
        },
        getFields: function(stepId){
            var self = this, request;
            request = wp.ajax.post(_sbWishlist.action + '_get_field',{
                nonce: _sbWishlist.nonce,
                step_id: stepId
            });
            request.done(function(response){
                var data = response,
                item = wp.template( 'sbws-form-field' );
                _.each(data, function(el){
                    $('.sbws-form-step[data-id='+ stepId +'] .step-placeholder-text').remove();
                    $('.sbws-form-step[data-id='+ stepId +'] .step-inner-wrap').append(item({
                        field_id: el.meta_id,
                        step_id: el.form_id,
                        type: el.meta_type,
                        name: el.meta_name,
                        order: el.meta_order,
                        category: el.meta_category,
                        connected: el.meta_connected
                    }));
                    self.getFieldOptions(el.meta_category, el.meta_id);
                });
            });
        },
        editFieldName: function(){
            $('body').on('click', '.field-name .field-edit', function(e){
                e.preventDefault();
                if($(this).parent().hasClass('editing')){
                    var request, el = $(this), name = $(this).prev('.field-control').val(), id = $(this).closest('.sbws-form-field').data('field_id'), order = $(this).closest('.sbws-form-field').data('order');
                    var data = {
                        id: id,
                        name: name,
                        order: order
                    };
                    request = wp.ajax.post(_sbWishlist.action + '_update_field',{
                        nonce: _sbWishlist.nonce,
                        data: data
                    });
                    request.done(function(response){
                        el.prev('.field-control').prop('disabled', true);
                        el.parent().removeClass('editing');
                    });
                }else{
                    $(this).prev('.field-control').prop('disabled', false).focus();
                    $(this).parent().addClass('editing');
                }
            });
        },
        getFieldOptions: function(category, field_id, preload = true){
            var request, type = $('.sbws-form-field[data-field_id='+ field_id +']').data('type');
            request = wp.ajax.post(_sbWishlist.action + '_get_field_options',{
                nonce: _sbWishlist.nonce,
                data: {id: field_id, category: category}
            });
            request.done(function(response){
                if(type === "checkbox" || type === 'radiobox'){
                    var item = wp.template( 'sbws-form-field-options' );
                    $('.sbws-form-field[data-field_id='+ field_id +'] .sbws-field-col[data-field=variables]').html(item({
                        values: response.categories,
                        checked: response.checked
                    }));
                }else if(type === 'imagebox' && preload === true){
                    var item = wp.template( 'sbws-image-field' ), url;
                    _.each(response.images, function(el){
                        if(el.img_id !== '' && el.img_id !== undefined){
                            wp.media.attachment(el.img_id).fetch().then(function (data) {
                                url = data.url;
                                $('.sbws-form-field[data-field_id='+ field_id +'] .sbws-field-col[data-field=images] .image-options-wrapper').append(item({
                                    values: response.categories,
                                    image: el,
                                    image_url: url
                                })); 
                            });
                        }else{
                           $('.sbws-form-field[data-field_id='+ field_id +'] .sbws-field-col[data-field=images] .image-options-wrapper').append(item({
                                values: response.categories,
                                image: el
                            }));  
                        }
                        
                    });
                }else if(type === 'imagebox' && preload === false){
                    var item = wp.template( 'sbws-image-field' );
                    $('.sbws-form-field[data-field_id='+ field_id +'] .sbws-field-col[data-field=images] .image-options-wrapper').html(item({
                        values: response.categories,
                        image: response.images
                    })); 
                }else if(type === 'plain_text'){
                    var item = wp.template( 'sbws-text-field' );
                    $('.sbws-form-field[data-field_id='+ field_id +'] .sbws-field-col[data-field=text_editor]').html(item({
                        field_id: field_id,
                        text: response.text
                    })); 
                }else{
                    
                }
            });
        },
        updateFieldOptions: function(category, field_id){
            var self = this;
            var request, id = field_id;
            request = wp.ajax.post(_sbWishlist.action + '_update_field_options',{
               nonce: _sbWishlist.nonce,
               data: {id: id, category: category}
            });
            request.done(function(response){
                self.getFieldOptions(category, id, false);
            });
        },
        checkFieldOptions: function(){
            var self = this;
            $('body').on('change', '.form-check-input', function(e){
                e.preventDefault();
                var option = $(this).filter(':checked').val(), id = $(this).closest('.sbws-form-field').data('field_id');
                self.updateFieldOptions(option, id);
            });
        },
        checkSelectedOptions: function(){
            $('body').on('change', '.field-options-select', function(e){
                $(this).closest('.field-options').find('.field-options-select-save').show();
            });
        },
        saveSelectedOptions: function(){
            $('body').on('click','.field-options-select-save', function(e){
                e.preventDefault(); 
                var request, self = $(this), id = $(this).closest(".sbws-form-field").data("field_id"), values = $(this).closest('.sbws-field-col').find('.field-options-select').val();
                var type = $(this).closest('.sbws-field-col').find('.field-options-select').data('option');
                request = wp.ajax.post(_sbWishlist.action + '_save_selected_options', {
                    nonce: _sbWishlist.nonce,
                    data: {id: id, values: values, type: type}
                });
                request.done(function(response){
                    console.log(response);
                    self.hide(); 
                });
            });
        },
        
        addImageOption: function(){
            $('body').on('click', '.sbws-form-add-option-image', function(e){
                e.preventDefault();
                var request, field_id = $(this).closest('.sbws-form-field').data('field_id'), category = $(this).closest('.sbws-form-field').find('.form-check-input:checked').val();
                request = wp.ajax.post(_sbWishlist.action + '_add_image_options',{
                    nonce: _sbWishlist.nonce,
                    data: {id: field_id, category: category}
                });
                request.done(function(response){
                    var item = wp.template( 'sbws-image-field' );
                    $('.sbws-form-field[data-field_id='+ field_id +'] .sbws-field-col[data-field=images] .image-options-wrapper').append(item({
                        values: response.categories,
                        image: response.images
                    })); 
                });
                
            });
        },
        
        saveImageOption: function(){
            $('body').on('click', '.field-options-image-save', function(e){
                e.preventDefault(); 
                var request, self = $(this), container = self.closest('.field-options').find('.image-options-wrapper'), id = self.closest(".sbws-form-field").data("field_id"), data = [], category = self.closest('.sbws-form-field').find('.form-check-input:checked').val();
                _.each(container.children(), function(el){
                    data.push({
                        img_id: $(el).find('.sbws-image-id').val(),
                        title: $(el).find('.field-control').val(),
                        cat_id: $(el).find('.field-options-select').val()
                    });
               });
               request = wp.ajax.post(_sbWishlist.action + '_save_image_options',{
                    nonce: _sbWishlist.nonce,
                    id: id,
                    category: category,
                    data: data
                });
                request.done(function(response){
                });
            });
        },
        removeImageOption: function(){
            $('body').on('click', '.sbws-form-remove-image-option', function(e){
                e.preventDefault();
                var request, self = $(this), data = [], id = self.closest(".sbws-form-field").data("field_id"), container = $(this).closest('.field-options').find('.image-options-wrapper')
                if(confirm("Are you sure you want to delete the option?")){
                    self.closest('.image-option-field').remove();
                    _.each(container.children(), function(el){
                        data.push({
                            img_id: $(el).find('.sbws-image-id').val(),
                            title: $(el).find('.field-control').val(),
                            cat_id: $(el).find('.field-options-select').val()
                        });
                    });
                    request = wp.ajax.post(_sbWishlist.action + '_save_image_options',{
                        nonce: _sbWishlist.nonce,
                        id: id,
                        data: data
                    });
                    request.done(function(response){
                        console.log(response);
                    }); 
                }
            });
        },
        addImageToField: function(){
            $('body').on('click', '.sbws-add-image-button', function(e){
                e.preventDefault();
                var button = $(this), uploader = wp.media({
                    Title: 'Add image',
                    library: {
                        type: 'image'
                    },
                    multiple: false
                }).open();
                uploader.on('select', function(){
                    var attachment = uploader.state().get('selection').first().toJSON();
                    $(button).children('.image-preview').html('<img class="image" src="' + attachment.url + '" />');
                    $(button).children('.sbws-image-id').val(attachment.id);
                });
            });
        },
        savePlainText: function(){
            $('body').on('click', '.field-options-textbox-save', function(e){
                e.preventDefault();
                var textbox = $(this).closest('.field-options').find('.sbws-text-editor'), id = $(this).closest(".sbws-form-field").data("field_id"), request;
                text = wp.editor.getContent(textbox.attr('id'));
                request = wp.ajax.post(_sbWishlist.action + '_save_text_options',{
                    nonce: _sbWishlist.nonce,
                    id: id,
                    data: text
                });
                request.done(function(response){
                    console.log(response);
                }); 
            })
        }
    }
    sbws.init();
})( jQuery );
(function( $ ) {
    $('.field-options-select').select2({
        width: 'resolve'
    });
    $('.variables_field_active').on('change', function(e){
        e.preventDefault();
        if(!$(this).is(':checked')){
            $(this).closest('tr').find('.variables_field_score').prop('disabled','disabled');
            $(this).closest('tr').find('.variables_field_mandatory').prop('disabled','disabled');
        }else{
            if(!$(this).is(':checked'))
                $(this).closest('tr').find('.variables_field_score').prop('disabled','');
            $(this).closest('tr').find('.variables_field_mandatory').prop('disabled','');
        }
    });
    $('.variables_field_mandatory').on('change', function(e){
        e.preventDefault();
        if($(this).is(':checked')){
            $(this).closest('tr').find('.variables_field_score').prop('disabled','disabled');
        }else{
            $(this).closest('tr').find('.variables_field_score').prop('disabled','');
        }
    });
    $('.list-item-variations').hide();
    $('.sbws-wishlist').on('click', '.show-variations', function(e){
        e.preventDefault();
        $(this).closest('.list-item-inner').next('.list-item-variations').stop().slideToggle(500);
    });


    $('body').on('change', '.filter-customer', function(e){
        e.preventDefault();
       // alert( $(this).val() );

        var id = $(this).val();

        var request,
            data = {
                user_id: id
            };

        request = wp.ajax.post( _sbWishlist.action + '_change_filter_customer',{
            nonce: _sbWishlist.nonce,
            data: data
        });

        request.done( function (response ){

            $('.sbws-users-list').html( response.html );
        });


    });


})( jQuery );