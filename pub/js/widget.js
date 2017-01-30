jQuery(function() {
    var ajaxurl = window.ajaxurl;

    jQuery(document).ready(function() {
        var forumLoading = jQuery('#w3tc_latest').find('div.inside:visible').find('.widget-loading');
        if (forumLoading.length) {
            var forumLoadingParent = forumLoading.parent();
            setTimeout(function() {
                forumLoadingParent.load(
                    ajaxurl + '?action=w3tc_widget_latest_ajax&_wpnonce=' +
                        jQuery(forumLoading).metadata().nonce,
                    function () {
                        forumLoadingParent.hide().slideDown('normal',
                            function() {
                                jQuery(this).css('display', '');
                            });
                    });
            }, 500);
        }
        var newsLoading = jQuery('#w3tc_latest_news').find('div.inside:visible').find('.widget-loading');
        if (newsLoading.length) {
            var newsLoadingParent = newsLoading.parent();
            setTimeout(function() {
                newsLoadingParent.load(
                    ajaxurl + '?action=w3tc_widget_latest_news_ajax&_wpnonce=' +
                        jQuery(newsLoading).metadata().nonce,
                    function () {
                        newsLoadingParent.hide().slideDown('normal',
                            function() {
                                jQuery(this).css('display', '');
                            });
                    });
            }, 500);
        }

        jQuery('.w3tc_generic_widgetservice_radio').click(function () {
            var o = jQuery(this);

            jQuery('#w3tc_generic_widgetservices_name').val(o.attr('data-name'));
            jQuery('#w3tc_generic_widgetservices_value').val(o.attr('data-value'));
            jQuery('#w3tc_generic_widgetservices_form_hash').val(o.attr('data-form_hash'));
        });

        jQuery('#buy-w3-service-cancel').live('click', function() {
            jQuery('input:radio[name=service]:checked').prop('checked', false);
            jQuery('#buy-w3-service-area').empty();
            jQuery('#buy-w3-service').attr("disabled", "disabled");
        });
        
        jQuery('#submit_purge_urls').click(function(){
                var data = {
                    'action': 'w3tc_action_purge_urls',
                    'purge_urls': jQuery('#purge_urls').val(),
                    'nonce': jQuery('#purge_urls').data('nonce')
                };

		jQuery.post(ajaxurl, data, function(response) {
			alert(response);
		});
                jQuery('#purge_urls').val('')
                return false;
        })
        
    });
});
