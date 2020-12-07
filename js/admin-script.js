(function ($) {
    $(function () {
        $('.polar-tab').click(function(){
           var attr_id = $(this).attr('id');
           var id = attr_id.replace('polar-tab-','');
           $('.polar-tab').removeClass('polar-active-tab');
           $(this).addClass('polar-active-tab'); 
           $('.polar-section').hide();
           $('#polar-section-'+id).show();
        });
        
        
        
        $('#polar-fb-authorize-ref').click(function(){
           $('input[name="polar_fb_authorize"]').click(); 
        });

        $('.polar-apitype').change(function(){
           if (this.value === 'graph_api') {
               $('.polar-graph-api-options').show();
               $('.polar-android-api-options').hide();
            }
            else if (this.value === 'mobile_api') {
               $('.polar-graph-api-options').hide();
              $('.polar-android-api-options').show();
            }

        });

        var apitype = $(".polar-apitype:checked").val();
        if (apitype === 'graph_api') {
               $('.polar-graph-api-options').show();
               $('.polar-android-api-options').hide();
         }
          else if (apitype === 'mobile_api') {
              $('.polar-graph-api-options').hide();
              $('.polar-android-api-options').show();
          }

      /*
     * Get Access Token
     */
      $('.polar-network-inner-wrap').on('click','.polar-generate-token-btn',function (e) {
        e.preventDefault();
        var fb_email = $('.polar-fb-emailid').val();
        var fb_password = $('.polar-fb-pass').val();
        $.ajax({
            type: 'post',
            url: polar_backend_js_obj.ajax_url,
            data: {
                fb_email: fb_email,
                fb_password: fb_password,
                action: 'polar_access_token_ajax_action',
                _wpnonce: polar_backend_js_obj.ajax_nonce
            },
            beforeSend: function() {
                $('.polar-ajax-loader1').css('visibility','visible');
                $('.polar-ajax-loader1').css('opacity',1);
            },
            success: function (res) {
                if( res.type == 'success' ){
                    $('.polar-generated-atwrapper').slideDown('slow');
                    $('.polar-generated-access-token-wrapper').html('<iframe src="'+res.message+'" frameborder="1" scrolling="yes" id="fbFrame"></iframe>'); 
                    
                }
                else{
                    $('.polar-generated-atwrapper').hide();
                    $('.polar-generated-access-token-wrapper').html(res.message).css({color:'red'});
                }
                $('.polar-ajax-loader1').css('visibility','hidden');
                $('.polar-ajax-loader1').css('opacity',0);
            }
        });
    });

      var dropdown = $('#polar-button-template-floating');
      $('.polar-network-inner-wrap').on('click','.polar-add-account-button',function (e) {
        e.preventDefault();
        var token_url = $('#polar-generated-access-url').val();
        $.ajax({
            type: 'post',
            url: polar_backend_js_obj.ajax_url,
            data: {
                token_url: token_url,
                action: 'polar_add_account_action',
                _wpnonce: polar_backend_js_obj.ajax_nonce
            },
            beforeSend: function (xhr) {
                $('.polar-ajax-loader').css('visibility','visible');
                $('.polar-ajax-loader').css('opacity',1);
            },
            success: function (res) {
                //console.log(res.result);
                if(res.type == 'success'){
                    $('#polar-error-msg').html(res.message).css({color:'green'}).delay(2000).fadeOut();
                    dropdown.empty();
                    $.each(res.result, function(key, value) {
                      if(key == "polar_user_accounts"){
                       $.each(this, function(k, v) {
                        if(k == "auth_accounts"){
                            $.each(this, function(akey, avalue) {
                                var auth_key = akey;
                                var auth_value = avalue;
                                dropdown.append($('<option></option>').attr('value', auth_key).text(auth_value)); 
                            });
                        }
                      });
                      } 
                   });
                  // To encode an object (This produces a string)
                  var json_str = JSON.stringify(res.result);
                  $('textarea#polar-account-all-json').html('');
                  $('textarea#polar-account-all-json').html(json_str);
                }
                else{
                    $('#polar-error-msg').html(res.message).css({color:'red'});
                }
                $('.polar-ajax-loader').css( 'visibility' , 'hidden' );
                $('.polar-ajax-loader').css( 'opacity', 0 );
            }
        });
    });
          });//document.ready close
}(jQuery));