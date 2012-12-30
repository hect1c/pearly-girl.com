<?php
/*
  $Id$
  osCommerce, Open Source E-Commerce Solutions
  [url="http://www.oscommerce.com"]http://www.oscommerce.com[/url]
  Copyright © 2010 osCommerce
  Released under the GNU General Public License
*/
  require('includes/application_top.php');

  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_INDEX);
  require(DIR_WS_INCLUDES . 'template_top_index.php');

?>
<h1><?php echo HEADING_TITLE; ?></h1>
<div class="contentContainer">
  <div class="contentText">
        <?php echo tep_customer_greeting(); ?>
  </div>
<?php
        if (tep_not_null(TEXT_MAIN)) {
?>
  <div class="contentText">
        <?php echo TEXT_MAIN; ?>
  </div>
<?php
        }
        include(DIR_WS_MODULES . FILENAME_NEW_PRODUCTS);
        include(DIR_WS_MODULES . FILENAME_UPCOMING_PRODUCTS);
?>
</div>
<?php

  require(DIR_WS_INCLUDES . 'template_bottom_index.php');
  require(DIR_WS_INCLUDES . 'application_bottom.php');
?>