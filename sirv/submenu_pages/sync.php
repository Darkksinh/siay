<?php
  $storageInfo = sirv_getStorageInfo();
?>

<h2>Synchronization</h2>
<p class="sirv-options-desc">Copy your WordPress media library to Sirv, for supreme optimization and fast CDN delivery.</p>
<div class="sirv-optiontable-holder">
  <table class="optiontable form-table">
    <?php if (get_option('SIRV_ENABLE_CDN') != 1) { ?>
      <tr>
        <th class="no-padding" colspan="2">
          <div class="sirv-message warning-message">
            <span style="font-size: 15px;font-weight: 800;">Note:</span> <a class="sirv-show-account-tab">network status</a> is currently Disabled.
          </div>
        </th>
      </tr>
    <?php } ?>
    <tr>
      <th class="sirv-sync-messages no-padding" colspan="2">
        <?php if ($error) echo '<div id="sirv-sync-message" class="sirv-message error-message">' . $error . '</div>'; ?>
      </th>
    </tr>
    <tr>
      <td colspan="2">
        <h3>Status</h3>
        <p class="sirv-options-desc">Images are copied to Sirv the first time they are viewed, which can take 1-2 seconds per image. To perform a full synchronization now, click Sync images:</p>
      </td>
    </tr>
    <tr class="small-padding">
      <th colspan="2">
        <div class="sirv-sync-images-progress-block">
          <div class="sirv-progress">
            <div class="sirv-progress__text">
              <div class="sirv-progress__text--percents"><?php echo $cacheInfo['progress'] . '%'; ?></div>
              <div class="sirv-progress__text--complited"><span><?php echo $cacheInfo['q_s'] . ' out of ' . $cacheInfo['total_count_s']; ?></span> images completed</div>
            </div>
            <!-- <div class="sirv-progress__bar <?php if ($isSynced) echo 'sirv-failed-imgs-bar'; ?>"> -->
            <div class="sirv-progress__bar">
              <div class="sirv-progress__bar--line-complited sirv-complited" style="width: <?php echo $cacheInfo['progress_complited'] . '%;'; ?>"></div>
              <div class="sirv-progress__bar--line-queued sirv-queued" style="width: <?php echo $cacheInfo['progress_queued'] . '%;'; ?>"></div>
              <div class="sirv-progress__bar--line-failed sirv-failed" style="width: <?php echo $cacheInfo['progress_failed'] . '%;'; ?>"></div>
            </div>
          </div>
          <?php if (!$isMuted) { ?>
            <div class="sirv-sync-button-container">
              <input type="button" name="sirv-sync-images" class="button-primary sirv-initialize-sync" value="<?php echo $sync_button_text; ?>" <?php echo $is_sync_button_disabled; ?> />
            </div>
          <?php } ?>
        </div>
        <table class="sirv-progress-data">
          <tbody>
            <tr>
              <td>
                <div class="sirv-progress-data__label sirv-complited"></div>
              </td>
              <td>Synced images</td>
              <td>
                <div class="sirv-progress-data__complited--text"><?php echo $cacheInfo['q_s']; ?></div>&nbsp;&nbsp;&nbsp;
                <div class="sirv-progress-data__complited--size"><?php echo $cacheInfo['size_s']; ?></div>
              </td>
            </tr>
            <tr>
              <td>
                <div class="sirv-progress-data__label sirv-queued"></div>
              </td>
              <td>Queued images</td>
              <td>
                <div class="sirv-progress-data__queued--text"><?php echo $cacheInfo['queued_s']; ?></div>
              </td>
              <td></td>
            </tr>
            <tr>
              <td>
                <div class="sirv-progress-data__label sirv-failed"></div>
              </td>
              <td>Failed images</td>
              <td>
                <div class="sirv-progress-data__failed--text"><?php echo $cacheInfo['FAILED']['count_s']; ?></div>&nbsp;&nbsp;&nbsp;
                <div class="failed-images-block" style="<?php echo $is_show_failed_block; ?>">
                  <span class=" sirv-traffic-loading-ico" style="display: none;"></span><a href="#">Show</a>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </th>
    </tr>
    <tr class="sync-errors-wrap">
      <th colspan="2">
        <div class="sync-errors">
          <table class="optiontable form-table sirv-form-table">
            <thead>
              <tr>
                <td style="width: 65%;"><b>Error message</b></td>
                <td><b>Count</b></td>
                <td></td>
              </tr>
            </thead>
            <tbody class='sirv-fetch-errors'></tbody>
          </table>
        </div>
      </th>
    </tr>
    <?php if (!$isMuted) {
      $fetch_limit = isset($storageInfo['limits']['fetch:file']['limit']) ? $storageInfo['limits']['fetch:file']['limit'] : 2000;
    ?>
      <tr class="sirv-processing-message" style='display: none;'>
        <td colspan="2">
          <span class="sirv-traffic-loading-ico"></span><span class="sirv-queue">Processing (1/3): calculating folders...</span>
          <p style="margin: 10px 0 !important; font-weight: bold; color: #8a6d3b;">
            Keep this page open until synchronisation reaches 100%. Your account can sync <?php echo $fetch_limit; ?> images per hour (<a target="_blank" href="admin.php?page=sirv/sirv/submenu_pages/account.php">check current usage</a>).
            If sync stops, refresh this page and resume the sync.
            </?php>
        </td>
      </tr>
      <tr class='sirv-resync-block' style="<?php echo $is_show_resync_block; ?>">
        <td colspan="2">
          <span>
            <h2>Re-Synchronize</h2>
          </span>
        </td>
      </tr>
      <?php
      $g_disabled = $isGarbage ? '' : 'disabled';
      $g_checked = $isGarbage ? 'checked' : '';
      $g_show = $isGarbage ? '' : 'style="display: none;"';
      $g_dis_class = $isGarbage ? '' : 'sirv-dis-text';
      $f_disabled = $isFailed ? '' : 'disabled';
      $f_dis_class = $isFailed ? '' : 'sirv-dis-text';
      $f_checked = $isFailed ? 'checked' : '';
      $a_checked = !$isFailed ? 'checked' : '';
      ?>
      <tr class="sirv-discontinued-images" <?php echo $g_show; ?>>
        <td class="no-padding" colspan="2">
          <div class="sirv-message warning-message">
            <span style="font-size: 15px;font-weight: 800;">Recommendation:</span> <span class="sirv-old-cache-count"><?php echo $cacheInfo['garbage_count'] ?></span> images in plugin database no longer exist.&nbsp;&nbsp;
            <input type="button" name="optimize_cache" class="button-primary optimize-cache" value="Clean up" />&nbsp;
            <span class="sirv-traffic-loading-ico" style="display: none;"></span>
          </div>
        </td>
      </tr>
      <tr class="sirv-resync-block small-padding" style="<?php echo $is_show_resync_block; ?>">
        <td colspan="2">
          <!-- <label class="sirv-ec-garbage-item <?php echo $g_dis_class; ?>">
                <input type="radio" name="empty_cache" value="garbage" <?php echo $g_disabled . '' . $g_checked; ?>><b>Optimize</b> - clear cache of <abbr title="Images that no longer exist in your WordPress media library">discontinued</abbr> images (<span class="sirv-old-cache-count"><?php echo $cacheInfo['garbage_count'] ?></span> images).
              </label><br> -->
          <label class="sirv-ec-failed-item <?php echo $f_dis_class; ?>">
            <input type="radio" name="empty_cache" value="failed" <?php echo $f_disabled . ' ' . $f_checked; ?>><b>Failed</b> - clear cache of failed images.
          </label>
          <br>
          <label class="sirv-ec-all-item">
            <input type="radio" name="empty_cache" value="all" <?php echo $a_checked; ?>><b>All</b> - clear cache of all images.
          </label>
        </td>
      </tr>
      <tr class="sirv-resync-block sirv-resync-button-block" style="<?php echo $is_show_resync_block; ?>">
        <td>
          <input type="button" name="empty_cache" class="button-primary empty-cache" value="Empty cache" />&nbsp;
          <span class="sirv-traffic-loading-ico" style="display: none;"></span>
        </td>
      </tr>
    <?php } ?>
    <!-- <tr>
            <th></th>
            <td>
              <input type="button" name="tst" class="button-primary tst" value="Test" />
            </td>
          </tr> -->
  </table>
</div>
