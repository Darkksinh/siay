<h2>Sirv Media Viewer for WooCommerce</h2>
<p class="sirv-options-desc">Image zoom, 360 spin and product videos to make your products look glorious. Replaces your existing media gallery with <a target="_blank" href="https://sirv.com/help/articles/sirv-media-viewer/">Sirv Media Gallery</a> on your product pages.</p>
<div class="sirv-optiontable-holder">
  <table class="sirv-woo-settings optiontable form-table">
    <?php
    echo Woo_options::render_options($profiles);
    ?>
    <tr>
      <th></th>
      <td><input type="submit" name="submit" class="button-primary sirv-save-settings" value="<?php _e('Save Settings') ?>" /></td>
    </tr>
  </table>
</div>
<h2>Cache settings</h2>
<div class="sirv-optiontable-holder">
  <table class="sirv-woo-settings optiontable form-table">
    <?php
    echo Woo_options::render_view_clean_cache();
    ?>
    <!-- <tr>
              <th></th>
              <td><input type="submit" name="submit" class="button-primary sirv-save-settings" value="<?php _e('Save Settings') ?>" /></td>
            </tr> -->
  </table>
</div>
