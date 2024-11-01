<?php
/*
Plugin Name: WP-Venus
Plugin URI: http://www.mfd-consult.dk/wp-venus/
Description: Planet Venus cache syndication - http://www.intertwingly.net/code/venus/
Author: Morten Høybye Frederiksen
Author URI: http://www.wasab.dk/morten/
Version: 1.3
*/

/*

Copyright (c) 2006-2009 Morten Høybye Frederiksen <morten@wasab.dk>

Permission to use, copy, modify, and distribute this software for any
purpose with or without fee is hereby granted, provided that the above
copyright notice and this permission notice appear in all copies.

THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.

*/

function get_venus() {
  static $venus;
  if (!isset($venus))
    $venus = new Venus;
  return $venus;
}

class Venus {

  var $debug;
  var $do_update;
  var $wp_head_hooked;
  var $permalink;

  function Venus() {
    $this->debug=true;
    $this->update=false;
    $this->permalink=false;
    $this->wp_head_hooked=false;
  }

  function action_admin_head() {
    global $this_file, $link_id;
    if ('link-manager.php'!=$this_file)
      return;
    $options = get_settings('venus_options');
    if (!$options['link_category_mapping'])
      return;
    // Determine current settings
    $options = get_settings('venus_links');
    $options = @$options[$link_id];
    // Generate replace rows
    $replace_rows = '';
    if (is_array($options['replace'])) {
      foreach ($options['replace'] as $replace)
        $replace_rows .= "venus_add_row('venus_text_replace','" . htmlspecialchars($replace['in'], ENT_QUOTES) . "', '" . htmlspecialchars($replace['out'], ENT_QUOTES) . "', '" . htmlspecialchars($replace['field'], ENT_QUOTES) . "'); ";
    }
    // Generate mapping rows
    $mapping_rows = '';
    if (is_array($options['mapping'])) {
      foreach ($options['mapping'] as $mapping)
        $mapping_rows .= "venus_add_row('venus_category_mapping','" . htmlspecialchars($mapping['in'], ENT_QUOTES) . "', '" . htmlspecialchars($mapping['out'], ENT_QUOTES) . "'); ";
    }
    // Generate adding rows
    $adding_rows = '';
    if (is_array($options['adding'])) {
      foreach ($options['adding'] as $adding)
        $adding_rows .= "venus_add_row('venus_category_adding','" . htmlspecialchars($adding['in'], ENT_QUOTES) . "', '" . htmlspecialchars($adding['out'], ENT_QUOTES) . "'); ";
    }
    // Output script
    print "<!-- ";
    print_r($options);
    print "-->\n";
    print str_replace('
', ' ', '
<style type="text/css">
#venus_text_replace a:hover { cursor: pointer; }
#venus_category_mapping a:hover { cursor: pointer; }
#venus_category_adding a:hover { cursor: pointer; }
</style>
<script type="text/javascript">
function venus_add_row(table,invalue,outvalue,field) {
  cmt = document.getElementById(table);
  cmt = cmt.lastChild;
  row = document.createElement("tr");
  row.id = table + "_" + cmt.childNodes.length;
  cell = document.createElement("td");
  cell.innerHTML = \' \';
  row.appendChild(cell);
  if (table == "venus_text_replace") {
    cell = document.createElement("td");
    select = \'<select name="\' + table + \'_field[]" style="width: 100%">\';
    fields = [\'All\', \'Title\', \'Content\', \'Summary\'];
    for (i=0; i<fields.length; i++) {
      select = select + \'<option value="\' + fields[i] + \'"\';
      if (field == fields[i]) {
        select = select + \'selected="selected" \';
      }
      select = select + \'>\' + fields[i] + \'</option>\';
    }
    select = select + \'</select>\';
    cell.innerHTML = select;
    row.appendChild(cell);
  }
  cell = document.createElement("td");
  cell.innerHTML = \'<input name="\' + table + \'_in[]" type="text" style="width: 100%" value="\' + invalue + \'"/>\';
  row.appendChild(cell);
  cell = document.createElement("td");
  cell.innerHTML = \'<input name="\' + table + \'_out[]" type="text" style="width: 100%" value="\' + outvalue + \'"/>\';
  row.appendChild(cell);
  cell = document.createElement("td");
  cell.innerHTML = \'<a style="cursor: hand" onclick="venus_remove_row(\\\'\' + table + \'\\\', \\\'\' + row.id + \'\\\')">Remove</a>\';
  row.appendChild(cell);
  add = document.getElementById(table + "_add");
  cmt.insertBefore(row, add);
}
function venus_remove_row(table,row) {
  cmt = document.getElementById(table);
  cmt = cmt.lastChild;
  row = document.getElementById(row);
  cmt.removeChild(row);
}
addLoadEvent(function(){
if (!(document.getElementById("addlink") || document.getElementById("editlink")))
  return;
adv = document.getElementById("advancedstuff");
if (!adv && !(adv = document.getElementById("linkadvanceddiv")))
  return;
venus = document.createElement("fieldset");
venus.id = "wp-venus";
venus.setAttribute("class", "dbx-box");
venus.innerHTML = \'
<h3 class="dbx-handle">Venus Source Settings</h3>
<div class="dbx-content">
<table id="venus_text_replace" class="editform" width="100%" cellspacing="2" cellpadding="5"><thead></thead><tbody>
  <tr>
    <th width="20%" scope="row">Skip entries matching:</th>
    <td width="80%" colspan="4"><input type="text" id="venus_skip_match" name="venus_skip_match" size="20" value="'.htmlspecialchars(@$options['skip_match']).'" style="width: 98%" /></td>
  </tr>
  <tr>
    <th width="20%" scope="row">Skip consecutive entries with duplicate:</th>
    <td><input name="venus_skip_dupe_title" id="venus_skip_dupe_title" value="1" type="checkbox" ' . (@$options['skip_dupe_title']?'checked="checked" ':'') . '/><label for="venus_skip_dupe_title"> Title</label></td>
    <td><input name="venus_skip_dupe_content" id="venus_skip_dupe_content" value="1" type="checkbox" ' . (@$options['skip_dupe_content']?'checked="checked" ':'') . '/><label for="venus_skip_dupe_content"> Content</label></td>
    <td><input name="venus_skip_dupe_summary" id="venus_skip_dupe_summary" value="1" type="checkbox" ' . (@$options['skip_dupe_summary']?'checked="checked" ':'') . '/><label for="venus_skip_dupe_summary"> Summary</label></td>
  </tr>
  <tr>
    <th width="20%" scope="row">Time stamp:</th>
    <td width="80%" colspan="4"><input name="venus_use_published_date" id="venus_use_published_date" value="1" type="checkbox" ' . (@$options['use_published_date']?'checked="checked" ':'') . '/><label for="venus_use_published_date"> Use published date, not modification date</label></td>
  </tr>
  <tr>
    <th width="20%" scope="row">Approval:</th>
    <td width="80%" colspan="4"><input name="venus_hold_for_review" id="venus_hold_for_review" value="1" type="checkbox" ' . (@$options['hold_for_review']?'checked="checked" ':'') . '/><label for="venus_hold_for_review"> Hold for review, do not publish</label></td>
  </tr>
  <tr>
    <th width="20%" scope="row">Text Replacement:</th>
    <th style="text-align: center" width="20%" scope="col">In the field...</th>
    <th style="text-align: center" width="27%" scope="col">... match the text...</th>
    <th style="text-align: center" width="27%" scope="col">... and replace with</th>
    <th style="text-align: center" width="6%" scope="col">Action</th>
  </tr>
  <tr id="venus_text_replace_add">
    <td width="20%"> </td>
    <td style="text-align: center" colspan="3" width="80%"><a style="cursor: hand" onclick="venus_add_row(\\\'venus_text_replace\\\', \\\'\\\', \\\'\\\');">Add row...</a></td>
  </tr>
</tbody></table>
<table id="venus_category_mapping" class="editform" width="100%" cellspacing="2" cellpadding="5"><thead></thead><tbody>
  <tr>
    <th width="20%" scope="row">Default Category:</th>
    <td colspan="3" width="80%">' . str_replace("\n", ' ', str_replace("'", '"', wp_dropdown_categories('echo=0&hierarchical=1&hide_empty=0&name=venus_default_category&orderby=name&show_option_all=None&selected=' . @$options['default_category']))) . '</td>
  </tr>
    <th width="20%" scope="row">Default Category Parent:</th>
    <td colspan="3" width="80%">' . str_replace("\n", ' ', str_replace("'", '"', wp_dropdown_categories('echo=0&hierarchical=1&hide_empty=0&name=venus_default_category_parent&orderby=name&show_option_all=None&selected=' . @$options['default_category_parent']))) . '</td>
  </tr>
  <tr>
    <th width="20%" scope="row">Category Split:</th>
    <td colspan="3" width="80%"><input name="venus_category_split" id="venus_category_split" value="1" type="checkbox" ' . (@$options['category_split']?'checked="checked" ':'') . '/><label for="venus_category_split"> (Split incoming categories by white space)</label></td>
  </tr>
  <tr>
    <th width="20%" scope="row">Category Mapping:</th>
    <th style="text-align: center" width="37%" scope="col">For incoming category matching...</th>
    <th style="text-align: center" width="37%" scope="col">... use this category</th>
    <th style="text-align: center" width="6%" scope="col">Action</th>
  </tr>
  <tr id="venus_category_mapping_add">
    <td width="20%"> </td>
    <td style="text-align: center" colspan="2" width="80%"><a style="cursor: hand" onclick="venus_add_row(\\\'venus_category_mapping\\\', \\\'\\\', \\\'\\\');">Add row...</a></td>
  </tr>
</tbody></table>
<table id="venus_category_adding" class="editform" width="100%" cellspacing="2" cellpadding="5"><thead></thead><tbody>
  <tr>
    <th width="20%" scope="row">Category Adding:</th>
    <th style="text-align: center" width="37%" scope="col">For incoming title/content matching...</th>
    <th style="text-align: center" width="37%" scope="col">... add this category</th>
    <th style="text-align: center" width="6%" scope="col">Action</th>
  </tr>
  <tr id="venus_category_adding_add">
    <td width="20%"> </td>
    <td style="text-align: center" colspan="2" width="80%"><a style="cursor: hand" onclick="venus_add_row(\\\'venus_category_adding\\\', \\\'\\\', \\\'\\\');">Add row...</a></td>
  </tr>
</tbody></table>
</div>\';
adv.insertBefore(venus, adv.firstChild);
' . $replace_rows . '
' . $mapping_rows . '
' . $adding_rows . '
venus_add_row(\'venus_text_replace\', \'\',\'\');
venus_add_row(\'venus_category_mapping\', \'\',\'\');
venus_add_row(\'venus_category_adding\', \'\',\'\');
});
</script>');
  }

  function action_edit_link($link_id) {
    $options = get_settings('venus_links');
    if (!is_array($options))
      $options = array();
    $options[$link_id] = array(
        'skip_match' => @$_REQUEST['venus_skip_match'],
        'replace' => array(),
        'default_category' => @$_REQUEST['venus_default_category'],
        'default_category_parent' => @$_REQUEST['venus_default_category_parent'],
        'category_split' => @$_REQUEST['venus_category_split']?1:0,
        'skip_dupe_title' => @$_REQUEST['venus_skip_dupe_title']?1:0,
        'skip_dupe_content' => @$_REQUEST['venus_skip_dupe_content']?1:0,
        'skip_dupe_summary' => @$_REQUEST['venus_skip_dupe_summary']?1:0,
        'use_published_date' => @$_REQUEST['venus_use_published_date']?1:0,
        'hold_for_review' => @$_REQUEST['venus_hold_for_review']?1:0,
        'mapping' => array(),
        'adding' => array());
    foreach (@$_REQUEST['venus_category_adding_in'] as $k => $in) {
      if (!empty($in))
        $options[$link_id]['adding'][] = array('in' => $in, 'out' => $_REQUEST['venus_category_adding_out'][$k]);
    }
    foreach (@$_REQUEST['venus_category_mapping_in'] as $k => $in) {
      if (!empty($in))
        $options[$link_id]['mapping'][] = array('in' => $in, 'out' => $_REQUEST['venus_category_mapping_out'][$k]);
    }
    foreach (@$_REQUEST['venus_text_replace_in'] as $k => $in) {
      if (!empty($in))
        $options[$link_id]['replace'][] = array(
            'field' => $_REQUEST['venus_text_replace_field'][$k], 
            'in' => $in,
            'out' => $_REQUEST['venus_text_replace_out'][$k]);
    }
    update_option('venus_links', $options);
  }

  function action_admin_menu() {
    if (!current_user_can('edit_plugins') || function_exists('is_site_admin') && !is_site_admin())
      return;
    add_submenu_page("options-general.php", $this->T('Venus'),
                     $this->T('Venus'),
                     8, basename(__FILE__), array(&$this, 'options_venus'));
  }

  function action_delete_post($post_id) {
    $options = get_settings('venus_options');
    if (!$options['remove_on_delete'])
      return;
    list($filename) = get_post_custom_values('venus_cache_file', $post_id);
    $this->remove_file($filename, $post_id);
    $deletes = get_settings('venus_deletes');
    if (!is_array($deletes))
      $deletes = array();
    $deletes[$filename] = 1;
    update_option('venus_deletes', $deletes);
  }

  function remove_file($filename, $post_id=0) {
    $options = get_settings('venus_options');
    $path = $options['cache_directory'] . DIRECTORY_SEPARATOR . $filename;
    if (@unlink($path)) {
      if ($this && $this->debug)
        $this->log('Removed file ' . ($post_id?'for post #'.$post_id.': ':'') . $filename);
    } else {
      if ($this && $this->debug)
        $this->log('Oops, unable to remove file ' . ($post_id?'for post #'.$post_id.': ':'') . $filename);
    }
  }

  function options_venus() {
    $this->options_venus_checkaction();
    $this->options_venus_display();
  }
  
  function options_venus_checkaction() {
    global $wpdb;
    if (!current_user_can('edit_plugins') || function_exists('is_site_admin') && !is_site_admin())
      return;
    switch ($_POST['action']) {
      case 'do_update':
        /* perform forced update run */
        $count = $this->update(true);
        $this->display_updated('Done updating from cache, '.$count.' new entries were added.');
        break;
      case 'update_options':
        /* update options */
        $options = get_settings('venus_options');
        foreach (array('cache_directory', 'default_user_role', 'update_interval', 'default_category_parent', 'default_category_assignment') as $k)
          $options[$k]=$_REQUEST[$k];
        foreach (array('tag_with_source_url', 'tag_with_source_title', 'tag_with_link_title', 'tag_with_link_categories', 'check_entry_size', 'never_update_entries', 'remove_on_delete', 'remove_on_skip', 'original_link', 'link_category_mapping') as $k)
          $options[$k]=@$_REQUEST[$k]?1:0;
        $options['last_update']=0;
        update_option('venus_options', $options);
        if ($this->debug)
          $this->log('updated options.');
        $this->display_updated('Options updated.');
        break;
      case 'consolidate_users':
        /* merge two users into one */
        if (!(current_user_can('delete_users') || !function_exists('wpmu_delete_user'))) {
          $this->display_updated('Sorry, you do not have access to user consolidation.');
          break;
        }
        $user1=$_REQUEST['user1'];
        $user2=$_REQUEST['user2'];
        if (!$user1 || !$user2 || $user1==$user2 || $user2==1) {
          $this->display_updated('Two unique users must be selected for consolidation.');
          break;
        }
        # Validate WPMU operation, user1 must be able to receive posts from user2.
        if (function_exists('wpmu_delete_user')) {
          # Which blogs does user2 belong to?
          $blogs=get_blogs_of_user($user2);
          if (!sizeof($blogs)) {
            $this->display_updated('User 2 does not have any blogs, unable to consolidate');
            break;
          }
          # Make sure user1 belongs to the same blogs.
          $ok=true;
          foreach ($blogs as $blog => $info) {
            if (!is_user_member_of_blog($user1, $blog)) {
              $this->display_updated('User 1 does not belong to blog '.$blog.', unable to consolidate');
              $ok=false;
            }
          }
          if (!$ok)
            break;
        }
        # Move Venus ID(s) for user2 to user1.
        $wpdb->query('UPDATE ' . $wpdb->usermeta . ' SET user_id="' . $wpdb->escape($user1) . '" WHERE meta_key="venus_id" AND user_id="' . $wpdb->escape($user2) . '"');
        # Delete user.
        if (function_exists('wpmu_delete_user')) {
          # WPMU: Remove user2 after moving posts to user1 for each blog.
          foreach ($blogs as $blog => $info) {
            if ($this->debug)
              $this->log('removing user #'.$user2.' from blog #'.$blog);
            switch_to_blog($blog);
            wp_delete_user($user2, $user1);
            restore_current_blog();
          }
          wpmu_delete_user($user2);
        } else {
          # WP: Remove user2, moving posts to user1.
          wp_delete_user($user2, $user1);
        }
        if ($this->debug)
          $this->log('consolidated user #'.$user2.' into user #'.$user1);
        $this->display_updated('Users merged.');
        break;
      case 'consolidate_categories':
        /* merge two categories into one */
        if (!(current_user_can('manage_categories') || !function_exists('get_objects_in_term'))) {
          $this->display_updated('Sorry, you do not have access to category consolidation.');
          break;
        }
        /* validate */
        $cat1=$_REQUEST['category1'];
        $cat2=$_REQUEST['category2'];
        if (!$cat1 || !$cat2 || $cat1==$cat2) {
          $this->display_updated('Two unique categories must be selected for consolidation.');
          break;
        }
        if (function_exists('get_category_children')) {
          if (strlen(get_category_children($cat2))>1) {
            $this->display_updated('Cannot consolidate category with children.');
            break;
          }
        }
        /* assign category1 to objects in category2 */
        $cat1term = get_term($cat1, 'category');
        $objects = get_objects_in_term($cat2, 'category');
        foreach ($objects as $object_id)
          wp_set_object_terms($object_id, $cat1term->name, 'category', true);
        /* delete category2 */
        wp_delete_term($cat2, 'category');
        if ($this->debug)
          $this->log('consolidated category #'.$cat2.' into category #'.$cat1);
        $this->display_updated('Categories merged ('.sizeof($objects).' objects were affected).');
        $merges = get_settings('venus_merges');
        if (!is_array($merges))
          $merges = array();
        $merges[$cat2] = $cat1;
        update_option('venus_merges', $merges);
        if (function_exists('wp_update_term_count')) {
          $taxonomies = get_object_taxonomies('post');
          foreach ($taxonomies as $taxonomy) {
            $terms = get_terms($taxonomy);
            wp_update_term_count($terms, $taxonomy);
          }
        }
        break;
    }
    if (function_exists('wpmu_delete_user'))
      restore_current_blog();
  }
  
  function options_venus_display() {
    /* Display options page */
    global $wpdb, $wp_roles;
    if (!current_user_can('edit_plugins') || function_exists('is_site_admin') && !is_site_admin())
      return;
    $options = get_settings('venus_options');
    ?>
    <div class="wrap">
      <h2><?php $this->EHT('Venus Options') ?></h2>
      <?php
        if ($this->debug && file_exists(ABSPATH . 'wp-content/venus.log')) {
          print '<p>Debug is enabled, <a href="'.get_bloginfo('home').'/wp-content/venus.log">view log</a>.</p>';
        }
      ?>
      <form method="post" action="">
        <fieldset class="options">
          <input type="hidden" name="action" value="do_update" />
          <div class="submit"><input type="submit" value="<?php
                                   echo $this->EHT('Force update...') ?> &raquo;" /></div>
        </fieldset>
      </form>
      <form method="post" action="">
        <input type="hidden" name="action" value="update_options" />
        <fieldset class="options">
        <legend><?php $this->EHT('General options') ?></legend>
        <table class="editform">
          <tr>
            <td><?php echo $this->EHT("Absolute path to Venus cache directory").": " ?></td>
            <td><input name="cache_directory" value="<?php echo $this->H($options['cache_directory']) ?>"
                       type="text" size="40" /></td>
          </tr><tr>
<?php if ($this->wp_head_hooked) : ?>
            <td><?php echo $this->EHT("Minimum update interval (minutes)").": " ?></td>
            <td><input name="update_interval" value="<?php echo $this->H($options['update_interval']) ?>"
                       title="<?php echo strftime('%a, %d %b %Y %H:%M:%S', $options['last_update']); ?>" type="text" size="5" /></td>
          </tr><tr>
<?php endif; ?>
            <td><?php echo $this->EHT("Default role for new users").": " ?></td>
            <td><select name="default_user_role"><?php 
              foreach ($wp_roles->role_names as $role => $name)
                echo '<option value="'.$role.'" '.($options['default_user_role']==$role?' selected="selected"':'').'>'.$name.'</option>';
            ?></select></td>
          </tr><tr>
<?php if (function_exists('wp_dropdown_categories')) : ?>
            <td><?php echo $this->EHT("Default parent for new categories").": " ?></td>
            <td><?php wp_dropdown_categories('hierarchical=1&hide_empty=0&name=default_category_parent&orderby=name&show_option_all=No parent&selected=' . $options['default_category_parent']); ?></td>
          </tr><tr>
            <td><?php echo $this->EHT("Default category assignment").": " ?></td>
            <td><?php wp_dropdown_categories('hierarchical=1&hide_empty=0&name=default_category_assignment&orderby=name&show_option_all=None&selected=' . $options['default_category_assignment']); ?></td>
          </tr><tr>
<?php endif; ?>
            <td><?php echo $this->EHT("Use source link settings?").": " ?></td>
            <td><input name="link_category_mapping" value="1" <?php if (!function_exists('get_bookmark') || !function_exists('wp_get_link_cats')) echo 'disabled="disabled" '; ?>
                       type="checkbox" <?php if (1==$options['link_category_mapping']) echo 'checked="true" '; ?>/></td>
          </tr><tr>
            <td><?php echo $this->EHT("Tag with source URL?").": " ?></td>
            <td><input name="tag_with_source_url" value="1" <?php if (!function_exists('get_tags')) echo 'disabled="disabled" '; ?>
                       type="checkbox" <?php if (1==$options['tag_with_source_url']) echo 'checked="true" '; ?>/></td>
          </tr><tr>
            <td><?php echo $this->EHT("Tag with source title?").": " ?></td>
            <td><input name="tag_with_source_title" value="1" <?php if (!function_exists('get_tags')) echo 'disabled="disabled" '; ?>
                       type="checkbox" <?php if (1==$options['tag_with_source_title']) echo 'checked="true" '; ?>/></td>
          </tr><tr>
            <td><?php echo $this->EHT("Tag with source link title?").": " ?></td>
            <td><input name="tag_with_link_title" value="1" <?php if (!function_exists('get_tags')) echo 'disabled="disabled" '; ?>
                       type="checkbox" <?php if (1==$options['tag_with_link_title']) echo 'checked="true" '; ?>/></td>
          </tr><tr>
              <td><?php echo $this->EHT("Tag with source link categories?").": " ?></td>
              <td><input name="tag_with_link_categories" value="1" <?php if (!function_exists('get_tags')) echo 'disabled="disabled" '; ?>
                         type="checkbox" <?php if (1==$options['tag_with_link_categories']) echo 'checked="true" '; ?>/></td>
          </tr><tr>
            <td><?php echo $this->EHT("Check entry size?").": " ?></td>
            <td><input name="check_entry_size" value="1"
                       type="checkbox" <?php if (1==$options['check_entry_size']) echo 'checked="true" '; ?>/></td>
          </tr><tr>
            <td><?php echo $this->EHT("Never update entries?").": " ?></td>
            <td><input name="never_update_entries" value="1"
                       type="checkbox" <?php if (1==$options['never_update_entries']) echo 'checked="true" '; ?>/></td>
          </tr><tr>
            <td><?php echo $this->EHT("Remove file on post delete?").": " ?></td>
            <td><input name="remove_on_delete" value="1"
                        type="checkbox" <?php if (1==$options['remove_on_delete']) echo 'checked="true" '; ?>/></td>
          </tr><tr>
            <td><?php echo $this->EHT("Remove file for skipped post?").": " ?></td>
            <td><input name="remove_on_skip" value="1"
                        type="checkbox" <?php if (1==$options['remove_on_skip']) echo 'checked="true" '; ?>/></td>
          </tr><tr>
            <td><?php echo $this->EHT("Use original link to post?").": " ?></td>
            <td><input name="original_link" value="1"
                       type="checkbox" <?php if (1==$options['original_link']) echo 'checked="true" '; ?>/></td>
          </tr>
        </table>
        <div class="submit"><input type="submit" value="<?php
                                   echo $this->EHT('Update options') ?> &raquo;" /></div>
        </fieldset>
      </form>
      <?php
        $users=$wpdb->get_results('SELECT u.ID, display_name, replace(user_url, "http://", "") as user_url, user_email, count(p.post_author) as post_count FROM '.$wpdb->users.' AS u LEFT JOIN '.$wpdb->posts.' AS p ON u.ID=p.post_author WHERE u.ID>1 GROUP BY u.ID ORDER BY display_name');
        if (sizeof($users)<=1)
          print '<!-- no venus users found -->';
        if ((sizeof($users)>1) && (current_user_can('delete_users') || !function_exists('wpmu_delete_user'))) {
      ?>
      <form method="post" action="">
        <input type="hidden" name="action" value="consolidate_users" />
        <fieldset class="options">
        <legend><?php $this->EHT('Consolidate users (merge User 2 into User 1)') ?></legend>
        <table class="editform">
          <tr>
            <th style="text-align: left"><?php echo $this->EHT("User 1 (keep)").": " ?></th>
          </tr><tr>
            <td>
              <select name="user1">
                <option value="0"><?php echo $this->EHT("Select...") ?></option>
                <?php
                  foreach ($users as $user)
                    print '<option value="'.$user->ID.'">'.$this->H($user->display_name).($user->user_email!=''?', '.$this->H($user->user_email):'').' (ID: '.$this->H($user->ID).', posts: '.$this->H($user->post_count).(!empty($user->user_url)?', '.$this->H($user->user_url):'').')</option>';
                ?>

              </select>
            </td>
          </tr><tr>
            <th style="text-align: left"><?php echo $this->EHT("User 2 (delete)").": " ?></th>
          </tr><tr>
            <td>
              <select name="user2">
                <option value="0"><?php echo $this->EHT("Select...") ?></option>
                <?php
                  foreach ($users as $user)
                    print '<option value="'.$user->ID.'">'.$this->H($user->display_name).($user->user_email!=''?', '.$this->H($user->user_email):'').' (ID: '.$this->H($user->ID).', posts: '.$this->H($user->post_count).(!empty($user->user_url)?', '.$this->H($user->user_url):'').')</option>';
                ?>

              </select>
            </td>
          </tr>
        </table>
        <div class="submit"><input type="submit" value="<?php
                                   echo $this->EHT('Consolidate') ?> &raquo;" /></div>
        </fieldset>
      </form>
      <?php
        }
      ?>
<?php if (function_exists('wp_dropdown_categories')) { ?>
      <form method="post" action="">
        <input type="hidden" name="action" value="consolidate_categories" />
        <fieldset class="options">
        <legend><?php $this->EHT('Consolidate categories (merge Category 2 into Category 1)') ?></legend>
        <table class="editform">
          <tr>
            <th style="text-align: left"><?php echo $this->EHT("Category 1 (keep)").": " ?></th>
          </tr><tr>
            <td>
              <?php wp_dropdown_categories('echo=1&hierarchical=1&hide_empty=0&name=category1&orderby=name&show_option_all=Select...'); ?>
            </td>
          </tr><tr>
            <th style="text-align: left"><?php echo $this->EHT("Category 2 (delete)").": " ?></th>
          </tr><tr>
            <td>
              <?php wp_dropdown_categories('echo=1&hierarchical=1&hide_empty=0&name=category2&orderby=name&show_option_all=Select...'); ?>
            </td>
          </tr>
        </table>
        <div class="submit"><input type="submit" value="<?php
                                   echo $this->EHT('Consolidate') ?> &raquo;" /></div>
        </fieldset>
      </form>
<?php } ?>
    </div>
    <?
  }

  function display_updated($text) {
    echo '<div class="updated"><p>';
    $this->EHT($text);
    echo '</p></div>';
  }

  function T($text) {
    return __($text, 'venus');
  }

  function H($text) {
    return htmlspecialchars($text, ENT_QUOTES);
  }

  function HT($text) {
    return $this->H($this->T($text));
  }

  function EHT($text) {
    echo $this->HT($text);
  }

  function update($force=false) {
    global $wpdb;
    
    $options = get_settings('venus_options');
    $deletes = get_settings('venus_deletes');
    if (!is_array($deletes))
      $deletes = array();

    # Not yet time to check for updates?
    if (!$force && (($options['last_update'] + $options['update_interval'] * 60) > mktime()))
      return;

    $options['last_update'] = mktime();
    update_option('venus_options', $options);
    if ($this->debug)
      $this->log('ready to update.');
    # Loop through files in cache directory.
    $handle = @opendir($options['cache_directory']);
    if (!$handle) {
      if ($this->debug)
        $this->log('unable to open directory, '.$options['cache_directory'].'...');
      return;
    }
    if ($this->debug)
      $this->log('reading directory...');
    $entries = 0;
    while (($filename = @readdir($handle)) !== false) {
      if ($this->debug)
        $this->log('found file: '.$filename);
      # Deleted?
      if (isset($deletes[$filename])) {
        if ($this->debug)
          $this->log('file previously deleted: '.$filename);
        if ($options['remove_on_delete'])
          $this->remove_file($filename);
        continue;
      }
      
      # Only handle real files.
      $path = $options['cache_directory'] . DIRECTORY_SEPARATOR . $filename;
      if (@is_dir($path))
        continue;

      # Check for existing entry/post.
      $filemtime = @filemtime($path);
      $filesize = @filesize($path);
      $post = $wpdb->get_row('SELECT pm1.post_id AS ID, pm2.meta_value AS mtime, pm3.meta_value AS size ' . 
                             ' FROM ' . $wpdb->postmeta . ' AS pm1 ' .
                             ' JOIN ' . $wpdb->postmeta . ' AS pm2 ' .
                             ' JOIN ' . $wpdb->postmeta . ' AS pm3 ' .
                             ' WHERE pm1.post_id = pm2.post_id AND pm2.post_id = pm3.post_id ' .
                             ' AND pm1.meta_key = "venus_cache_file" AND pm1.meta_value = "' . $wpdb->escape($filename) . '" ' .
                             ' AND pm2.meta_key = "venus_cache_mtime"' .
                             ' AND pm3.meta_key = "venus_cache_size" LIMIT 1');
      # Unchanged entry/post?
      if ($post && $filemtime <= $post->mtime && ($filesize == $post->size || !$options['check_entry_size']))
        continue;
      if ($post && $options['never_update_entries']) {
        if ($this->debug)
          $this->log('skipping update of entry #'.$post->ID.' ('.$filemtime.'/'.$post->mtime.'/'.$filesize.'/'.$post->size.'): '.$filename);
        continue;
      }
      if ($this->debug) {
        if ($post)
          $this->log('updating entry #'.$post->ID.' ('.$filemtime.'/'.$post->mtime.'/'.$filesize.'/'.$post->size.'): '.$filename);
        else
          $this->log('new entry ('.$filemtime.'/'.$filesize.'): '.$filename);
      }

      # Parse entry.
      $this->parse_path($path);
      if ('FEED'==$this->parse_tree[0]['name'] && isset($this->parse_tree[0]['nodes']['entry'][0])) {
        foreach ($this->parse_tree[0]['nodes']['entry'] as $entry) {
          if (!isset($entry['source'])) {
            $entry['source'] = $this->parse_tree[0]['nodes'];
            unset($entry['source']['entry']);
          }
          $entries += $this->insert_entry($post, $entry, $filename, $filemtime, $filesize);
        }
      } elseif ('ENTRY'==$this->parse_tree[0]['name']) {
        $entry = $this->parse_tree[0]['nodes'];
        $entries += $this->insert_entry($post, $entry, $filename, $filemtime, $filesize);
      }
    }
    if ($this->debug)
      $this->log('done, found '.$entries.' new entries.');
    return $entries;
  }

  function parse_path($path) {
    $xml = file_get_contents($path);
    if (!function_exists('xml_parser_create')
        || !($parser = @xml_parser_create('UTF-8'))
        || !is_resource($parser))
      trigger_error('Unable to create XML/Atom parser');
    xml_set_element_handler($parser,
                            array(&$this, 'entry_start_element'),
                            array(&$this, 'entry_end_element'));
    xml_set_character_data_handler($parser, array(&$this, 'entry_cdata')); 
    $this->parse_tree = array();
    $this->parse_level = 0;
    $this->parse_inxhtml = false;
    if (xml_parse($parser, $xml, true) && 
        ($errorcode = xml_get_error_code($parser)) != XML_ERROR_NONE)
      trigger_error('Unable to parse Atom entry (' .
                    sprintf(__('XML error: %1$s at line %2$s'),
                    xml_error_string($errorcode),
                    xml_get_current_line_number($parser)) . ')');
    xml_parser_free($parser);
  }

  function insert_entry($post, $entry, $filename, $filemtime, $filesize) {
    global $wpdb;
    $options = get_settings('venus_options');
    include_once(ABSPATH . WPINC . '/rss-functions.php');
    include_once(ABSPATH . WPINC . '/registration-functions.php');
    if (!function_exists('wp_insert_category') || !function_exists('category_exists')) {
      if (file_exists(ABSPATH . 'wp-admin/includes/admin.php')) {
        if ($this->debug)
          $this->log('using wp-admin/includes/admin.php');
        require_once(ABSPATH . 'wp-admin/includes/admin.php');
      } elseif (file_exists(ABSPATH . 'wp-admin/admin-db.php')) {
        if ($this->debug)
          $this->log('using wp-admin/admin-db.php');
        include_once(ABSPATH . 'wp-admin/admin-db.php');
      } else {
        if ($this->debug)
          $this->log('unable to create categories...');
        break;
      }
    }

    if (!isset($entry['source']['link_alternate']) && isset($entry['source']['link_self']))
      $entry['source']['link_alternate'] = &$entry['source']['link_self'];
    if (!isset($entry['author']) && isset($entry['source']['author']))
      $entry['author'] = &$entry['source']['author'];
    if (!isset($entry['category']) && isset($entry['source']['category']))
      $entry['category'] = &$entry['source']['category'];
    if (!isset($entry['author']['uri']) && isset($entry['source']['id']))
      $entry['author']['uri'] = &$entry['source']['id'];
    elseif (!isset($entry['author']['uri']) && isset($entry['source']['link_alternate']))
      $entry['author']['uri'] = &$entry['source']['link_alternate'];
    elseif (!isset($entry['author']['uri']) && isset($entry['source']['link_self']))
      $entry['author']['uri'] = &$entry['source']['link_self'];
    $entry['summary']=preg_replace('|\[i\](.+?)\[/i\]|','<em>$1</em>',$entry['summary']);
    $entry['summary']=preg_replace('|\[b\](.+?)\[/b\]|','<strong>$1</strong>',$entry['summary']);
    $entry['summary']=preg_replace('|\s+---\s+|',' &mdash; ',$entry['summary']);
    if (!isset($entry['content']))
      $entry['content'] = $entry['summary'];
    $entry['summary']=strip_tags($entry['summary']);

    # Find source link info.
    if (function_exists('get_bookmark') && !function_exists('wp_get_link_cats') && file_exists(ABSPATH . 'wp-admin/admin-db.php'))
      @include_once(ABSPATH . 'wp-admin/admin-db.php');
    $linkinfo = array();
    $link = false;
    if ($options['link_category_mapping'] && is_array($links = get_settings('venus_links'))
        && function_exists('get_bookmark') && function_exists('wp_get_link_cats')) {
      foreach ($links as $link_id => $l) {
        if (!($b = get_bookmark($link_id)))
          continue;
        $linkurl = preg_replace('|^http://(www\.)?(.+?)/?$|', '$2', $b->link_url);
        $rssurl = preg_replace('|^http://(www\.)?(.+?)/?$|', '$2', $b->link_rss);
        $sourceurl = str_replace('&', '&#38;', preg_replace('|^http://(www\.)?(.+?)/?$|', '$2', $entry['source']['link_alternate']));
        $selfurl = str_replace('&', '&#38;', preg_replace('|^http://(www\.)?(.+?)/?$|', '$2', $entry['source']['link_self']));
        if ($linkurl!=$sourceurl && $rssurl!=$selfurl)
          continue;
        $link = $b;
        if (function_exists('wp_get_object_terms'))
          $link->link_category = array_unique( wp_get_object_terms($link_id, 'link_category', 'fields=ids') );
        foreach ($link->link_category as $i => $lc) {
          if (function_exists('get_term')) {
            $lc = get_term($lc, 'link_category');
            $link->link_category[$i] = $lc->name;
          } else
            $link->link_category[$i] = get_cat_name($lc);
        }
        if ($this->debug)
          $this->log('found link #'.$link_id.': '.$linkurl.' ('.join(', ', $link->link_category).')');
        $linkinfo = $l;
        break;
      }
    }

    # Skip entry?
    if ($skip = @$linkinfo['skip_match']) {
      if (preg_match('~'.$skip.'~i', $entry['title'])
          || preg_match('~'.$skip.'~i', $entry['content'])) {
        if ($this->debug)
          $this->log('skipping entry "' . $entry['title'] . '", matches skip');
        if ($options['remove_on_skip'])
          $this->remove_file($filename);
        return 0;
      }
    }

    # Replace text in content/summary.
    if (@sizeof(@$linkinfo['replace'])) {
      foreach ($linkinfo['replace'] as $cmr) {
        if ('All'==$cmr['field'])
          $fields = array('title', 'content', 'summary');
        else
          $fields = array(strtolower($cmr['field']));
        foreach ($fields as $k) {
          $v = preg_replace('~'.$cmr['in'].'~i', $cmr['out'], $entry[$k]);
          if ($entry[$k]!=$v) {
            $entry[$k] = $v;
            if ($this->debug)
              $this->log('replaced text matching "'.$cmr['in'].'" in '.$k);
          }
        }
      }
    }

    # Skip consecutive dupe?
    if (($skip_dupe_title = @$linkinfo['skip_dupe_title']) ||
        ($skip_dupe_content = @$linkinfo['skip_dupe_content']) ||
        ($skip_dupe_summary = @$linkinfo['skip_dupe_summary'])) {
      $query = 'SELECT ID, post_title, post_content, post_excerpt FROM ' . $wpdb->posts . ', ' . $wpdb->postmeta . ' WHERE post_date < "' . date('Y-m-d H:i:s',parse_w3cdtf($entry['updated'])) . '" AND ID = post_id AND meta_key = "venus_source_url" AND meta_value = "' . $wpdb->escape($entry['source']['link_alternate']) . '" ORDER BY post_date DESC LIMIT 1';
      $last = $wpdb->get_row($query);
      if ($this->debug)
        $this->log('testing for duplicate entry against #' . $last->ID . ': "' . $last->post_title . '"');
      if ($last->ID && 
          (!$skip_dupe_title || $entry['title']==$last->post_title) &&
          (!$skip_dupe_content || $entry['content']==$last->post_content) &&
          (!$skip_dupe_summary || $entry['summary']==$last->post_excerpt)) {
        if ($this->debug)
          $this->log('skipping duplicate entry "' . $entry['title'] . '" in ' . $filename);
        if ($options['remove_on_skip'])
          $this->remove_file($filename);
        return 0;
      }
    }

    # Find author.
    $where = array();
    if (isset($entry['author']['uri']) && !empty($entry['author']['uri']))
      $where[] = 'user_url = "' . $wpdb->escape($entry['author']['uri']) . '"';
    if (isset($entry['author']['email']) && !empty($entry['author']['email']))
      $where[] = 'user_email = "' . $wpdb->escape($entry['author']['email']) . '"';
    if (isset($entry['author']['name']) && !empty($entry['author']['name']) && 'admin'==$entry['author']['name'])
      $entry['author']['name'] .= md5($entry['author']['uri']);
    if (isset($entry['author']['name']) && !empty($entry['author']['name']))
      $where[] = 'display_name = "' . $wpdb->escape($entry['author']['name']) . '"';
    if (!sizeof($where))
      $post->post_author = 0;
    else {
      $query = 'SELECT ID FROM ' . $wpdb->users . ' WHERE ' . join(' AND ', $where) . ' ORDER BY ID LIMIT 1';
      $post->post_author = $wpdb->get_var($query);
      $venus_id = $entry['author']['uri'].':'.$entry['author']['email'].':'.$entry['author']['name'];
      if (!$post->post_author) {
        $query = 'SELECT user_id FROM ' . $wpdb->usermeta . ' WHERE meta_key="venus_id" AND meta_value="' . $wpdb->escape($venus_id) . '" ORDER BY user_id LIMIT 1';
        $post->post_author = $wpdb->get_var($query);
      }
      if (!$post->post_author) {
        # Create author.
        $author = array();
        if (isset($entry['author']['name']) && !empty($entry['author']['name']))
          $author['user_login'] = $entry['author']['name'];
        elseif (isset($entry['author']['email']) && !empty($entry['author']['email']))
          $author['user_login'] = preg_replace('|@.+$|','',$entry['author']['email']);
        elseif (isset($entry['author']['uri']) && !empty($entry['author']['uri']))
          $author['user_login'] = 'u'.md5($entry['author']['uri']);
        if (isset($entry['author']['email']) && !empty($entry['author']['email']))
          $author['user_email'] = $entry['author']['email'];
        if (isset($entry['author']['uri']) && !empty($entry['author']['uri']))
          $author['user_url'] = $entry['author']['uri'];
        $author['user_nicename'] = $author['user_login'];
        $author['display_name'] = $author['user_login'];
        $author['nickname'] = $author['user_login'];
        $author['user_login'] = sanitize_title(sanitize_user($this->remove_accents($author['user_login']), true));
        $lcc = $wpdb->get_var('SELECT COUNT(*) FROM ' . $wpdb->users . ' WHERE user_login LIKE "' . $wpdb->escape($author['user_login']) . '%"');
        if ($lcc)
          $author['user_login'] .= ($lcc + 1);
        $author['user_nicename'] = $author['user_login'];
        $author['user_pass'] = 'p'.md5(mktime());
        $post->post_author = wp_insert_user($author);
        $user = new WP_User($post->post_author);
        $user->set_role($options['default_user_role']);
        update_usermeta($post->post_author, 'venus_id', $wpdb->escape($venus_id));
        if ($this->debug)
          $this->log('created author #'.$post->post_author.': '.$author['user_nicename']);
      }
    }

    # Find categories.
    $post->post_category = array();
    $categories = array();
    if (isset($entry['category']) && is_array($entry['category'])) {
      if (isset($entry['category']['term']))
        $entry['category']=array($entry['category']);
      foreach ($entry['category'] as $c) {
        if (is_array($c) && isset($c['term']))
          $c = $c['term'];
        elseif (is_array($c) && isset($c[0]) && isset($c[0]['term'])) {
          $cc = array();
          foreach ($c as $ct)
            $cc[] = $ct['term'];
          $c = join(',', $cc);
        }
        $c = preg_split('/\s*,+\s*/', $c, -1, PREG_SPLIT_NO_EMPTY);
        $categories = array_merge($categories, $c);
      }
    }

    # Split categories.
    if (@$linkinfo['category_split']) {
      $newcategories = array();
      foreach ($categories as $c) {
        while (preg_match('|^(\S+)\s+(.+)$|', $c, $M)) {
          $newcategories[] = $M[1];
          $c = $M[2];
        }
        $newcategories[] = $c;
      }
      $categories = $newcategories;
      if ($this->debug)
        $this->log('categories after split: '.join(', ',$categories));
    }
    
    # Map categories.
    if (@sizeof(@$linkinfo['mapping'])) {
      foreach ($categories as $i => $c) {
        foreach ($linkinfo['mapping'] as $m) {
          if (preg_match('<'.$m['in'].'>', $c, $M)) {
            $cc = $m['out'];
            while (preg_match('|^(.*?)\$(\d)(.*?)$|', $cc, $MM))
              $cc = $MM[1] . $M[$MM[2]] . $MM[3];
            if ($this->debug)
              $this->log('mapping category from "'.$c.'" to "'.$cc.'"');
            if (empty($cc))
              unset($categories[$i]);
            else
              $categories[$i] = $cc;
          }
        }
      }
    }

    # Add categories.
    if (@sizeof(@$linkinfo['adding'])) {
      foreach (array('title', 'content') as $e) {
        foreach ($linkinfo['adding'] as $m) {
          if (preg_match('<'.$m['in'].'>', $entry[$e], $M)) {
            $cc = $m['out'];
            while (preg_match('|^(.*?)\$(\d)(.*?)$|', $cc, $MM))
              $cc = $MM[1] . $M[$MM[2]] . $MM[3];
            if ($this->debug)
              $this->log('adding category "'.$cc.'"');
            $categories[] = $cc;
          }
        }
      }
    }

    # Add default category.
    if (!sizeof($categories)) {
      if (@$linkinfo['default_category']) {
        $post->post_category[] = $linkinfo['default_category'];
        if ($this->debug)
          $this->log('adding source default category: #'.$linkinfo['default_category']);
      } elseif ($options['default_category_assignment']) {
        $post->post_category[] = $options['default_category_assignment'];
        if ($this->debug)
          $this->log('adding global default category: #'.$options['default_category_assignment']);
      }
    }

    # Handle merged categories
    $merges = get_settings('venus_merges');
    if (!is_array($merges))
      $merges = array();
    foreach ($categories as $c => $category) {
      $beenthere = array();
      while (isset($merges[$categories[$c]])) {
        $categories[$c] = $merges[$categories[$c]];
        if (isset($beenthere[$categories[$c]]))
          break;
        $beenthere[$categories[$c]] = 1;
      }
    }
    
    # Create categories, if necessary.
    if ($this->debug)
      $this->log('extra categories to assign: '.(sizeof($categories)?join(', ', $categories):'(none)'));
    foreach ($categories as $category) {
      if (strlen($category)<2)
        continue;
      $cat_nicename = sanitize_title($category);
      $cat_nicename = apply_filters('pre_category_nicename', $cat_nicename);
      if (function_exists('category_exists')) {
        $cat = category_exists($category);
        if ($this->debug)
          $this->log('category check: '.$cat_nicename.', '.$category.($cat?' (#'.$cat.')':''));
      } else {
        $cat = $wpdb->get_var('SELECT cat_ID FROM ' . $wpdb->categories . ' WHERE category_nicename = "' . $wpdb->escape($cat_nicename) . '" OR cat_name = "' . $wpdb->escape($category) . ' " LIMIT 1');
        if ($this->debug)
          $this->log('using category: '.$cat_nicename.', '.$category.($cat?' (#'.$cat.')':''));
      }
      if (!$cat) {
        # Create category.
        $cat = wp_insert_category(array(
          'category_parent' => (@$linkinfo['default_category_parent'] ? $linkinfo['default_category_parent'] : $options['default_category_parent']),
          'cat_name' => $wpdb->escape($category),
          'category_nicename' => $wpdb->escape($category)));
        if ($this->debug)
          $this->log('created category #'.$cat.': '.$cat_nicename.', '.$category);
      }
      $post->post_category[] = $cat;
    }
    
    # Create or update post
    $post->post_content = $wpdb->escape($entry['content']);
    $post->post_excerpt = $wpdb->escape($entry['summary']);
    $post->post_title = $wpdb->escape($entry['title']);
    if (@$linkinfo['hold_for_review'])
      $post->post_status = 'pending';
    else
      $post->post_status = 'publish';
    if (@$linkinfo['use_published_date'])
      $post->post_date = date('Y-m-d H:i:s',parse_w3cdtf($entry['published']));
    else
      $post->post_date = date('Y-m-d H:i:s',parse_w3cdtf($entry['updated']));
    $post->comment_status = get_option('default_comment_status');
    $post->ping_status = get_option('default_ping_status');
    $post->post_pingback = 0;
    $post->tags_input = array();
    if ($options['tag_with_source_url'] && isset($entry['source']['link_alternate']))
      $post->tags_input[] = $entry['source']['link_alternate'];
    if ($options['tag_with_source_title'] && isset($entry['source']['title']))
      $post->tags_input[] = $entry['source']['title'];
    if ($options['tag_with_link_title'] && $link && isset($link->link_name))
      $post->tags_input[] = $link->link_name;
    if ($options['tag_with_link_categories'] && $link && is_array($link->link_category))
      $post->tags_input = array_merge($post->tags_input, $link->link_category);
    if ($this->debug && sizeof($post->tags_input))
      $this->log('tagged post with "'.join('", "', $post->tags_input).'"');
    if (!isset($entry['link_alternate']))
      $this->permalink=$entry['id'];
    else
      $this->permalink=$entry['link_alternate'];
    define('WP_IMPORTING', 1);
    $post_id = wp_insert_post($post);
    if ($post_id && !empty($entry['geo:lat']) && !empty($entry['geo:long'])) {
      $coords = $entry['geo:lat'].','.$entry['geo:long'];
      add_post_meta($post_id, '_geo_location', $coords);
      if ($this->debug)
        $this->log('added coordinates: '.$coords);
    }
    sleep(1);
    if ($post_id && !$post->ID) {
      add_post_meta($post_id, 'venus_cache_file', $filename);
      add_post_meta($post_id, 'venus_cache_mtime', $filemtime);
      add_post_meta($post_id, 'venus_cache_size', $filesize);
      if (isset($entry['source']['link_alternate']))
        add_post_meta($post_id, 'venus_source_url', htmlspecialchars($entry['source']['link_alternate']));
      if (isset($entry['source']['title']))
        add_post_meta($post_id, 'venus_source_title', $entry['source']['title']);
      if ($link && isset($link->link_name))
        add_post_meta($post_id, 'venus_source_link_title', $link->link_name);
      delete_post_meta($post_id, 'venus_original_link');
      add_post_meta($post_id, 'venus_original_link', htmlspecialchars($this->permalink));
      if ($this->debug)
        $this->log('finished creating post #'.$post_id.': "'.$entry['title'].'"');
      return 1;
    } elseif ($post_id) {
      update_post_meta($post_id, 'venus_cache_mtime', $filemtime);
      update_post_meta($post_id, 'venus_cache_size', $filesize);
      if ($this->debug)
        $this->log('finished updating post #'.$post_id.': "'.$entry['title'].'"');
      return 0;
    }
  }

  function entry_start_element($parser, $elem, &$attrs) {
    $attrs = array_change_key_case($attrs, CASE_LOWER);
    $this->parse_level++;
    $node = &$this->parse_tree;
    $level = $this->parse_level - 1;
    while ($level) {
      $node = &$node[sizeof($node)-1]['nodes'];
      $level--;
    }
    if ($this->parse_inxhtml) {
      $e = '<' . strtolower($elem);
      foreach ($attrs as $a => $v)
        $e .= ' ' . $a . '="' . htmlspecialchars($v) . '"';
      $e .= '>'; 
      $this->parse_xhtml .= $e;
    } elseif (isset($attrs['xmlns']) && $attrs['xmlns'] == 'http://www.w3.org/1999/xhtml') {
      $this->parse_inxhtml = $this->parse_level-1;
      $e = '<' . strtolower($elem);
      foreach ($attrs as $a => $v)
        $e .= ' ' . $a . '="' . htmlspecialchars($v) . '"';
      $e .= '>'; 
      $this->parse_xhtml = $e;
    }
    if ('LINK'==$elem && isset($attrs['rel'])) {
      $elem .= '_' . $attrs['rel'];
      if ('alternate' == $attrs['rel'] && isset($attrs['type']) && 'application/xml' == $attrs['type'])
        $elem .= '_xml';
      $node[] = array('name' => $elem, 'text' => $attrs['href']);
    } else 
      $node[] = array('name' => $elem, 'attrs' => $attrs);
  }
  
  function entry_end_element($parser, $elem) {
    $this->parse_level--;
    $node = &$this->parse_tree;
    $level = $this->parse_level;
    while ($level) {
      $node = &$node[sizeof($node)-1]['nodes'];
      $level--;
    }
    if ($this->parse_inxhtml) {
      $this->parse_xhtml .= '</' . strtolower($elem) . '>';
      if ($this->parse_inxhtml == $this->parse_level) {
        $this->parse_inxhtml = false;
        $node = $this->parse_xhtml;
      }
    }
    if (isset($node[sizeof($node)-1]['attrs']) && !sizeof($node[sizeof($node)-1]['attrs']))
      unset($node[sizeof($node)-1]['attrs']);
    if (isset($node[sizeof($node)-1]['nodes']) && !sizeof($node[sizeof($node)-1]['nodes']))
      array_pop($node);
    elseif (isset($node[sizeof($node)-1]['nodes']) && is_array($node[sizeof($node)-1]['nodes'])) {
      $nodes = array();
      foreach ($node[sizeof($node)-1]['nodes'] as $n) {
        if (isset($n['nodes']))
          $v = $n['nodes'];
        elseif (isset($n['text']))
          $v = $n['text'];
        else
          $v = $n['attrs'];
        if (isset($nodes[$n['name']])) {
          if (isset($nodes[$n['name']][0])) {
            if (is_array($nodes[$n['name']][0]))
              $nodes[$n['name']][] = $v;
            elseif (!is_array($v))
              $nodes[$n['name']] .= $v;
          } else
            $nodes[$n['name']] = array($nodes[$n['name']], $v);
        } else
          $nodes[$n['name']] = $v;
      }
      if (sizeof($nodes))
        $node[sizeof($node)-1]['nodes'] = array_change_key_case($nodes, CASE_LOWER);
    }
  }

  function entry_cdata($parser, $text) {
    $node = &$this->parse_tree;
    $level = $this->parse_level - 1;
    while ($level) {
      $node = &$node[sizeof($node)-1]['nodes'];
      $level--;
    }
    if ($this->parse_inxhtml)
      $this->parse_xhtml .= $text;
    else {
      if (!isset($node[sizeof($node)-1]['text']))
        $node[sizeof($node)-1]['text']='';
      $node[sizeof($node)-1]['text'] .= $text;
    }
  }
  
  function remove_accents($s) {
    if (seems_utf8($s)) {
      $chars = array(
        chr(195).chr(134) => 'AE',  # Aelig
        chr(195).chr(166) => 'ae',  # aelig
        chr(195).chr(184) => 'oe',  # oslash
        chr(195).chr(152) => 'OE',  # Oslash
        chr(195).chr(133) => 'AA',  # Aring
        chr(195).chr(165) => 'aa'  # aring
      );
      $s = strtr($s, $chars);
    }
    return remove_accents($s);  
  }

  function action_init() {
    load_plugin_textdomain('venus');
  }

  function action_publish_post($post_id) {
    if (!empty($this->permalink))
      add_post_meta($post_id, 'venus_original_link', htmlspecialchars($this->permalink));
  }
  
  function filter_post_link($permalink = '') {
    $options = get_settings('venus_options');
    if ($options['original_link']) {
      list($uri) = get_post_custom_values('venus_original_link');
      return ((strlen($uri) > 0) ? $uri : $permalink);
    } else
      return $permalink;
  }

  function filter_get_the_guid($permalink = '') {
    return $this->filter_post_link($permalink);
  }

  function show_user_profile() {
      global $wpdb, $user_ID;
      if (!current_user_can('edit_plugins') || function_exists('is_site_admin') && !is_site_admin())
        return;
      if (isset($_REQUEST['user_id']))
          $user_ID = $_REQUEST['user_id'];
      $vids = $wpdb->get_col('SELECT meta_value FROM '.$wpdb->usermeta.' WHERE meta_key = "venus_id" AND user_id = '.$wpdb->escape($user_ID));
      if (!sizeof($vids))
        return;
      ?>
  <fieldset style="width: 89%">
      <legend><?php _e('Venus ID List', 'venus'); ?></legend>
      <table>
        <?php
      foreach ($vids as $vid) {
        $vidwrapped = str_replace(':', ': ', $vid);
        $vidwrapped = str_replace('http: ', 'http:', $vidwrapped);
        $vidwrapped = str_replace(': : ', ':: ', $vidwrapped);
        print '<tr><td style="width: 1.2em"><input type="checkbox" checked="checked" name="venus_id[]" id="venus_id_'.md5($vid).'" value="'.$vid.'"/></td><td><code><label for="venus_id_'.md5($vid).'">'.$vidwrapped.'</label></code></td></tr>';
      }
        ?>
      </table>
  </fieldset>
      <?
  }

  function check_passwords() {
    global $wpdb, $current_user;
    if (isset($_POST['user_id']))
      $id=$_POST['user_id'];
    elseif (isset($_POST['checkuser_id']))
      $id=$_POST['checkuser_id'];
    else
      return;
    if (!$current_user->has_cap('edit_users')
        && !$current_user->has_cap('administrator')
        || $id==1 && $current_user->ID!=1)
      return;
    $vids = $wpdb->get_col('SELECT meta_value FROM '.$wpdb->usermeta.' WHERE meta_key = "venus_id" AND user_id = '.$wpdb->escape($id));
    $newvids = $_POST['venus_id'];
    foreach ($vids as $vid) {
      if (!in_array($vid, $newvids))
        delete_usermeta($id, 'venus_id', $vid);
    }
  }
  
  function filter_get_bookmarks($results) {
    $links = get_settings('venus_links');
    foreach ($results as $i => $r) {
      if (isset($links[$r->link_id])) {
        $link = $links[$r->link_id];
        $x = '"';
        if (sizeof($link['replace'])) {
          foreach ($link['replace'] as $j => $replace)
            $x .= ' replace' . $j . '="' . attribute_escape(join('~', $replace)) . '"';
        }
        if (sizeof($link['mapping'])) {
          foreach ($link['mapping'] as $j => $mapping)
            $x .= ' mapping' . $j . '="' . attribute_escape(join('~', $mapping)) . '"';
        }
        if ($link['skip_match'])
          $x .= ' skip_match="' . attribute_escape($link['skip_match']) . '"';
        if ($link['category_split'])
          $x .= ' category_split="' . attribute_escape($link['category_split']) . '"';
        if ($link['default_category'])
          $x .= ' default_category="' . attribute_escape(get_cat_name($link['default_category'])) . '"';
        $x .= ' link_description="' . attribute_escape($r->link_description);
        $r->link_updated .= $x;
        $results[$i] = $r;
      }
    }
    return $results;
  }
  
  function log($line) {
    $line = str_replace("\n", ' ', $line);
    $fh = @fopen(ABSPATH . 'wp-content/venus.log', 'a');
    @fwrite($fh, strftime("%D %T")."\t$line\n");
    @fclose($fh);
  }

}

function perform_venus_update_on_pageload() {
  global $venus;
  if ($venus) {
    add_action('wp_head', array(&$venus, 'update'));
    $venus->wp_head_hooked = true;
  }
}

# Create global Venus object.
$venus = get_venus();

# Include configuration if called directly.
if (!function_exists('add_action')) {
  $venus->do_update=true;
  if (file_exists('../../wp-config.php'))
    include_once('../../wp-config.php');
  elseif (file_exists('../../../wp-config.php'))
    include_once('../../../wp-config.php');
  else {
    $venus->do_update=false;
  }
}

if (function_exists('add_action')) {
  add_option('venus_options', array(
      'last_update' => 0,
      'original_link' => 0,
      'check_entry_size' => 0,
      'update_interval' => 55,
      'default_user_role' => 'author',
      'tag_with_source_url' => 0,
      'tag_with_source_title' => 0,
      'tag_with_link_title' => 0,
      'default_category_parent' => 0,
      'link_category_mapping' => 0,
      'cache_directory' => ''));

  add_action('publish_post', array(&$venus, 'action_publish_post'), 1);

  if ($venus->do_update)
    $venus->update();

  add_action('init', array(&$venus, 'action_init'));
  add_action('admin_head', array(&$venus, 'action_admin_head'));
  add_action('add_link', array(&$venus, 'action_edit_link'));
  add_action('edit_link', array(&$venus, 'action_edit_link'));
  add_action('admin_menu', array(&$venus, 'action_admin_menu'));
  add_action('delete_post', array(&$venus, 'action_delete_post'));
  add_filter('post_link', array(&$venus, 'filter_post_link'), 1);
  add_filter('get_the_guid', array(&$venus, 'filter_get_the_guid'), 1);
  add_action('show_user_profile', array(&$venus, 'show_user_profile'));
  add_action('edit_user_profile', array(&$venus, 'show_user_profile'));
  add_action('check_passwords', array(&$venus, 'check_passwords'));
  add_filter('get_bookmarks', array(&$venus, 'filter_get_bookmarks'), 1);
  #perform_venus_update_on_pageload();
}

// EOF
