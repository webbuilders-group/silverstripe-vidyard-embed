<script type="text/javascript" id="vidyard_embed_code_{$VideoID.ATT}" src="//play.vidyard.com/{$VideoID.URLATT}.js?v=3.1.1&amp;type=<% if $UseLightbox %>lightbox<% else %>inline<% end_if %>"></script>

<% if $UseLightbox %>
    <div class="outer_vidyard_wrapper">
        <div class="vidyard_wrapper" onclick="fn_vidyard_{$FunctionVideoID.ATT}();">
            <img alt="$RawVideoData.title.ATT" width="$RawVideoData.width.ATT" src="//play.vidyard.com/{$VideoID.URLATT}.jpg?"/>
            <div class="vidyard_play_button">'.
                <a href="javascript:void(0);"></a>
            </div>
        </div>
    </div>
<% end_if %>