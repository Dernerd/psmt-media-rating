/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
jQuery(function ($) {

    url = PSMT_RATING.ajax_url, _nonce = PSMT_RATING._nonce;

    $('.psmt-media-rating').rateit({resetable: false});

    $('.psmt-media-rating').bind('rated', function (event, value) {

        var $this = $(this),
            media_id = $this.attr('data-media-id');

        $this.rateit('readonly', true);

        if (!PSMT_RATING.is_user_logged_in && psmt_media_rating_exists(media_id)) {
            console.log("Already rated! media");
            return false;
        }

        var data = {
            action: 'psmt_rate_media',
            media_id: media_id,
            _nonce: _nonce,
            rating: value
        };

        $.post(url, data, function (resp) {

            if (resp.type == 'error') {
                console.log(resp.message);
            } else if (resp.type == 'success') {
                console.log(resp.message);
                $($this).rateit('value', resp.message.average_rating);
                psmt_media_rating_store(media_id);
            }

        }, 'json');

    });

});

function psmt_media_rating_get_rated_medias() {

    var media_ids = jQuery.cookie('psmt_media_rated_medias') ? jQuery.cookie('psmt_media_rated_medias').split(',').map(function (i) {
        return parseInt(i, 10)
    }) : [];

    return media_ids;

}

function psmt_media_rating_exists(media_id) {

    var media_ids = psmt_media_rating_get_rated_medias();

    if (jQuery.inArray(parseInt(media_id, 10), media_ids) == -1) {
        return false;
    }

    return true;

}

function psmt_media_rating_store(media_id) {

    if (psmt_media_rating_exists(media_id)) {
        return false;
    }

    //alerady existing?
    var media_ids = psmt_media_rating_get_rated_medias();

    media_ids.push(media_id);

    jQuery.cookie('psmt_media_rated_medias', media_ids.join(','), {expires: 1});

}
