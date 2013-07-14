<?php
	
	/**
	*	Moderator Notes
	*
	*	Extended functionality for moderator notes.
	*	Created by Josh Medeiros (Jammerx2)
	*	http://www.joshmedeiros.net/
	*/
	
	/**
	*	Add plugin hooks.
	*/
	$plugins->add_hook('admin_user_groups_edit', 'modnotes_usergroup');
	$plugins->add_hook('admin_user_groups_edit_commit', 'modnotes_usergroup_commit');
	$plugins->add_hook('member_profile_start', 'modnotes_display');
	$plugins->add_hook('modcp_editprofile_start', 'modnotes_display');
	$plugins->add_hook('modcp_start', 'modnotes_modcp');
	
	/**
	*	Returns the plugin information.
	*
	*	@return mixed An array of information about the plugin.
	*/
	function modnotes_info()
	{
		global $lang;
		
		$lang->load('modnotes');
		
		return array(
			"name"			=> $lang->modnotes,
			"description"	=> $lang->modnotes_desc,
			"website"		=> "http://www.joshmedeiros.net",
			"author"		=> "Josh Medeiros (Jammerx2)",
			"authorsite"	=> "http://www.joshmedeiros.net",
			"version"		=> "1.0",
			"compatibility" => "16*"
		);
	}
	
	/**
	*	Creates necessary modifications for the plugin.
	*/
	function modnotes_install()
	{
		global $db, $cache;
		
		if(!$db->field_exists("viewmodnotes", "usergroups"))
		{
			$db->query("ALTER TABLE ".TABLE_PREFIX."usergroups ADD viewmodnotes INT(1) NOT NULL");
		}
		
		if(!$db->field_exists("viewownmodnotes", "usergroups"))
		{
			$db->query("ALTER TABLE ".TABLE_PREFIX."usergroups ADD viewownmodnotes INT(1) NOT NULL");
		}
		
		if(!$db->field_exists("addmodnotes", "usergroups"))
		{
			$db->query("ALTER TABLE ".TABLE_PREFIX."usergroups ADD addmodnotes INT(1) NOT NULL");
		}
		
		if(!$db->field_exists("editmodnotes", "usergroups"))
		{
			$db->query("ALTER TABLE ".TABLE_PREFIX."usergroups ADD editmodnotes INT(1) NOT NULL");
		}
		
		if(!$db->field_exists("editownmodnotes", "usergroups"))
		{
			$db->query("ALTER TABLE ".TABLE_PREFIX."usergroups ADD editownmodnotes INT(1) NOT NULL");
		}
		
		$db->update_query("usergroups", array("viewmodnotes" => 1, "viewownmodnotes" => 1, "addmodnotes" => 1, "editmodnotes" => 1, "editownmodnotes" => 1), "canmodcp='1'");
		
		if(!$db->table_exists("modnotes"))
		{
			$db->write_query("CREATE TABLE ".TABLE_PREFIX."modnotes(
				id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				uid INT(10) UNSIGNED NOT NULL,
				mid INT(10) UNSIGNED NOT NULL,
				note TEXT NOT NULL,
				dateline BIGINT(30) NOT NULL
				) ENGINE=MyISAM".$db->build_create_table_collation().";");
		}
		
		$cache->update_usergroups();
	}
	
	/**
	 *  Returns whether or not the plugin is installed.
	 *  
	 *  @return bool Whether or not the plugin is installed.
	 */
	function modnotes_is_installed()
	{
		global $db;
		return $db->table_exists("modnotes");
	}
	
	/**
	*	Removes modifications created by the plugin.
	*/
	function modnotes_uninstall()
	{
		global $db, $cache;
		
		if($db->field_exists("viewmodnotes", "usergroups"))
		{
			$db->query("ALTER TABLE ".TABLE_PREFIX."usergroups DROP viewmodnotes");
		}
		
		if($db->field_exists("viewownmodnotes", "usergroups"))
		{
			$db->query("ALTER TABLE ".TABLE_PREFIX."usergroups DROP viewownmodnotes");
		}
		
		if($db->field_exists("addmodnotes", "usergroups"))
		{
			$db->query("ALTER TABLE ".TABLE_PREFIX."usergroups DROP addmodnotes");
		}
		
		if($db->field_exists("editmodnotes", "usergroups"))
		{
			$db->query("ALTER TABLE ".TABLE_PREFIX."usergroups DROP editmodnotes");
		}
		
		if($db->field_exists("editownmodnotes", "usergroups"))
		{
			$db->query("ALTER TABLE ".TABLE_PREFIX."usergroups DROP editownmodnotes");
		}
		
		if($db->table_exists("modnotes"))
		{
			$db->drop_table("modnotes");
		}
		
		$db->delete_query("templategroups", "title = 'Moderator Notes'");
		$db->delete_query("templates", "title LIKE 'modnotes_%'");
		
		$cache->update_usergroups();
	}
	
	/**
	 *  Add and edit templates.
	 */
	function modnotes_activate()
	{
		global $mybb, $db;
    
		$q = $db->simple_select("templategroups", "COUNT(*) as count", "title = 'Moderator Notes'");
		$c = $db->fetch_field($q, "count");
		$db->free_result($q);

		if($c < 1)
		{
			$ins = array(
				"prefix" => "modnotes",
				"title"  => "Moderator Notes",
			);
			$db->insert_query("templategroups", $ins);
		}
    
		$ins = array(
			"tid"      => NULL,
			"title"    => 'modnotes',
			"template" => $db->escape_string('<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" width="100%" class="tborder">
<tr>
<td colspan="2" class="thead"><strong>{$lang->modnotes}</strong>{$add}</td>
</tr>
{$modnotes_notes}
</table>
<div class="float_right">
{$multipage}
</div>
<script type="text/javascript">
function deleteModNote(id) {
	confirmReturn = confirm("{$lang->modnotes_delete_confirm}");
	if (confirmReturn == true) {
		var form = new Element("form", {
				method: "post",
				action: "modcp.php?action=deletenote",
				style: "display: none;"
			});
		if (my_post_key) {
			form.insert({
				bottom: new Element("input", {
					name: "my_post_key",
					type: "hidden",
					value: my_post_key
				})
			});
		}
		form.insert({
			bottom: new Element("input", {
				name: "id",
				type: "hidden",
				value: id
			})
		});
		$$("body")[0].insert({
			bottom: form
		});
		form.submit();
	}
}
</script>'),
			"sid"      => "-2",
			"version"  => $mybb->version + 1,
			"dateline" => TIME_NOW
		);
		$db->insert_query("templates", $ins);
		
		$ins = array(
			"tid"      => NULL,
			"title"    => 'modnotes_row',
			"template" => $db->escape_string('
<tr>
<td class="{$trow}" width="10%">{$username}</td>
<td class="{$trow}">{$edit}{$note}</td>
</tr>'),
			"sid"      => "-2",
			"version"  => $mybb->version + 1,
			"dateline" => TIME_NOW
		);
		$db->insert_query("templates", $ins);
		
		$ins = array(
			"tid"      => NULL,
			"title"    => 'modnotes_none',
			"template" => $db->escape_string('
<tr>
<td class="{$trow}" colspan="2" align="center">{$lang->modnotes_none}</td>
</tr>'),
			"sid"      => "-2",
			"version"  => $mybb->version + 1,
			"dateline" => TIME_NOW
		);
		$db->insert_query("templates", $ins);
		
		$ins = array(
			"tid"      => NULL,
			"title"    => 'modnotes_add',
			"template" => $db->escape_string('<span class="smalltext float_right"><a href="{$mybb->settings[\'bburl\']}/modcp.php?action=addnote&amp;uid={$uid}">{$lang->modnotes_add_note}</a></span>'),
			"sid"      => "-2",
			"version"  => $mybb->version + 1,
			"dateline" => TIME_NOW
		);
		$db->insert_query("templates", $ins);
		
		$ins = array(
			"tid"      => NULL,
			"title"    => 'modnotes_edit',
			"template" => $db->escape_string('<span class="smalltext float_right" style="margin: 0px 2px;"><a href="{$mybb->settings[\'bburl\']}/modcp.php?action=editnote&amp;id={$id}">{$lang->modnotes_edit_note}</a> <a href="#" onClick="deleteModNote({$id}); return false;">{$lang->modnotes_delete_note}</a></span>'),
			"sid"      => "-2",
			"version"  => $mybb->version + 1,
			"dateline" => TIME_NOW
		);
		$db->insert_query("templates", $ins);
		
		$ins = array(
			"tid"      => NULL,
			"title"    => 'modnotes_modcp',
			"template" => $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->modnotes_add_modnote}</title>
{$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
<tr>
{$modcp_nav}
<td valign="top">
{$preview}
{$errors}
<form action="modcp.php" method="post">
<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="2"><strong>{$lang->modnotes_add_modnote}</strong></td>
</tr>
<tr>
<td class="tcat" colspan="2"><span class="smalltext"><strong>{$lang->modnotes_user}</strong></span></td>
</tr>
<tr>
<td class="trow2" valign="top"><strong>{$lang->your_message}</strong><br />{$smilieinserter}</td>
<td class="trow2">
<textarea id="message" name="message" rows="20" cols="70" tabindex="2" >{$message}</textarea>
{$codebuttons}
</td>
</tr>
</table>
<br />
<div align="center"><input type="submit" class="button" name="submit" value="{$lang->post_reply}" tabindex="3" accesskey="s" />  <input type="submit" class="button" name="previewpost" value="{$lang->preview_post}" tabindex="4" /></div>
<input type="hidden" name="action" value="do_{$mybb->input[\'action\']}" />
<input type="hidden" name="uid" value="{$uid}" />
</form>
<br />
{$modnotes}
</td>
</tr>
</table>
{$footer}
</body>
</html>'),
			"sid"      => "-2",
			"version"  => $mybb->version + 1,
			"dateline" => TIME_NOW
		);
		$db->insert_query("templates", $ins);
		
		require MYBB_ROOT.'/inc/adminfunctions_templates.php';
		find_replace_templatesets(
			"member_profile",
			'#'.preg_quote('{$modoptions}').'#',
			"{\$modoptions}<br />{\$modnotes}"
		);
		
		find_replace_templatesets(
			"modcp_editprofile",
			'#'.preg_quote("<br />").'\s*'.preg_quote('<div align="center">').'\s*'.preg_quote('<input type="hidden" name="action" value="do_editprofile" />').'#',
			'<br />{$modnotes}$0'
		);
	}
	
	/**
	 *  Remove and restore templates.
	 */
	function modnotes_deactivate()
	{
		global $db;
		
		$db->delete_query("templates", "(title LIKE 'modnotes_%' OR title='modnotes') AND sid='-2'");
		
		require MYBB_ROOT.'/inc/adminfunctions_templates.php';
		find_replace_templatesets(
			"member_profile",
			'#'.preg_quote("<br />{\$modnotes}").'#',
			''
		);
		
		find_replace_templatesets(
			"modcp_editprofile",
			'#'.preg_quote('<br />{$modnotes}').'#',
			''
		);
	}
	
	/**
	 *  Hook the output of the row.
	 */
	function modnotes_usergroup()
	{
		global $plugins;
		//Hook the individual row output
		$plugins->add_hook("admin_formcontainer_output_row", "modnotes_row");
	}
	
	/**
	 *  Updates the usergroup setting in the database.
	 */
	function modnotes_usergroup_commit()
	{
		global $mybb, $db, $usergroup;
		
		$db->update_query("usergroups", array("viewmodnotes" => ($mybb->input['viewmodnotes'] == 1 ? 1 : 0),
										"viewownmodnotes" => ($mybb->input['viewownmodnotes'] == 1 ? 1 : 0),
										"addmodnotes" => ($mybb->input['addmodnotes'] == 1 ? 1 : 0),
										"editmodnotes" => ($mybb->input['editmodnotes'] == 1 ? 1 : 0),
										"editownmodnotes" => ($mybb->input['editownmodnotes'] == 1 ? 1 : 0)
										), "gid='".$usergroup['gid']."'");
	}
	
	/**
	 *  Output options to user.
	 *  
	 *  @param mixed Plugin arguments.
	 */
	function modnotes_row(&$pluginargs)
	{
		global $mybb, $lang, $form_container, $form, $usergroup;
		
		if($pluginargs['title'] == $lang->misc)
		{
			$lang->load("modnotes");
			if(!isset($mybb->input['viewmodnotes'])) $mybb->input['viewmodnotes'] = $usergroup['viewmodnotes'];
			if(!isset($mybb->input['viewownmodnotes'])) $mybb->input['viewownmodnotes'] = $usergroup['viewownmodnotes'];
			if(!isset($mybb->input['addmodnotes'])) $mybb->input['addmodnotes'] = $usergroup['addmodnotes'];
			if(!isset($mybb->input['editmodnotes'])) $mybb->input['editmodnotes'] = $usergroup['editmodnotes'];
			if(!isset($mybb->input['editownmodnotes'])) $mybb->input['editownmodnotes'] = $usergroup['editownmodnotes'];
			
			$checkboxes = array($form->generate_check_box("viewmodnotes", 1, $lang->modnotes_view, array("checked" => $mybb->input['viewmodnotes'])),
								$form->generate_check_box("viewownmodnotes", 1, $lang->modnotes_view_own, array("checked" => $mybb->input['viewownmodnotes'])),
								$form->generate_check_box("addmodnotes", 1, $lang->modnotes_add, array("checked" => $mybb->input['addmodnotes'])),
								$form->generate_check_box("editmodnotes", 1, $lang->modnotes_edit, array("checked" => $mybb->input['editmodnotes'])),
								$form->generate_check_box("editownmodnotes", 1, $lang->modnotes_edit_own, array("checked" => $mybb->input['editownmodnotes']))
								);
			
			$form_container->output_row($lang->modnotes, "", "<div class=\"group_settings_bit\">".implode("</div><div class=\"group_settings_bit\">", $checkboxes)."</div>", "modnotes");
		}
	}
	
	/**
	 *  Display moderator notes.
	 */
	function modnotes_display($hideadd = false)
	{
		global $mybb, $db, $modnotes, $theme, $templates, $lang;
		
		if($mybb->input['uid'])
		{
			$uid = intval($mybb->input['uid']);
		}
		else
		{
			$uid = $mybb->user['uid'];
		}
		
		if($mybb->usergroup['viewmodnotes'] != 1 && !($mybb->usergoup['viewownmodnotes'] == 1 && $uid == $mybb->user['uid'])) return;
		
		$lang->load("modnotes");
		
		$query = $db->simple_select("modnotes", "COUNT(id) AS count", "uid='{$uid}'");
		$perpage = 10;
		$notecount = $db->fetch_field($query, "count");
		$page = intval($mybb->input['page']);
		$pages = ceil($notecount / $perpage);
		if($page < 1) $page = 1;
		if($page > $pages) $page = $pages;
		
		$query = $db->simple_select("modnotes", "*", "uid='{$uid}'", array("limit_start" => ($page-1)*$perpage, "limit" => $perpage));
		
		$modnotes_notes = "";
		
		$trow = "trow1";
		
		require_once MYBB_ROOT."inc/class_parser.php";
		$parser = new postParser;
		
		$parser_options = array(
				"allow_html" => 0,
				"allow_mycode" => 1,
				"allow_smilies" => 1,
				"allow_imgcode" => 1,
				"allow_videocode" => 1,
				"filter_badwords" => 1
			);
		
		$count = 0;
		
		while($modnote = $query->fetch_array())
		{
			$trow = "trow".(++$count % 2 + 1);
			$user = get_user($modnote['mid']);
			$parser_options['me_username'] = $user['username'];
			$note = $parser->parse_message($modnote['note'], $parser_options);
			$username = "<a href=\"{$mybb->settings['bburl']}/".get_profile_link($uid)."\" title=".my_date($mybb->settings['dateformat'], $modnote['dateline']).'&nbsp;'.my_date($mybb->settings['timeformat'], $modnote['dateline'])."\">".format_name($user['username'], $user['usergroup'], $user['displaygroup'])."</a>";
			$id = $modnote['id'];
			
			if(!$hideadd && ($mybb->usergroup['editmodnotes'] == 1 || ($mybb->usergroup['editownmodnotes'] == 1 && $modnote['mid'] == $mybb->user['uid']))) eval('$edit = "'.$templates->get("modnotes_edit").'";');
			eval('$modnotes_notes .= "'.$templates->get("modnotes_row").'";');
		}
		
		if($mybb->input['action']) $ending .= ($ending ? "&" : "?")."action=".urlencode($mybb->input['action']);
		
		if($mybb->input['uid']) $ending .= ($ending ? "&" : "?")."uid=".urlencode($mybb->input['uid']);
		
		$multipage = multipage($notecount, $perpage, $page, THIS_SCRIPT.$ending);
		
		if($modnotes_notes == "") eval('$modnotes_notes = "'.$templates->get("modnotes_none").'";');
		
		if(!$hideadd && $mybb->usergroup['addmodnotes'] == 1) eval('$add = "'.$templates->get("modnotes_add").'";');
		
		eval('$modnotes = "'.$templates->get("modnotes").'";');
	}
	
	/**
	 *  Moderator notes moderator control panel.
	 */
	function modnotes_modcp()
	{
		global $mybb, $db, $lang, $headerinclude, $header, $footer, $theme, $modcp_nav, $templates, $modnotes;
		
		if($mybb->input['action'] == "deletenote" && $mybb->request_method == "post")
		{
			$note = $db->simple_select("modnotes", "id,uid,mid", "id='".intval($mybb->input['id'])."'");
			$note = $note->fetch_array();
			if($mybb->usergroup['editmodnotes'] != 1 && !($mybb->usergroup['editownmodnotes'] == 1 && $mybb->user['uid'] == $note['mid'])) error_no_permission();
			
			verify_post_check($mybb->input['my_post_key']);
			
			$lang->load("modnotes");
			if(!$note['id']) error($lang->modnotes_notfound);
			
			$db->delete_query("modnotes", "id='{$note['id']}'");
			redirect(get_profile_link($note['uid']), $lang->modnotes_delete_success);
		}
		
		if($mybb->input['action'] == "do_editnote")
		{
			$id = $mybb->input['id'] = intval($mybb->input['uid']);
			$note = $db->simple_select("modnotes", "*", "id='{$id}'");
			$note = $note->fetch_array();
			if($mybb->usergroup['editmodnotes'] != 1 && !($mybb->usergroup['editownmodnotes'] == 1 && $mybb->user['uid'] == $note['mid'])) error_no_permission();
			
			$lang->load("modnotes");
			if(!$note['id']) error($lang->modnotes_notfound);
			
			$user = get_user($note['uid']);
			$uid = $user['uid'];
		
			// Verify incoming POST request
			verify_post_check($mybb->input['my_post_key']);
			
			$lang->load("datahandler_post");
			$lang->load("modnotes");
			
			$message = trim_blank_chrs($mybb->input['message']);
			
			if(my_strlen($message) < $mybb->settings['minmessagelength'] && $mybb->settings['minmessagelength'] > 0)
			{
				$lang->postdata_message_too_short = $lang->sprintf($lang->postdata_message_too_short, $mybb->settings['minmessagelength']);
				$errors[] = $lang->postdata_message_too_short;
			}
			
			if(my_strlen($message) > $mybb->settings['maxmessagelength'] && $mybb->settings['maxmessagelength'] > 0)
			{
				$lang->postdata_message_too_long = $lang->sprintf($lang->postdata_message_too_long, $mybb->settings['maxmessagelength'], my_strlen($message));
				$errors[] = $lang->postdata_message_too_long;
			}
			
			if(count($errors) > 0)
			{
				$errors = inline_error($errors);
				$mybb->input['action'] = "editnote";
			}
			else if(!$mybb->input['previewpost'])
			{
				$update = array(
							"note"     => $db->escape_string($message),
							"dateline" => TIME_NOW
							);
							
				$db->update_query("modnotes", $update, "id='{$note['id']}'");
				redirect(get_profile_link($uid), $lang->redirect_modnotes);
			}
			
			if($mybb->input['previewpost'] && my_strlen($message) > 0)
			{
				require_once MYBB_ROOT."inc/class_parser.php";
				$parser = new postParser;
				
				$parser_options = array(
						"allow_html" => 0,
						"allow_mycode" => 1,
						"allow_smilies" => 1,
						"allow_imgcode" => 1,
						"allow_videocode" => 1,
						"me_username" => $mybb->user['username'],
						"filter_badwords" => 1
					);
				$note = $parser->parse_message($message, $parser_options);
				
				$trow = "trow1";
				$username = "<a href=\"{$mybb->settings['bburl']}/".get_profile_link($mybb->user['uid'])."\" title=".my_date($mybb->settings['dateformat'], TIME_NOW).'&nbsp;'.my_date($mybb->settings['timeformat'], TIME_NOW)."\">".format_name($mybb->user['username'], $mybb->user['usergroup'], $mybb->user['displaygroup'])."</a>";
				
				$temp = $lang->modnotes;
				$lang->modnotes = $lang->modnotes_preview;
				
				eval('$modnotes_notes .= "'.$templates->get("modnotes_row").'";');
				eval('$preview = "'.$templates->get("modnotes").'";');
				$preview .= "<br />";
				
				$lang->modnotes = $temp;
				
				$mybb->input['action'] = "editnote";
			}
		}
		
		if($mybb->input['action'] == "editnote")
		{
			$note = $db->simple_select("modnotes", "*", "id='".intval($mybb->input['id'])."'");
			$note = $note->fetch_array();
			if($mybb->usergroup['editmodnotes'] != 1 && !($mybb->usergroup['editownmodnotes'] == 1 && $mybb->user['uid'] == $note['mid'])) error_no_permission();
			
			$lang->load("modnotes");
			if(!$note['id']) error($lang->modnotes_notfound);
			
			$user = get_user($note['uid']);
			
			$message = ($mybb->input['message'] ? $mybb->input['message'] : $note['note']);
			
			require_once MYBB_ROOT."inc/functions_post.php";
		
			$lang->load("editpost");
			add_breadcrumb($lang->mcp_nav_editprofile, "modcp.php?action=editprofile&id=".$note['id']);
			add_breadcrumb($lang->modnotes_edit_modnote, "modcp.php?action=editnote&id=".$note['id']);
			
			if($mybb->settings['bbcodeinserter'] != 0 && $mybb->user['showcodebuttons'] != 0)
			{
				$codebuttons = build_mycode_inserter();
				$smilieinserter = build_clickable_smilies();
			}
			
			$lang->modnotes_user = $lang->sprintf($lang->modnotes_user_edit, build_profile_link(format_name($user['username'], $user['usergroup'], $user['displaygroup']), $user['uid']));
			$temp = $lang->modnotes_add_modnote;
			$temp2 = $lang->post_reply;
			$lang->post_reply = $lang->update_post;
			$lang->modnotes_add_modnote = $lang->modnotes_edit_modnote;
			$uid = $note['id'];
			
			eval('$page = "'.$templates->get("modnotes_modcp").'";');
			
			$lang->modnotes_add_modnote = $temp;
			$lang->post_reply = $temp2;
			output_page($page);
		}
		
		if($mybb->input['action'] == "do_addnote")
		{
			if($mybb->usergroup['addmodnotes'] != 1) error_no_permission();
			
			$uid = intval($mybb->input['uid']);
			
			$user = get_user($mybb->input['uid']);
			if(!$user['uid'])
			{
				error($lang->invalid_user);
			}
		
			// Verify incoming POST request
			verify_post_check($mybb->input['my_post_key']);
			
			$lang->load("datahandler_post");
			$lang->load("modnotes");
			
			$message = trim_blank_chrs($mybb->input['message']);
			
			if(my_strlen($message) < $mybb->settings['minmessagelength'] && $mybb->settings['minmessagelength'] > 0)
			{
				$lang->postdata_message_too_short = $lang->sprintf($lang->postdata_message_too_short, $mybb->settings['minmessagelength']);
				$errors[] = $lang->postdata_message_too_short;
			}
			
			if(my_strlen($message) > $mybb->settings['maxmessagelength'] && $mybb->settings['maxmessagelength'] > 0)
			{
				$lang->postdata_message_too_long = $lang->sprintf($lang->postdata_message_too_long, $mybb->settings['maxmessagelength'], my_strlen($message));
				$errors[] = $lang->postdata_message_too_long;
			}
			
			if(count($errors) > 0)
			{
				$errors = inline_error($errors);
				$mybb->input['action'] = "addnote";
			}
			else if(!$mybb->input['previewpost'])
			{
				$insert = array(
							"uid"      => $uid,
							"mid"      => $mybb->user['uid'],
							"note"     => $db->escape_string($message),
							"dateline" => TIME_NOW
							);
							
				$db->insert_query("modnotes", $insert);
				redirect(get_profile_link($uid), $lang->redirect_modnotes);
			}
			
			if($mybb->input['previewpost'] && my_strlen($message) > 0)
			{
				require_once MYBB_ROOT."inc/class_parser.php";
				$parser = new postParser;
				
				$parser_options = array(
						"allow_html" => 0,
						"allow_mycode" => 1,
						"allow_smilies" => 1,
						"allow_imgcode" => 1,
						"allow_videocode" => 1,
						"me_username" => $mybb->user['username'],
						"filter_badwords" => 1
					);
				$note = $parser->parse_message($message, $parser_options);
				
				$trow = "trow1";
				$username = "<a href=\"{$mybb->settings['bburl']}/".get_profile_link($mybb->user['uid'])."\" title=".my_date($mybb->settings['dateformat'], TIME_NOW).'&nbsp;'.my_date($mybb->settings['timeformat'], TIME_NOW)."\">".format_name($mybb->user['username'], $mybb->user['usergroup'], $mybb->user['displaygroup'])."</a>";
				
				$temp = $lang->modnotes;
				$lang->modnotes = $lang->modnotes_preview;
				
				eval('$modnotes_notes .= "'.$templates->get("modnotes_row").'";');
				eval('$preview = "'.$templates->get("modnotes").'";');
				$preview .= "<br />";
				
				$lang->modnotes = $temp;
				
				$mybb->input['action'] = "addnote";
			}
		}
		
		if($mybb->input['action'] != "addnote") return;
		
		if($mybb->usergroup['addmodnotes'] != 1) error_no_permission();
		
		$uid = intval($mybb->input['uid']);
		
		$user = get_user($mybb->input['uid']);
		if(!$user['uid'])
		{
			error($lang->invalid_user);
		}
		
		require_once MYBB_ROOT."inc/functions_post.php";
		
		$lang->load("modnotes");
		$lang->load("newreply");
		add_breadcrumb($lang->mcp_nav_editprofile, "modcp.php?action=editprofile&uid=".$uid);
		add_breadcrumb($lang->modnotes_add_modnote, "modcp.php?action=addnote&uid=".$uid);
		
		if($mybb->settings['bbcodeinserter'] != 0 && $mybb->user['showcodebuttons'] != 0)
		{
			$codebuttons = build_mycode_inserter();
			$smilieinserter = build_clickable_smilies();
		}
		
		$lang->modnotes_user = $lang->sprintf($lang->modnotes_user, build_profile_link(format_name($user['username'], $user['usergroup'], $user['displaygroup']), $user['uid']));
		
		modnotes_display(true);
		eval('$page = "'.$templates->get("modnotes_modcp").'";');
		output_page($page);
	}
	
?>