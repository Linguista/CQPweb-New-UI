<?php
/*
 * CQPweb: a user-friendly interface to the IMS Corpus Query Processor
 * Copyright (C) 2008-today Andrew Hardie and contributors
 *
 * See http://cwb.sourceforge.net/cqpweb.php
 *
 * This file is part of CQPweb.
 * 
 * CQPweb is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * CQPweb is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
/** @var array $DESCRIBE_METADATA_TYPE (suppress Eclipse issue with const arrays) */
/** @var array $RELATIVE_URL_BASE (suppress Eclipse issue with const arrays) */


/**
 * @file
 * 
 * A file full of functions that generate handy bits of HTML.
 * 
 * MOST of the functions in this library *return* a string rather than echoing it.
 * 
 * So, the return value can be echoed (to browser), or stuffed into a variable.
 */

/**
 * Backend function which implements menu item printing. 
 */
function print_menurow_backend(string $link_handle, string $link_text, string $tip_text, bool $active, string $script='index.php') : string
{
	$tip_data = empty($tip_text) ? '' : ' data-tooltip="' . escape_html($tip_text) . '"';
	
	$s = '';
	if (!$active)
		$s .= "\n\t<a class=\"menuItem\" href=\"$script?ui=$link_handle\"$tip_data>";
	else 
		$s .= "\n\t<a class=\"menuItem current\">";
	$s .= "$link_text</a>\n";
	
	return $s;
}


/**
 * Creates a table row for the index-page left-hand-side menu, which is either a link,
 * or a greyed-out entry if the variable specified as $current_query is equal to
 * the link handle. It is returned as a string, -not- immediately echoed.
 *
 * This is the version for the help page.
 */
function print_menurow_help(string $link_handle, string $link_text, string $tip_text, string $active_handle, string $script='help.php') : string
{
	return print_menurow_backend($link_handle, $link_text, $tip_text, ($active_handle == $link_handle), $script);
}


/**
 * Creates a table row for the index-page left-hand-side menu, which is either a link,
 * or a greyed-out entry if the $active_handle is equal to
 * the link handle. It is returned as a string, -not- immediately echoed.
 *
 * This is the version for a normal index.php.
 */
function print_menurow_index(string $link_handle, string $link_text, string $tip_text, string $active_handle) : string
{
	return print_menurow_backend($link_handle, $link_text, $tip_text, ($active_handle == $link_handle));
}


/**
 * Creates a table row for the index-page left-hand-side menu, which is a section heading
 * containing the label as provided.
 */
function print_menurow_heading(string $label) : string
{
	return "\n\t<a class=\"menuItem header\">$label</a>\n";
}


/**
 * Print the "about CQPweb" block that appears at the bottom of the menu for queryhome; some but not all elements are used in Userhome. 
 * 
 * Returns string (does not echo automatically!) 
 */
function print_menu_aboutblock(string $what_ui) : string
{
	global $Config;
	
	$prefix = get_url_prefix();
	
	$userhome_chunk = '';
	if (RUN_LOCATION_USERCORPUS  == $Config->run_location)
		$userhome_chunk = <<<END_OF_HTML
		
			<a id="menu:about:account" class="menuItem" href="{$prefix}usr" data-tooltip="Account control and your personal settings">User account</a>
		END_OF_HTML;
	
	/* cqpweb-new-ui: the site's news page, when it exists and is switched on (Personalization page) */
	require_once(__DIR__ . '/cqpweb-new-ui/brand-lib.php');
	$cp_news = (cp_brand()['newsShow'] && cp_news_available())
		? '<a id="menu:about:cpnews" class="menuItem" href="' . $prefix . 'news.php" data-tooltip="What has changed on this site">' . escape_html(cp_news_title()) . '</a>'
		: '';
	
	return print_menurow_heading('About CQPweb') 
		. <<<END_OF_HTML
		
			<a id="menu:about:homepage" class="menuItem" href="{$prefix}" data-tooltip="Go to the main homepage for this CQPweb server">CQPweb main menu</a>
		$userhome_chunk
			<a id="menu:about:help" class="menuItem" target="cqpweb_help_browser" href="help.php" data-tooltip="Open the help browser">Help system</a>
		
			<a id="menu:about:videos" class="menuItem" 
				href="https://www.youtube.com/playlist?list=PL2XtJIhhrHNQgf4Dp6sckGZRU4NiUVw1e"
				data-tooltip="CQPweb video tutorials (on YouTube)"
			>Video tutorials</a>
		
			$cp_news
		
		END_OF_HTML
		. print_menurow_index('who_the_hell', 'Who did it?', '', $what_ui)
		. print_menurow_index('latest'      , 'CQPweb news', '', $what_ui)
		. print_menurow_index('bugs'        , 'Report bugs', '', $what_ui)
		;
}

function print_menurow_admin_ui() : string
{
	global $User;
	if (!$User->is_admin())
		return '';
	
	$prefix = get_url_prefix();
	
	return "\n\t<a class=\"menuItem\" href=\"{$prefix}adm\">Go to admin control panel</a>\n";
}


/**
 * Takes a string consisting of a sequence of td's.
 * 
 * Returns the same string of td's, in the opposite order.
 * Surrounding whitespace in the original string is trimmed.
 * 
 * Note - if there is material outside the td's, results may
 * be unexpected. Should not be used outside the concordance
 * line rendering module.
 */
function invert_printed_tds(string $str) : string
{
	$str = trim($str);
	if ('</td>' == substr($str, -5))
		$str = substr($str, 0, -5);
	
	$stack = array_reverse(explode('</td>', $str));
	
	return implode('</td>', $stack) . '</td>';
}


/**
 * Echoes a string, but with HTML 'pre' tags (used by squawk, flaunt and friends).
 */
function pre_echo(string $s) : void
{
	echo "\n<pre>\n", escape_html($s), "\n</pre>\n";
}


/**
 * Create hidden input elements from a hash containing parameters to pass through.
 * 
 * @param  array  $hash      Asociative array (name=>value) from which to generate hidden inputs.
 * @param  string $form_id   If not empty, the hidden inputs will be flagged as belonging to the specified form.
 * @return string            Line of HTML hidden inputs. 
 */
function print_hidden_inputs(array $hash, string $form_id = '') : string
{
	array_walk($hash, 
			function (&$value, $name, $form_id)
			{ 
				$value = '<input ' 
						. (empty($form_id) ? '' : ('form="'.$form_id.'" ')) 
						. 'type="hidden" name="' . $name 
						. '" value="' . escape_html($value) 
						. '">'
						; 
			},
			$form_id);
	return implode('', $hash);
}


/**
 * Gets a string for a URL (without '&' before or after).
 * @param  array $hash    Associative array of parameters.
 * @param  bool  $html    Whether to be safe for HTML (e.g. for an href="" ). 
 *                        If we do, the value separator is &amp; . 
 *                        If not, it's a plain ampersand. Defaults false.
 * @return string         URL query fragment.
 */
function print_url_params(array $hash, bool $html = false) : string
{
	return http_build_query($hash, null, ($html ? '&amp;' : '&'));
}


/**
 * Builds the "data-tooltip" attribute as a string for links to the specified text_id.
 */
function print_text_metadata_tooltip(string $text_id) : string
{
	global $Corpus;

	static $stored_tts = array();

	/* avoid re-running the queries / string building code for a text whose tooltip has already been created;
	 * worth doing because we KNOW a common use-case is to have lots of concordances from the same text visible at once,
	 * which will cause this function to be called once per concordance line. */
	if (isset($stored_tts[$text_id]))
		return $stored_tts[$text_id];

	$text_data = array_map('escape_html', get_map_of_metadata_for_text($Corpus->id, $text_id, true));

	$field_descs = array_map('escape_html', list_text_metadata_fields_as_desc_map($Corpus->id));

	if (empty($field_descs))
		return '';

	$tt = 'data-tooltip="' . "Text <strong>$text_id</strong><br><em>(length = " . number_format($text_data['n_tokens'], 0). ' tokens)</em><br>--------------------<br>';
	unset($text_data['text_id'], $text_data['n_tokens'], $text_data['cpos_begin'], $text_data['cpos_end']);

	foreach ($text_data as $f => $html_desc_val)
		$tt .= "<em>{$field_descs[$f]}:</em> <strong>$html_desc_val</strong><br>";

	$tt .= '"'; /* end of data-tooltip HTML attribute */

	/* store result for later use */
	return ($stored_tts[$text_id] = $tt);
}


/**
 * Produces three options for a select(d/a/da) to choose the CRLF for a data download.
 * 
 * @return string     HTML: three option elements; default set by the user setting, or guessed.
 */
function print_download_crlf_options() : string
{
	global $User;
	
	$da_select = array('d' => '', 'a' => '', 'da' => '');
	
	$opt_selected = ( $User->linefeed != 'au' ? $User->linefeed : guess_user_linefeed($User->username) ) ;
	$da_select[$opt_selected] = ' selected';
	
	return <<<END_OF_HTML
	
							<option value="da" {$da_select['da']}>Windows</option>
							<option value="a"  {$da_select['a']} >UNIX (incl. Mac OS, iOS &amp; Android)</option>
							<option value="d"  {$da_select['d']} >Old Macintosh (OS 9 or below)</option>
	
	END_OF_HTML;
}




/**
 * Gets a string with an <a><img></a> blob for the RSS icon of the right kind for the run location.
 * @return string
 */
function print_rss_icon_link() : string
{
	global $Config;
	return '<a href="' . RELATIVE_URL_BASE[$Config->run_location] . 'rss"><img src="' . RELATIVE_URL_BASE[$Config->run_location] . 'css/img/feed-icon-14x14.png"></a>';
}


/**
 * Returns string containing a form chunk that has in it the P-attribute definition form.
 * 
 * Works in tandem with clientside JS functions, q.v.
 * 
 * @param  string $input_name_base  Prefix for HTML-form-field "names".
 * @param  int    $init_n           Initial number of rows to print. Minimum is 1. Default is 6.
 * @return string                   A string containing the HTML of the form-chunk.

 */
function print_embiggenable_p_attribute_form($input_name_base, $init_n = 6)
{
	$html = <<<END
	
				<tr id="p_att_row_1">
					<td class="layout-bg algC">Primary?</td>
					<td class="layout-bg algC">Handle</td>
					<td class="layout-bg algC">Description</td>
					<td class="layout-bg algC">Tagset</td>
					<td class="layout-bg algC">External URL</td>
					<td class="layout-bg algC">Collation setting</td>
					<td class="layout-bg algC">Needs freq. table?</td>
					<td class="layout-bg algC">Feature set?</td>
				</tr>
	
	END;
	
	$p_handle_len = HANDLE_MAX_ANNOTATION;
	$free_str_len = NONHANDLE_MAX_STRING;

	for ($q = 1 ; $q <= $init_n; $q++)
		$html .= <<<END_OF_ROW
		
					<tr>
						<td class="layout-fg algC">
							<input type="radio" name="{$input_name_base}PPrimary" value="$q">
						</td>
						<td class="layout-fg algC">
							<input type="text" maxlength="$p_handle_len" name="{$input_name_base}PHandle$q" onKeyUp="check_c_word(this)">
						</td>
						<td class="layout-fg algC">
							<input type="text" maxlength="$free_str_len" name="{$input_name_base}PDesc$q">
						</td>
						<td class="layout-fg algC">
							<input type="text" maxlength="$free_str_len" name="{$input_name_base}PTagset$q">
						</td>
						<td class="layout-fg algC">
							<input type="text" maxlength="$free_str_len" name="{$input_name_base}Purl$q">
						</td>
						<td class="layout-fg algC">
							<select name="{$input_name_base}P_clt$q">
								<option value="aici">AI / CI (%cd)</option>
								<option value="aics">AI / CS (%d)</option>
								<option value="asci">AS / CI (%c)</option>
								<option value="ascs" selected>AS / CS</option>
							</select>
						</td>
						<td class="layout-fg comfy algC">
							<input type="checkbox" name="{$input_name_base}P_ftr$q" value="1" checked>
						</td>
						<td class="layout-fg comfy algC">
							<input type="checkbox" name="{$input_name_base}P_ifs$q" value="1">
						</td>
					</tr>
		
		END_OF_ROW;
	
	$html .= <<<END_OF_HTML
	
				<tr id="p_embiggen_button_row">
					<td class="layout-bg algC comfy" colspan="8">
						<button type="button" onClick="add_p_attribute_row()">Embiggen form</button>
					</td>
				</tr>
				<input type="hidden" name="pNumRows" id="pNumRows" value="$init_n">
				<input type="hidden" name="inputNameBaseP" id="inputNameBaseP" value="$input_name_base">
	
	END_OF_HTML;
	
	return $html;
}


/**
 * Creates a chunk of an HTML form containing a dynamically-growable form for use in XML templates
 * and installation of corpus XML.
 *
 * Works in tandem with two clientside JS functions, q.v.
 *
 * @param  string $input_name_base  Prefix for HTML-form-field "names".
 * @param  int    $init_n           Initial number of rows to print. Minimum is 1. Default is 4.
 * @return string                   A string containing the HTML of the form-chunk.
 */
function print_embiggenable_s_attribute_form(string $input_name_base, int $init_n = 4) : string
{
	/* some chunks using constants that we need to create first, then embed. */
	$text_id_type = METADATA_TYPE_UNIQUE_ID;
	
// 	/* note, we *manully* list the datatypes, rather than using some sort of array. This may merit changing later. */
// 	$optblock = '
// 				<option value="' . METADATA_TYPE_FREETEXT       . '" selected>Free text</option>
// 				<option value="' . METADATA_TYPE_CLASSIFICATION . '">Classification</option>
// 				<option value="' . METADATA_TYPE_IDLINK         . '">ID link</option>
// 				<option value="' . METADATA_TYPE_UNIQUE_ID      . '">Unique ID</option>
// 		';
	$optblock = '';
	foreach (list_valid_metadata_types(true) as $value)
		$optblock .= '<option value="' . $value . ($value == METADATA_TYPE_FREETEXT? '" selected>' : '">') . DESCRIBE_METADATA_TYPE[$value] . '</option>' . "\n\t\t\t\t\t\t";

	/* now compose the main returnable */ 
	$html = <<<END
	
				<tr id="s_att_row_1">
					<td class="layout-bg algC" rowspan="2">Element tag</td>
					<td class="layout-bg algC" rowspan="2">Description</td>
					<td class="layout-bg algC" colspan="3">Attributes</td>
				</tr>
				<tr>
					<td class="layout-bg algC">Att tag</td>
					<td class="layout-bg algC">Description</td>
					<td class="layout-bg algC">Datatype</td>
				</tr>
				
				<!-- first content row is the text element: special -->
				<tr id="row_for_S_1">
					<td id="cell{$input_name_base}SHandle1" rowspan="2" class="layout-fg algC">
						<!-- hidden block of select options for the attribute datatypes; never rendered, 
						     but used as a template by the clientside JavaScript.
						     Exists in this row only. -->
						<select id="getDataTypeOptionsFromHere" style="display:none">
							$optblock
						</select>
						
						<input type="hidden" name="nOfAttsFor{$input_name_base}Xml1" id="nOfAttsFor{$input_name_base}Xml1" value="1">
						<!-- hidden variables that == the inputs that **would** exist for text/text_id -->
						<input type="hidden" name="{$input_name_base}SHandle1" value="text">
						<input type="hidden" name="{$input_name_base}SDesc1" value="Text">
						<input type="hidden" name="{$input_name_base}SHandleAtt1_1" value="id">
						<input type="hidden" name="{$input_name_base}SDescAtt1_1" value="Text ID">
						<input type="hidden" name="{$input_name_base}STypeAtt1_1" value="$text_id_type">
						<em>text</em>
					</td>
					<td id="cell{$input_name_base}SDesc1" rowspan="2" class="layout-fg algC">
						<em>The text division markers<br>are automatic and compulsory.</em>
					</td>
					<td class="layout-fg algC">
						<em>id</em>
					</td>
					<td class="layout-fg algC comfy" colspan="2">
						<em>automatic and compulsory</em>
					</td>
				</tr>
				<tr>
					<td id="addXmlAttributeButtonCellFor1" class="layout-fg hollow" colspan="3">
						<a class="control-box" onClick="add_xml_attribute_to_s(1)">[Add attribute slot]</a>
					</td>
				</tr>
				<!-- end special variant row for text/text_id -->
	
	END;
	
	$max_len = NONHANDLE_MAX_STRING;
	
	for ($q = 2 ; $q <= $init_n; $q++)
		$html .= <<<END
		
					<tr id="row_for_S_$q">
						<td id="cell{$input_name_base}SHandle$q" class="layout-fg algC">
							<input type="hidden" name="nOfAttsFor{$input_name_base}Xml$q" id="nOfAttsFor{$input_name_base}Xml$q" value="0">
							<input type="text" maxlength="64" name="{$input_name_base}SHandle$q" onKeyUp="check_c_word(this)">
						</td>
						<td id="cell{$input_name_base}SDesc$q" class="layout-fg algC">
							<input type="text" maxlength="$max_len" name="{$input_name_base}SDesc$q">
						</td>
						<td id="addXmlAttributeButtonCellFor$q" class="layout-fg hollow" colspan="3">
							<a class="control-box" onClick="add_xml_attribute_to_s($q)">[Add attribute slot]</a>
						</td>
					</tr>
		
		END;
	
	$html .= <<<END_HTML
	
			<tr id="s_embiggen_button_row">
				<td class="layout-bg algC comfy" colspan="5">
					<input type="hidden" name="sNumRows" id="sNumRows" value="$init_n">
					<input type="hidden" name="inputNameBaseS" id="inputNameBaseS" value="$input_name_base">
					<button type="button" onclick="add_s_attribute_row();">Embiggen form</button>
				</td>
			</tr>
	
	END_HTML;
	
	return $html;
}


/**
 * Create the content-specification rows of a metadata-design form.
 * For metadata table install and metadata template creation.
 * 
 * Requires the metadata-embiggen javascript.
 */
function print_embiggenable_metadata_form(int $nrows = 5, bool $show_primary_classification = true, ?string $form_id = null) : string
{
	$id_prefix = $form_marker = '';
	if (!empty($form_id))
	{
		$id_prefix = $form_id . ':';
		$form_marker = 'form="'.$form_id.'" ';
	}
	
	/* Using the "list_valid" func: "None" is impossible in a metadata table;
	 * so is IDLINK (this kind of table is the target of an IDLINK!) */
	$options = '';
	foreach (list_valid_metadata_types(true) as $value)
		$options .= '<option value="' . $value . ($value == METADATA_TYPE_CLASSIFICATION ? '" selected>' : '">') . DESCRIBE_METADATA_TYPE[$value] . '</option>';

	$rows_html = <<<END_HTML
	
					<tr>
						<th class="layout-strong">&nbsp;</th>
						<th class="layout-strong">Handle for this field</th>
						<th class="layout-strong">Description for this field</th>
						<th class="layout-strong">Datatype of this field</th>
						COL_5_PLACEHOLDER
					</tr>
	
	END_HTML;
	
	$rows_html = str_replace('COL_5_PLACEHOLDER', ($show_primary_classification ? '<th class="layout-strong">Which field is the <br>primary classification?</th>' : ''), $rows_html) ;
	
	for ( $i = 1 ; $i <= $nrows ; $i++ )
	{
		$maxfield = HANDLE_MAX_FIELD;
		$maxdesc  = NONHANDLE_MAX_STRING;
		$rows_html .=  <<<END_HTML
		
						<tr>
							<td class="layout-fg">Field $i</td>
							<td class="layout-fg algC">
								<input {$form_marker}type="text" id="{$id_prefix}fieldHandle$i" name="fieldHandle$i" maxlength="$maxfield" onKeyUp="check_c_word(this)">
							</td>
							<td class="layout-fg algC">
								<input {$form_marker}type="text" id="{$id_prefix}fieldDescription$i" name="fieldDescription$i" maxlength="$maxdesc">
							</td>
							<td class="layout-fg algC">
								<select {$form_marker}id="{$id_prefix}fieldType$i" name="fieldType$i">
									$options
								</select>
							</td>
							COL_5_PLACEHOLDER
						</tr>
		
		END_HTML;
		$rows_html = str_replace('COL_5_PLACEHOLDER', 
					($show_primary_classification 
						? "<td class=\"layout-fg algC\"><input {$form_marker}type=\"radio\" id=\"{$id_prefix}primaryClassification:$i\" name=\"primaryClassification\" value=\"$i\"></td>" 
						: ''
						), $rows_html) ;
	}
	
	$n_of_cols = 4 + ($show_primary_classification ? 1 : 0);
	
	$rows_html .= <<<END_HTML
	
					<tr id="{$id_prefix}metadataEmbiggenRow">
						<td class="layout-bg algC comfy" colspan="$n_of_cols">
							<button type="button" onClick="add_metadata_form_row('{$form_id}')">Embiggen form</button>
						</td>
					</tr>
	
	END_HTML;
	
	return $rows_html;
}


/**
 * Create the content-specification rows of a catdesc-template-design form.
 * 
 * Requires the metadata-embiggen javascript.
 */
function print_embiggenable_catdesc_form(int $nrows = 7, ?string $form_id = null) : string
{
// FIXME  apply the id-prefix modifications used in the func above to this func.
	$form_marker = '';
	if (!empty($form_id))
		$form_marker = 'form="'.$form_id.'" ';
	
	$rows_html = <<<END_HTML
	
					<tr>
						<th class="layout-strong">Category handle</th>
						<th class="layout-strong">Category description</th>
					</tr>
	
	END_HTML;
	
	for ( $i = 1 ; $i <= $nrows ; $i++ )
	{
		$maxcat  = HANDLE_MAX_CATEGORY;
		$maxdesc = NONHANDLE_MAX_STRING;
		$rows_html .=  <<<END_HTML
		
					<tr>
						<td class="layout-fg algC">
							<input {$form_marker}type="text" id="catHandle$i" name="catHandle$i" size="25" maxlength="$maxcat" class="handle-needed">
						</td>
						<td class="layout-fg algC">
							<input {$form_marker}type="text" id="catDescription$i" name="catDescription$i" size="60" maxlength="$maxdesc">
						</td>
					</tr>
		
		END_HTML;
	}
	
	$rows_html .= <<<END_HTML
	
				<tr id="catdescEmbiggenRow">
					<td class="layout-bg algC comfy" colspan="2">
						<button type="button" onClick="add_catdesc_form_row()">Embiggen form</button>
					</td>
				</tr>
	
	END_HTML;
	
	return $rows_html;
}

/**
 * Generates a single html "select" which functions as a language picker. Its name is "language".
 *  
 * @param  string $form_id      ID of the form the select belongs to. If empty, the resulting select 
 *                              must be placed between the form and slash-form.  
 * @param  string $preselect    An ISO 639 code (3 byte) to preselect. If none, defaults to "und". 
 * @return string               HTML from selsct to slash-select.
 */
function print_lang_selector(?string $form_id = null, ?string $select_id = null, ?string $preselect = null) : string
{
	require_once("../lib/iso639-lib.php");
	
					// Note, doing it this way creates a very long list, several of which are families not languages. 
					// a "picker" would be better (cascading menus -- see, for instance, https://api.jqueryui.com/menu/
					// with areas such as
					/*
						Leave undetermined
						<hr>
						Europe and North Asia
						Americas
						Africa and West Asia
						Central and South Asia
						Asia-Pacific
						
						amnd a list of langs uner each area. 
						
						OR a suggest-completion : see, for instance https://api.jqueryui.com/autocomplete/
						(with an addiitonal "check-if-valid" to make sure we don't submit something not on the list.)
						empty would = leave undetermined which would be rendered by being the placeholder. 
					*/
					// But the below will do for now.
	
	$form_att = empty($form_id) ? '' : " form=\"$form_id\"";
	$id_att   = empty($select_id) ? '' : " id=\"$select_id\"";
	
	$html = "<select $form_att$id_att name=\"language\">\n\t\t\t\t\t\t<option value=\"und\"". (empty($preselect) ? ' selected' : '') .' required>Leave unspecified</option>';
	
	foreach(Iso639::get_map_for_selects() as $code => $lang)
		$html .= "\n\t\t\t\t\t\t<option value=\"$code\"" . ($code==$preselect ? ' selected' : '') . ">$lang</option>";
	
	$html .= "\n\t\t\t\t\t</select>";
	
	return $html;
}


/** 
 * Returns a 4-column file selector for files in a given folder (radio button style).
 *
 * The first tr contains the col headers. Neither <form> nor </form> is included.
 * 
 * The columns are: 1) radio button, 2) filename, 3) size, 4) last-modification date.
 * 
 * @param  string $input_name       String to use as basis for the name/id attributes 
 *                                  of the form inputs. Default is 'dataFile'.
 * @param  string $form_target      String to use as the "form" attribute for inputs. 
 *                                  If an empty value is given (the default) no form 
 *                                  attributes will be inserted.
 * @param  string $from_dir_path    Path to the directory to get files from. If not 
 *                                  supplied, defaults to the config file's upload directory.
 * @param  bool   $show_compressed  Default false; if true, compressed files will not be
 *                                  skipped. 
 * @return string                   Resulting HTML (a tr element or sequence of tr elements). 
 *                                  The second column always has colspan=2.
 */
function print_file_selector(string $input_name = 'dataFile', string $form_target = '', ?string $from_dir_path = null, bool $show_compressed = false) : string
{
	global $Config;
	
	if (is_null($from_dir_path))
		$path = $Config->dir->upload;
	else 
		$path = rtrim($from_dir_path, "\\/");
	
	if (! is_dir($path))
		$file_list = [];
	else
	{
		$file_list = scandir($path);
		natcasesort($file_list);
	}
	
	if (empty($file_list))
		return '<tr><td class="layout-fg algC" colspan="5"><p>There are no files available.</p></td></tr>';
	
	$rows = <<<END_ROW
	
				<tr>
					<th class="layout-strong">Use?</th>
					<th class="layout-strong" colspan="2">Filename</th>
					<th class="layout-strong">Size</th>
					<th class="layout-strong">Date modified</th>
				</tr>
	
	END_ROW;
	
	$target = empty($form_target) ? '' : ' form="' . $form_target . '"';
	
	foreach ($file_list as $f)
	{
		$file = "$path/$f";
		if (!is_file($file)) 
			continue;
		
		if (!$show_compressed)
			if (ARCHIVE_TYPE_UNKNOWN != compressed_file_get_type($file))
				continue;
		
		$f_esc = escape_html($f);

		$stat       = stat($file);
		$size_show  = render_bytes($stat['size']);
		$mtime_show = date(CQPWEB_UI_DATE_FORMAT, $stat['mtime']);
		
		$rows .= <<<END_OF_HTML
		
					<tr>
						<td class="layout-fg algC">
							<input type="radio" id="$input_name:$f" name="$input_name" value="$f"$target> 
						</td>
						<td class="layout-fg algL" colspan="2">
							<label for="$input_name:$f">$f_esc</label>
						</td>
						<td class="layout-fg algR">$size_show</td>
						<td class="layout-fg algC">$mtime_show</td>
					</tr>
		
		END_OF_HTML;
	}
	return $rows;
}


/**
 * 
 * @param  int      $index_to_select
 * @return string                     Series of <option> elements.
 */
function print_colloc_statistic_form_options(int $index_to_select) : string
{
	$statistic = load_statistic_info();
	$output = '';
	foreach($statistic as $index => $s)
		if ($index != COLLSTAT_RANK_FREQ)		/* this one is saved for last */
			$output .= "\n\t<option value=\"$index\"". ($index_to_select == $index ? ' selected' : '') . ">{$s['desc']}</option>";
	$output .= "<option value=\"".COLLSTAT_RANK_FREQ.'" ' . ($index_to_select == COLLSTAT_RANK_FREQ ? ' selected' : '') . ">{$statistic[COLLSTAT_RANK_FREQ]['desc']}</option>";
	return $output;
}


/**
 * 
 * @param  int      $index_to_select
 * @return string                     Series of <option> elements.
 */
function print_key_statistic_form_options(int $index_to_select) : string
{
	$stat_option_value = [];
	foreach( [KEYSTAT_LR_WITH_LL, KEYSTAT_LOGLIKELIHOOD, KEYSTAT_LR_WITH_CONFINT, KEYSTAT_LR_CONSERVATIVE, KEYSTAT_LR_UNFILTERED] as  $kstat )
		$stat_option_value[$kstat] = ' value="'. $kstat . '"' .($kstat == $index_to_select ? ' selected' : '');
	
	return <<<END_HTML
	
							<option{$stat_option_value[KEYSTAT_LR_WITH_LL]}     >Log Ratio with Log-likelihood filter</option>
							<option{$stat_option_value[KEYSTAT_LOGLIKELIHOOD]}  >Log-likelihood</option>
							<option{$stat_option_value[KEYSTAT_LR_WITH_CONFINT]}>Log Ratio with Confidence Interval filter</option>
							<option{$stat_option_value[KEYSTAT_LR_CONSERVATIVE]}>Log Ratio (conservative estimate)</option>
							<option{$stat_option_value[KEYSTAT_LR_UNFILTERED]}  >Log Ratio (unfiltered)</option>
	
	END_HTML;
}


/**
 * Print a select element which offers a choice of colour scheme 
 * 
 * @param  string $form_id   If specified and not empty, select will have a form attribute referencing this ID.
 * @param  string $selected  If specified and not empty, option given will be selected (and the disabled first option suppressed). 
 * @return string            HTML select element with id and name "colourScheme". 
 */
function print_select_colour_scheme(?string $form_id = null, ?string $selected = null) : string
{
	$colour_words = array_keys(list_css_colour_schemes());
	/*[ 
			'blue','yellow','green','red','brown','purple','navy','lime','aqua',
			'neon','dusk','gold','rose','teal','charcoal','motley','cinema',
			'skin','floral','cappuccino','forest','grapefruit','periwinkle',
			'saffron','snowflake',
	];*/
	
	if (!empty($selected))
		$selected_type = type_of_colour_scheme($selected);
	
	switch(true)
	{
	case is_null($selected):
		$options = "<option disabled selected value=\"\">Choose a color scheme...</option>\n";
		foreach ($colour_words as $col)
			$options.= "\t\t\t\t\t<option value=\"$col\">" . ucfirst($col) . "</option>\n";
		break;
		
	case 'json' == $selected_type:
		return "(Custom color scheme, not editable yet)";
		break;
		
	case 'cssfile' == $selected_type:
		return "(CSS URL, not editable yet)";
		break;
		
	case 'handle' == $selected_type:
		if ('' == ($selected = trim($selected, '~')))
			$selected = 'blue';
		$options = "\n";
		foreach ($colour_words as $col)
			$options.= "\t\t\t\t\t<option value=\"$col\"".($col==$selected?' selected':'').'>'. ucfirst($col). "</option>\n";
		break;
	}
	
	$form = empty($form_id) ? '' : " form=\"$form_id\"";
	
	return <<<END_OF_SELECT
	
					<select id="colourScheme"$form name="colourScheme" required>
						$options
					</select>
	
	END_OF_SELECT;
}


//TODO depracate me!
/**
 * Creates an HTML page footer for all flavours of CQPweb page.
 * 
 * It takes a single argument: the required help page to link to
 * (see short codes in help.inc.php) -- if an empty value or nothing is passed,
 * no help link will appear. 
 * 
 * If "hello" is passed, a link to the "Hello" page appears.
 * 
 * @param  string $helplink  String with a short code for a help video to embed in the linked help page.
 * @return string            String containing the HTML page footer.
 */
function print_html_footer(?string $helplink = null) : string
{
	global $User;
// 	global $Config;
	
	$v = CQPWEB_VERSION;
	
	if (!empty($helplink))
		$help_cell = '<a class="copynote" href="help.php?'
			. ($helplink == 'hello' ? 'ui=hello' : 'vidreq=' . $helplink) 
			. '" target="cqpweb_help_browser">' 
			. ($helplink ==  'hello' ? 'Help! on CQPweb' : 'Help! for this screen') 
			. '</a>'
			;
	else
		$help_cell = '&nbsp;';
	
	if (! (isset($User) && $User->logged_in))
		$lognote = 'You are not logged in';
	else
		$lognote = "You are logged in as user [{$User->username}]";
	
// 	$js_path = ($Config->run_location == RUN_LOCATION_MAINHOME ? 'jsc' : '../jsc');
	
	
// objecty funcs up top don't have this; when they do will be kinda like this:
//			$writer->do_tooltip_system_styles();
//			$writer->do_tooltip_system_script();
//			$writer->do_tooltip_template();
//			(or the like)
	
	

//<!-- tmp implementation -->
$skb_html = '';
$kb_ui_label_data = $kb_layout_data = '';
if (($User->username ?? '')  != 'andre w') goto SKIP_SKB;
foreach(get_included_files() as $x)
	if (preg_match('/keyboard-lib\.php$/', $x))
	{
// this is an experiment with awholly arbitrary example. Plugin reg not used yet. 
		$kb = PhysicalKeyboard::create_standard_keyboard();
		$skb_html = "\n" .  $kb->print_as_html('cqpweb-soft-keyboard')  .  "\n\n";
		if (!empty($kb->ui_css_width))
			$skb_html .= "\n\n<style>\n#cqpweb-soft-keyboard {--keyboard-width:" . $kb->ui_css_width . "; }\n\n</style>\n\n" ;

require_once('../lib/plugin-lib.php');
require('../lib/plugins/builtin/SoftKeyboard/GeorgianMkhedruliKeyboard.php');
//require('../lib/plugins/builtin/SoftKeyboard/RomanisedIndicPad.php');
		$g = new GeorgianMkhedruliKeyboard([]);
//		$g = new SinhalaKeyboard([]);
//		$g = new RomanisedIndicPad([]);
//		$kb_layout_data = print_latedata_complex('soft-keyboard-layout', $g->get_layout());
		$kb_layout_data = print_latedata_complex('soft-keyboard-keymap', $g->get_keymap());
		$kb_ui_label_data = print_latedata_complex('soft-keyboard-ui-label', $g->get_ui_label());
//var_dump($kb_ui_label);exit;
	}
SKIP_SKB:

	/* cqpweb-new-ui: the site's own footer text and the Help, news and Contact links */
	require_once(__DIR__ . '/cqpweb-new-ui/brand-lib.php');
	$cp_footer = cp_footer_html();

	return <<<END_FOOTER_HTML
	
		<footer>
			<hr>
			<table class="layout-frame fullwidth">
				<tr>
					<td class="footbox algL" width="33%">CQPweb v$v &#169; 2008-2023</td>
					<td class="footbox algC" width="33%">$help_cell</td>
					<td class="footbox algR" width="33%">$lognote</td>
				</tr>
			</table>
		</footer>
		$cp_footer
		<div id="templateForToolTip" class="floatingToolTip">
			<table class="floatingToolTipFrame">
				<tr>
					<td class="floatingToolTipTarget"></td>
				</tr>
			</table>
		</div>
		

		$skb_html
		$kb_layout_data
		$kb_ui_label_data

		</body>
	
	</html>
	
	END_FOOTER_HTML;
}



// object not in use yet really.
/**
 * Object which generates the HTML for the beginning and end of a CQPweb UI "screen".
 */
class UiPageFramer
{
	private $title;
	
	private $helplink = '';
	
	private $js_builtin = [];
	private $js_extra   = [];
	
	private $css_builtin   = [];
	private $css_extra     = [];
	private $css_literals  = [];
	private $colour_scheme;
	
	private $app_data = [];
	
	private $done_begin_page = false;
	
	/**
	 * Optionally set title & colour scheme; can also be done via setters. 
	 * 
	 * @param string $title
	 * @param string $colour_scheme
	 */
	public function __construct(?string $title = null, ?string $colour_scheme = null)
	{
		$this->title = $title ?? '';
		$this->colour_scheme = $colour_scheme ?? '';
	}
	
	/** @param string $title      New title value. Not checked, not escaped. */
	public function set_title(string $title) : void
	{
		$this->title = $title;
	}
	
	/** @param string $colour_scheme    New colour scheme value. Not checked, not escaped. */
	public function set_colour_scheme(string $colour_scheme) : void 
	{
		$this->colour_scheme = $colour_scheme;
	}
	
	/** 
	 * Sets a string with a short code for a help video to embed in the linked help page.
	 * @param   string $helplink   New help code. 
	 */
	public function set_help(string $helplink) : void 
	{
		$this->helplink = $helplink;
	}
	
	/**
	 * Specify that a builtin CSS should be declared within the header.
	 * 
	 * @param string  $css_name   A name (file in normal location) without js suffix.
	 */
	public function add_builtin_css(string $css_name) : void
	{
		$this->css_builtin[] = $css_name;
		$this->css_builtin = array_unique($this->css_builtin);
	}
	
	/**
	 * Specify extra (user-defined) CSS to be declared within the header.
	 * 
	 * @param  string $css_name   Name of the CSS file (with no leading path to "./css/extra" or trailing ".css".
	 */
	public function add_extra_css(string $css_name) : void 
	{
		$this->css_extra[] = $css_name;
		$this->css_extra = array_unique($this->css_extra);
	}
	
	/**
	 * Add literal CSS code to be inserted AFTER all the default and extra CSS.
	 * 
	 * @param string $css_code     CSS code - do not include enclosing style tags!
	 */
	public function add_literal_css(string $css_code) : void
	{
		$this->css_literals[] = $css_code;
	}
	
	/**
	 * Specify that a JSC builtin should be declared within the header.
	 * 
	 * @param string  $js_name   A name (file in normal location) without js suffix.
	 */
	public function add_builtin_js(string $js_name) : void
	{
		$this->js_builtin[] = $js_name;
		$this->js_builtin = array_unique($this->js_builtin);
	}
	
	/**
	 * Specify that 1+ extra (user-defined) js files should be declared within the header.
	 * 
	 * @param string $js_name   A name (file in ./jsc/extra/) without js suffix.
	 */
	public function add_extra_js(string $js_name) : void
	{
		$this->js_extra[] = $js_name;
		$this->js_extra = array_unique($this->js_extra);
	}
	
	/**
	 * Set a variable in the browser application datastore.
	 * 
	 * (Via the header if not sent yet; via the latedata mechanism otherwise.)
	 * 
	 * @param string $varname   Name of var to set, in the "with-dashes" format 
	 *                          (will convert to "camelCase" dataset key when used in JS; 
	 *                          this system forbids use of uipper case letters or any dots, 
	 *                          colons or underscores even though they're allowed in an att name).
	 *                          A name in an invalid form aborts CQPweb. 
	 *                          If the variable was previously set, this overwrites it.
	 * @param mixed $value      Value to give to the variable. It will be HTML-escaped 
	 *                          prior to insertion, so no need to do that in advance.
	 *                          If it's an object or array, it will also be JSON encoded.
	 */
	public function add_app_data(string $varname, /* mixed */ $value) : void
	{
		/* we are strict about the varname because the Mozilla documentation, at least, is a bit ambiguous 
		 * about what is allowed other than the canonical "data-some-variable" --> "dataset.someVarname" */
		if (!preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $varname))
			exiterror("UiPageFramer: Application data issue - the variable name '$varname' is not in canonical 'some-variable-name' form.");
		
		if (is_object($value) || is_array($value))
			$value = json_encode($value, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_INVALID_UTF8_SUBSTITUTE);
		$this->app_data[$varname] = $value;
	}
		
	public function do_begin_page() : void
	{
		global $Config;
		global $User;
		
		$url_prefix = get_url_prefix();
		
		/* override the CSS url with accessibility style option if enabled;
		 * isset() check necessary because in case of error this can be called before $User setup. */
		if (isset($User) && $User->logged_in && $User->css_monochrome)
			$this->css_url = ($Config->run_location == RUN_LOCATION_MAINHOME ? 'css' : '../css') . '/CQPweb-user-monochrome.css';
		
		/* set the generic header before starting the write  */
		if (!headers_sent())
			header('Content-Type: text/html; charset=utf-8');
		
		?><!DOCTYPE html>
	<html lang="en">
		<head>

		<?php 
		echo print_application_meta($this->app_data);
		$this->app_data = [];
		?>
		<title><?= $this->title ?></title>
		<meta charset="UTF-8">
		
		<?php /* cqpweb-new-ui: version tags (cp_asset_url); CQPweb wrote these two without "jsc/" */ ?>
		<script       src="<?= cp_asset_url($url_prefix, 'jsc/depend/jquery.js') ?>"></script>
		<script defer src="<?= cp_asset_url($url_prefix, 'jsc/always.js') ?>"></script>
		
		<?php
		/* jQuery can't be deferred safely. Must be loaded synchronously. For reason why see:
		 *   https://stackoverflow.com/questions/37272020/jquery-loaded-async-and-ready-function-not-working
		 *   https://github.com/jquery/jquery/issues/3271
		 * but we can "defer" the rest, starting with "always", as above. */
		foreach ($this->js_builtin as $js)
			echo "\t\t<script defer src=\"", cp_asset_url($url_prefix, "jsc/$js.js"), "\"></script>\n";
		
		foreach ($this->js_extra as $js)
			echo "\t\t<script defer src=\"", cp_asset_url($url_prefix, "jsc/extra/$js.js"), "\"></script>\n";
		
		/* js now done - move on to css. */
		echo print_header_css_embed($this->colour_scheme); /* cqpweb-new-ui: CQPweb called this without echo, so the page got no stylesheet */

		/* Extra CSS files & literals, if supplied, override the colour scheme and system CSS code. */
		foreach($this->css_extra as $css)
			echo "\t\t<link rel=\"stylesheet\" type=\"text/css\" href=\"", cp_asset_url($url_prefix, "css/extra/$css.css"), "\">\n";

		foreach($this->css_literals as $code)
			echo "\n\n\t<style>\n$code\n\t</style>\n";
		?>
		
		</head>
		
		<?php
		echo "\n\t<body>\n";
		
		do_archaic_browser_warning();
		
		/* HTML of the page beginning is all done. */
	}
	
	/**
	 * Function to call when we're all done. 
	 */
	public function do_end_page() : void
	{
		global $User;
		
		if (!empty($this->helplink))
			$help_cell = '<a class="copynote" href="help.php?'
				. ($this->helplink == 'hello' ? 'ui=hello' : 'vidreq=' . $this->helplink) 
				. '" target="cqpweb_help_browser">' 
				. ($this->helplink ==  'hello' ? 'Help! on CQPweb' : 'Help! for this screen') 
				. '</a>'
				;
		else
			$help_cell = '&nbsp;';
		
		if ($User && $User->logged_in)
			$lognote = "You are logged in as user [{$User->username}]";
		else
			$lognote = 'You are not logged in';
		?>
		
		<footer>
			<hr>
			<table class="layout-frame fullwidth">
				<tr>
					<td class="footbox algL" width="33%">CQPweb v<?= CQPWEB_VERSION ?> &#169; 2008-2020</td>
					<td class="footbox algC" width="33%"><?= $help_cell ?>	</td>
					<td class="footbox algR" width="33%"><?= $lognote, "\n"; ?></td>
				</tr>
			</table>
		</footer>
		<?php require_once(__DIR__ . '/cqpweb-new-ui/brand-lib.php'); echo cp_footer_html(); /* cqpweb-new-ui: site footer, Help, news and Contact links */ ?>
		<div id="templateForToolTip" class="floatingToolTip">
			<table class="floatingToolTipFrame">
				<tr>
					<td class="floatingToolTipTarget"></td>
				</tr>
			</table>
		</div>
		
		<?php
		/* late data : results from calling add_app_data_* AFTER the header is written */ 
		foreach($this->app_data as $var => $val)
			echo print_latedata_simplex($var, $val), "\n";
			
		echo "\n\t</body>\n</html>\n";
		/* page footer done. */
	}
}


/**
 * Prints out the application-name meta-element.
 * 
 * Takes a hash of variables to be inserted into the global application datastore 
 * using the "data-" atrtribute method. 
 * 
 * The "data-url-prefix" is always added automagically.
 * 
 * If the right config vvariable is set, "data-pwd-blacklist" and
 * "data-pwd-min-length" are also added automagically.
 * 
 * @param  array  $data_hash  Map of "data-xxxx" to string values. Values will be escaped.
 * @return string             HTML meta element.
 */
function print_application_meta(array $data_hash = []) : string
{
	global $Config;
	
	$dataset = 'data-url-prefix="' . get_url_prefix() ."\"\n\t\t";
	
	foreach($data_hash as $var => $val)
		$dataset .= "data-$var=\"".escape_html((string)$val)."\"\n\t\t";

	/* scripts must set this variable... */
	if ($Config->transmit_password_choose_or_change_data)
	{
		include_once("../lib/user-password-blacklist.php");
		initialise_password_blacklist();
		$dataset .= 'data-pwd-blacklist="' . escape_html(json_encode(array_keys($Config->user_password_blacklist))) . '"'; 
		$dataset .= 'data-pwd-min-length="' . $Config->password_minimum_length . '"';
	}
	
//	// bodge, bodge, bodge!!!!!!!!!
	if (isset($Config->transmit_we_want_a_cloud))
		$dataset .= 'data-we-want-a-cloud="' . $Config->transmit_we_want_a_cloud . '"';
	
	return <<<END_HTML
	
		<!-- CQPweb application data -->
		<meta
			id="CQPwebGlobalApplicationData"
			name="application-name" 
			content="CQPweb"
	
			$dataset
		>
	
	END_HTML;
}


/** Depracated. Use the const array directly. */
function get_url_prefix() : string
{
	global $Config;
	
	return RELATIVE_URL_BASE[$Config->run_location];
}

/**
 * cqpweb-new-ui: the address of one of CQPweb's own script or stylesheet files, with "?v=" and the
 * file's modification time. A browser treats a new "?v=" as a new file and fetches it, so a copy
 * cached before an update is never used again. Without the tag, copies of jsc/always.js fetched
 * in August 2026 (served then with no Cache-Control) were still being run in November 2026, after
 * the CQPweb update of 23 October, and the "please wait" spinner asked for "undefinedcss/img/throbber.gif".
 *
 * @param string $url_prefix  The page's URL prefix (get_url_prefix()).
 * @param string $rel         The file's path from the CQPweb root, for example "jsc/always.js".
 */
function cp_asset_url(string $url_prefix, string $rel) : string
{
	$f = __DIR__ . '/../' . $rel;
	return $url_prefix . $rel . (is_file($f) ? '?v=' . filemtime($f) : '');
}


/** Prints the style element for the header. */
function print_header_css_embed(string $colour_scheme) : string
{
	global $Config;
	global $User;
	
	$url_prefix = get_url_prefix();
	$cp_system_css = cp_asset_url($url_prefix, 'css/0system.css'); /* cqpweb-new-ui: version tag */
	
	/* override the CSS url with accessibility style option if enabled;
	 * isset() check necessary because in case of error this can be called before $User setup. */
	if (isset($User) && $User->logged_in && $User->css_monochrome)
		return <<<END_HTML_CSS
		
			<style>
				/* colors for monochrome UI */
				:root {
					--colour-layout-fg      : white ;
					--colour-layout-bg      : white ;
					--colour-layout-strong  : white ;
					--colour-layout-contrast: white ;
					--colour-layout-data2   : white ;
					--colour-layout-outline : black ;
					--colour-text-normal    : black ;
					--colour-text-faint     : black ;
					--colour-text-bright    : black ;
					--colour-tooltip-frame  : black ;
					--colour-tooltip-bg     : white ;
					--colour-tooltip-text   : black ;
				}
			</style>
			<link rel="stylesheet" type="text/css" href="{$cp_system_css}">
			<style>
				/* add an override for table borders in monochrome : just one pixel, collapse on */
				table,td,th {
					border-width    : 0.0625rem;
					border-collapse : collapse;
				}
				/* override the normal "external" img marker */
				a.external::after {
					content         : url(../css/img/ext-link-monochrome.png);
				}
			</style>
		
		END_HTML_CSS;
	else
	{
		$root = print_css_root_variables($colour_scheme);
		/* for explanation of the following line: see doc on the config variable "colour_scheme_rainbow_override" */
		$rainbow = $Config->colour_scheme_rainbow_override ? "\n\t<link rel=\"stylesheet\" type=\"text/css\" href=\"" . cp_asset_url($url_prefix, 'css/1rainbow.css') . "\">" : '';
		/* cqpweb-new-ui: palettes, fonts, skin and day/night switch (lib/cqpweb-new-ui/appearance-lib.php) */
		require_once(__DIR__ . '/cqpweb-new-ui/appearance-lib.php');
		$corpora_pro_head = cp_appearance_head();
		return <<<END_HTML_CSS
		
			<style>
			$root
			</style>
			<link rel="stylesheet" type="text/css" href="{$cp_system_css}">$rainbow
			$corpora_pro_head
		
		END_HTML_CSS;
	}
}

/** function to change aaa-bbb-ccc to aaaBbbCcc */
function attribute_style_to_property_style(string $name) : string
{
	return preg_replace_callback('/-([a-z])/', function ($m) { return strtoupper($m[1]); }, $name);
}

/**
 * Creates for the HTML a "latedata"-class div, which will be scanned on setup and moved to the central datastore. 
 * 
 * The same "dash-style" to "camelCase" as would be expected should be implemented.  
 * 
 * Use to add datastore variables after the header has printed. 
 * 
 * @param  string  $varname  Name of the variable in 'abc-abc-abc' format. 
 * @param  string  $value    Value it is to be given (strings or stringifiables  only!); will be HTML-escaped.
 * @return string            String containing a div that will be invisible (and then deleted). 
 */
function print_latedata_simplex(string $varname, string $value) : string
{
	$value = escape_html($value);
	$varname = escape_html($varname); /* shouldn't be necessary.... but just in case. */
	$varname = attribute_style_to_property_style($varname);
	return "<div class=\"latedata\" data-varname=\"$varname\" data-value=\"$value\"></div>";
}

/**
 * Inserts into the HTML a "latedata"-class div, which will be scanned on setup and moved to the central datastore. 
 * 
 * Use to add datastore variables after the header has printed. 
 * 
 * @param  string  $varname  Name of the variable in 'abc-abc-abc' format. 
 * @param  mixed   $value    Value it is to be given: anything that can be json-encoded.
 * @return string            String containing a div that will be invisible (and then deleted). 
 */
function print_latedata_complex(string $varname, /* mixed */ $value) : string
{
	$json = json_encode($value, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_INVALID_UTF8_SUBSTITUTE);
	return print_latedata_simplex($varname, $json.'~~~json');
	/* ~~~json is the flag that makes this variable be auto-json-decoded when accessed via the usual routes. */
}


/**
 * Create an HTML header (everything from [html] to [body],
 * which specifies the title as provided, sets up CSS,
 * and finally imports the specified JavaScript files.
 * 
 * It also sets the Content-Type via header().
 * 
 * @param  string $title         Note this WILL NOT be HTML escaped. If needed, caller must. 
 * @param  string $colour_scheme Colour scheme content.
 * @param  array  $js_scripts    Array of names of JS to include.
 * @param  array  $extra_css     Array of extra CSS to include.
 * @return string                The HTML header string ready to be printed. 
 */
function print_html_header(string $title, string $colour_scheme = '', array $js_scripts = [], array $extra_css = []) : string
{
	$url_prefix = get_url_prefix();
	
	$hdr = "<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n\t<meta charset=\"UTF-8\">\n"
		. print_application_meta()
		. "\t<title>$title</title>\n"
		. print_header_css_embed($colour_scheme)
		;
	
	/* Extra CSS files, if supplied, add additional styles for whatever reason. They do not get overridden. */
	foreach($extra_css as $extra)
		$hdr .= "\t<link rel=\"stylesheet\" type=\"text/css\" href=\"" . cp_asset_url($url_prefix, "css/extra/$extra.css") . "\">\n";
	
	/* jQuery can't be deferred safely. Must be loaded synchronously. For reason why see:
	 *   https://stackoverflow.com/questions/37272020/jquery-loaded-async-and-ready-function-not-working
	 *   https://github.com/jquery/jquery/issues/3271
	 * but we can "defer" the rest, starting with "always/tooltip". As follows. */ 
	
	/* cqpweb-new-ui: version tags (cp_asset_url) */
	$cp_jquery  = cp_asset_url($url_prefix, 'jsc/depend/jquery.js');
	$cp_always  = cp_asset_url($url_prefix, 'jsc/always.js');
	$cp_tooltip = cp_asset_url($url_prefix, 'jsc/tooltip.js');
	$hdr .= <<<END_HTML
	
		<script       src="{$cp_jquery}"></script>
		<script defer src="{$cp_always}"></script>
		<script defer src="{$cp_tooltip}"></script>
	
	END_HTML;
	
	foreach ($js_scripts as $js)
		if (is_file("../jsc/$js.js"))
			$hdr .= "\t<script defer src=\"" . cp_asset_url($url_prefix, "jsc/$js.js") . "\"></script>\n";
		else
			$hdr .= "\t<script defer src=\"" . cp_asset_url($url_prefix, "jsc/extra/$js.js") . "\"></script>\n"; /* sopmething of a bodge. But, the UiPageFramer has a much better way. */
	$hdr .= "\n</head>\n<body>\n";
	
	/* cqpweb-new-ui: top bar with Homepage and Back-to-corpus buttons (lib/cqpweb-new-ui/appearance-lib.php) */
	require_once(__DIR__ . '/cqpweb-new-ui/appearance-lib.php');
	$hdr .= cp_navbar();
	
	if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match("/\bTrident\b/", $_SERVER['HTTP_USER_AGENT']))
		$hdr .= <<<END_OF_WARNING
		
				<table id="browserIsInternetExplorerWarning" class="layout-frame fullwidth">
					<tr>
						<td class="layout-contrast">
							<strong>You appear to be using Internet Explorer</strong>.
							This browser is not fully compatible with CQPweb - some things will not work.
							Try Edge, Chrome or Firefox instead.
						</td>
					</tr>
				</table>
		
		END_OF_WARNING;
	
	/* also set the generic header (will only be sent when something is echo'd, though) */
	if (!headers_sent())
		header('Content-Type: text/html; charset=utf-8');
	
	return $hdr;
}


/**
 * Warn users of non-modern browsers (like Internet Explorer) that they should not expect CQPweb to work.
 * Nothing is emitted to browsers that are up to date!
 */
function do_archaic_browser_warning() : void
{
	/* We only look for Trident at present, since that's the only browser brand no longer has updates. */
	if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match("/\bTrident\b/", $_SERVER['HTTP_USER_AGENT']))
	{
		?>
		
		<table id="browserIsInternetExplorerWarning" class="layout-frame fullwidth">
			<tr>
				<td class="layout-contrast">
					<strong>You appear to be using Internet Explorer</strong>.
					This browser is not fully compatible with CQPweb - some things will not work.
					Try Firefox, Edge, or Chrome instead.
				</td>
			</tr>
		</table>
		
		<?php
	}
}


/**
 * The login form is used in more than one place, 
 * so this function puts the code in just one place.
 * 
 * @param  string $loc_after  URL to navigate to afetr successful login.
 * @return string             HTML (form element with table in it) of the login form.
 */
function print_login_form(string $loc_after = '') : string
{
	// TODO - implement the measures discussed here: https://blog.codinghorror.com/the-god-login/
	
	global $Config;
	
	if ($Config->run_location == RUN_LOCATION_USR)
		$pathbegin = '';
	else if ($Config->run_location == RUN_LOCATION_MAINHOME)
		$pathbegin = 'usr/';
	else
		/* in a corpus, or in adm */
		$pathbegin = '../usr/';
	
	/* pass through a location after, if one was given */
	$input_loc_after = (empty($loc_after) 
							? '' 
							: '<input type="hidden" name="locTo" value="'.escape_html($loc_after).'">'
							);
	
	$uname_len = NONHANDLE_MAX_USERNAME;
	$pword_len = NONHANDLE_MAX_STRING;
	
	return <<<END_OF_HTML
	
				<form action="{$pathbegin}useracct-act.php" method="post">
					<input type="hidden" name="userAction" value="userLogin">
					$input_loc_after
					<table class="basicbox" style="margin:auto">
						<tr>
							<td class="basicbox nowrap">
								<label for="login:username">Enter your username:</label>
							</td>
							<td class="basicbox">
								<input type="text" id="login:username" name="username" maxlength="$uname_len">
							</td>
						</tr>
						<tr>
							<td class="basicbox nowrap">
								<label for="login:password">Enter your password:</label>
							</td>
							<td class="basicbox nowrap">
								<!-- bcrypt uses only 72 bytes of password, but we allow up to $pword_len. -->
								<p style="display:inline-block;">
									<input id="passwordTextInput" type="password" name="password" maxlength="$pword_len">
								</p>
								<p id="capsLock:passwordTextInput" style="display:none">
									<strong>WARNING</strong>: Caps Lock seems to be on!
								</p>
							</td>
						</tr>
						<tr>
							<td class="basicbox nowrap">
								<label for="persist:1">
									Stay logged in
									<br>
									on this computer:
								</label>
							</td>
							<td class="basicbox">
								<input type="checkbox" id="persist:1" name="persist" value="1">
							</td>
						</tr>
						<tr>
							<td class="basicbox algR">
								<button type="submit">Log in</button>
							</td>
							<td class="basicbox algL">
								<button type="reset">Clear form</button>
							</td>
						</tr>
					</table>
				</form>
	
	END_OF_HTML;
}


/**
 * Creates simple navlinks to go the bottom of a table that prints out the content of a numbered
 * SQL query (or similar).
 * 
 * @param  string $base_url         Basic URL to which parameters will be added. Should end in ? if no parameters, but not in &.
 * @param  int    $first_shown      1-based integer ix of the row of the query that is the FIRST currently shown.
 * @param  int    $first_unshown    1-based integer ix of the row of the query that is the FIRST row after the LAST currently shown.
 * @param  int    $n_in_list        1-based integer ix of the LAST row of the query. (Get from mysqli_num_rows().)
 * @param  int    $per_page         Number of rows shown on each page.
 * @param  string $label_upwards    Label for the link on the left that moves back up the list.
 * @param  string $label_downwards  Label for the link on the right that moves down the list.
 * @return string                   HTML of a simple navlink bar as HTML table.
 */
function print_simple_navlinks(string $base_url, int $first_shown, int $first_unshown, int $n_in_list, int $per_page, string $label_upwards, string $label_downwards) : string
{
//flaunt(func_get_args());
	/* FURTHER UP the list */
	
	$up_content = '&lt;&lt;&nbsp;[' . escape_html($label_upwards) . ']';
	$up_href = '';
	
	if ($first_shown > 1)
	{
		$new_begin_at = max( [ 1, $first_shown - $per_page ] ) - 1;
		/* URL is 0-indexed, this func's parameters are 1-indexed: thus, minus 1 */
		$up_href = $base_url . (0 != $new_begin_at ? "&beginAt=$new_begin_at" : '');
	}
	
	if (empty($up_href))
		$up_a = "<span style=\"display:block; padding:0.8rem;\">$up_content</span>";
	else
		$up_a = "<a class=\"control-box\" href=\"$up_href\">$up_content</a>";
	
	/* FURTHER DOWN the list */
	
	$down_content = '[' . escape_html($label_downwards) . ']&nbsp;&gt;&gt;';
	$down_href = '';
	
	if ($first_unshown <= $n_in_list)
		$down_href = $base_url . '&beginAt=' . ($first_unshown - 1);
		/* URL is 0-indexed, this func's parameters are 1-indexed: thus, minus 1 */
	if (empty($down_href))
		$down_a = "<span style=\"display:block; padding:0.8rem;\">$down_content</span>";
	else
		$down_a = "<a class=\"control-box\" href=\"$down_href\">$down_content</a>";
	
	return <<<END_OF_HTML
	
			<table class="layout-frame fullwidth">
				<tr>
					<td class="layout-bg hollow algL" width="50%">$up_a</td>
					<td class="layout-bg hollow algR" width="50%">$down_a</td>
				</tr>
			</table>
	
	END_OF_HTML;
}


/**
 * An even simpler navlink system, for pagination where items-per-page is not user-settable.
 * 
 * @param  string $base_url          Basic URL to which parameters will be added. Should end in ? if no parameters, but not in &.
 * @param  int    $curr_page_no      Number of current page (where page numbers begin at 1).
 * @param  bool   $next_page_exists  False if we're on the last page; else true. 
 * @return string                    HTML of 3 x td elements, creating super-simple navigation.
 */
function print_braindead_navlinks(string $base_url, int $curr_page_no, bool $next_page_exists) : string
{
	/* work out page numbers */
	$marker = array( 'first' => '|&lt;', 'prev' => '&lt;&lt;', 'next' => "&gt;&gt;" );
	$nav_page_no = array(
		'first' => ($curr_page_no == 1 ? 0 : 1),
		'prev'  => $curr_page_no - 1,
		'next'  => ( (! $next_page_exists) ? 0 : $curr_page_no + 1)
		);
	/* all page numbers that should be dead links have zero in the above array */
	
	$div = ( false === strpos($base_url, '?') ? '?' : '&' );
	
	$string = '';
	foreach ($marker as $k => $label)
		$string .= '<td class="layout-bg hollow braindead-pager">'
				. ($nav_page_no[$k] 
						? "<a class=\"control-box\" href=\"{$base_url}{$div}pageNo={$nav_page_no[$k]}\"><strong>$label</strong></a></td>" 
						: "<span class=\"control-box-off\"><strong>$label</strong></span></td>"
						);
	$string .= "\n";
	return $string;
}


/**
 * Print out the system messages in HTML, including links to delete them. No return value.
 */
function display_system_messages() : void
{
	global $User;
	global $Config;
	
	/* weeeeeelll, this is unfortunately complex! */
	
	switch ($Config->run_location)
	{
	case RUN_LOCATION_ADM:
		$execute_path = 'index.php?admF=execute&function=delete_system_message';
		$after_path = urlencode("index.php?ui=systemMessages");
		$edit_path = 'index.php?ui=systemMessages'; /* cqpweb-new-ui: edit links; [x] deletes through site.js, because CQPweb's own delete address can answer 403 Forbidden behind a web application firewall */
		break;
		
	case RUN_LOCATION_USR:
		$execute_path = '../adm/index.php?admF=execute&function=delete_system_message';
		$after_path = urlencode("../usr/");
		$edit_path = '../adm/index.php?ui=systemMessages';
		break;
		
	case RUN_LOCATION_MAINHOME:
		$execute_path = 'adm/index.php?admF=execute&function=delete_system_message';
		$after_path = urlencode("../");
		$edit_path = 'adm/index.php?ui=systemMessages';
		break;
		
	case RUN_LOCATION_USERCORPUS:
	case RUN_LOCATION_CORPUS:
		/* we are in a corpus */
		$execute_path = 'execute.php?function=delete_system_message';
		$after_path = urlencode(basename($_SERVER['SCRIPT_FILENAME']));
		$edit_path = '../adm/index.php?ui=systemMessages';
		break;
	}
	
	$result = do_sql_query("select * from `system_messages` order by `date_of_posting` desc");
	if (0 == mysqli_num_rows($result))
		return;
	?>
	
	<table class="layout-frame fullwidth">
		<tr>
			<th class="layout-strong" colspan="<?= ($User->is_admin() ? '3' : '2') ?>">
				System messages
				<?= ($Config->rss_feed_available ? print_rss_icon_link() : ''), "\n" ?>
			</th>
		</tr>
		
		<?php
		$cp_first_msg = true; /* cqpweb-new-ui: a gap of about one line between messages */
		while ($o = mysqli_fetch_object($result))
		{
			if (!$cp_first_msg)
				echo "\n\t\t\t<tr class=\"cp-msg-gap\" aria-hidden=\"true\"><td colspan=\"", ($User->is_admin() ? '3' : '2'), "\"></td></tr>\n";
			$cp_first_msg = false;
			?>
			
			<tr>
				<td class="layout-bg nowrap" rowspan="2"><?= substr($o->date_of_posting, 0, 10) ?></td>
				<td class="layout-fg"><strong><?= escape_html(stripslashes($o->header)) ?></strong></td>
				
				<?php
				if ($User->is_admin())
					echo <<<END_HTML
					
							<td rowspan="2" class="layout-fg hollow nowrap">
								<a class="control-box" data-tooltip="Edit this system message" href="$edit_path#cpMsg$o->id">[edit]</a>
								<a id="sysMsgDel:$o->id" 
									class="control-box" 
									data-tooltip="Delete this system message"
									data-cp-sysmsg-del="$o->id"
									href="$edit_path#cpMsg$o->id"
									>[x]</a>
							</td>
					
					END_HTML;
				?>
			
			</tr>
			<tr>
				<td class="layout-fg">
					<?php
					/* Sanitise, then add br's, then restore whitelisted links ... */
					/* cqpweb-new-ui: links to pages of this site (starting with "/" or this site's address) are not marked as external */
					$cp_host = preg_quote(preg_replace('/^www\./i', '', strtolower((string)parse_url('//' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), PHP_URL_HOST))), '~');
					echo preg_replace('~<a class="link external" href="((?:/|https?://(?:www\.)?' . $cp_host . '(?=[/":]))[^"]*)"~', '<a class="link" href="$1"',
						unescape_escaped_a_href(str_replace("\n", '<br>', escape_html(stripslashes($o->content))))), "\n";
					?>
				</td>
			</tr>
			
			<?php
		}
		?>
	
	</table>
	
	<?php
}


/**
 * Print the "coming soon message", and end the HTML page.
 * NOTE: this does not shut down CQPweb. 
 */
function coming_soon_page() : void
{
	global $Config;
// example implementation of the new Ui obbj.
// 	$html = new UiPageFramer('unfinished function!', $Config->colour_scheme);
// 	$html->do_begin_page();
	
	echo print_html_header('unfinished function!', $Config->colour_scheme);
	?>
	
	<table class="layout-frame fullwidth">
		<tr>
			<th class="layout-strong">Unfinished function!</th>
		</tr>
		<tr>
			<td class="layout-contrast comfy"><strong>We are sorry, but that part of CQPweb has not been built yet.</strong></td>
		</tr>
	</table>
	
	<?php
	
	echo print_html_footer();
	
// 	$html->do_end_page();
}


function print_indexing_notes(int $corpus_id) : string
{
	$content = "\n" . escape_html(get_corpus_metadata($corpus_id, 'indexing_notes') ?? '')  . "\n";
	
	return <<<END_OF_HTML
	
			<pre>$content</pre>
	
	END_OF_HTML;
}


function do_bitui_access_statement(?string $statement) : void
{
	if (empty($statement))
		return;
	$statement = escape_html($statement);
	$statement = unescape_escaped_a_href($statement);
	$statement = preg_replace('/<a /', '<a class="link" ', $statement);
	$statement = str_replace("\r\n", "\n", $statement);
	
	/* is it multi para? */
	if (false !== strpos($statement, "\n\n"))
		$statement = str_replace("\n\n", "\n</p>\n<p>\n", $statement);
	else 
		$statement = str_replace("\n", "<br>\n", $statement);
	?>
	
	<table class="layout-frame fullwidth">
		<tr>
			<th class="layout-strong">Information on access to this corpus</th>
		</tr>
		<tr>
			<td class="layout-fg">
				<p class="spacer"></p>
				<p><?= $statement ?></p>
				<p class="spacer"></p>
			</td>
		</tr>
	</table>
	
	<?php
}


/**
 * 
 * @param string $target_path   The folder to view.
 * @param string $form_id       If specified, all inputs will use this value for their explicitly declared "form" attribute.
 *                              If this is an empty value, there will be no "form" attribute. 
 * @param string $message       String (one of "admincorpus", "usercorpus" which determines the set of messages to print.
 */
function do_fileselector_table(string $target_path, string $form_id = '', string $message = 'admincorpus') : void
{
	global $Config;
	
	if ('admincorpus' == $message)
	{
		$msgtxt = 'The following files are available (uncompressed) in the upload area. Put a tick next to the files you want to index into CWB format.';
		$emptymsg = 'The upload area is presently empty.';
	}
	else if ('usercorpus' == $message)
	{
		$msgtxt = 'Select files from your upload area to include in the corpus. If the files contain more data 
				   than you are allowed to install, the overflow will be left out of the corpus.';
		$emptymsg = 'You don\'t currently have any uploaded files!';
	}
	else
		$msgtxt = 'Choose one or more of your files below.';
	
	if (empty($form_id))
		$form_decl = '';
	else
		$form_decl = ' form="'.$form_id.'"';
	?>
	
	<table class="layout-frame fullwidth">
		<tr>
			<th class="layout-strong" colspan="4">Select files</th>
		</tr>
		<tr>
			<td class="layout-bg comfy" colspan="4"><?= $msgtxt ?></td>
		</tr>
		<tr>
			<th class="layout-strong">Include?</th>
			<th class="layout-strong">Filename</th>
			<th class="layout-strong">Size</th>
			<th class="layout-strong">Date modified</th>
		</tr>
		
		<?php 
		$file_list = scandir($target_path);
		if (empty($file_list) || 2 == count($file_list))
		{
			$file_list = [];
			echo "\n\t\t<tr>\n\t\t\t<td colspan=\"4\" class=\"layout-bg\"><p>$emptymsg</p></td>\n\t\t</tr>\n";
		}
		natcasesort($file_list);
		
		$arbitrary = 1;

		foreach ($file_list as $f)
		{
			$file = "$target_path/$f";
			
			/* skip hidden files & directories */
			if ('.' == $f[0] || !is_file($file))
				continue;
			/* but symlinks is allowed ! */
			
				// should be able to work on win now (2022-07)
// 			/* DO NOT skip compressed files: any modern cwb-encode version will support them
// 			 * .... unless we are running on Windows! in which case, cwb-encode can't use pipes. */
// 			if ($Config->cqpweb_running_on_windows)
// 				if (compressed_file_is_single_nontar($f))
// 					continue;
			
			/* skip PHP scripts (regardless) */
			if ('.php' == substr($f, -4))
				continue;
			
			$stat = stat($file);
			?>
			
			<tr>
				<td class="layout-fg algC">
					<?= '<input type="checkbox" id="iF_' , $arbitrary , '" class="includeFile" name="iF_' , $arbitrary , '" value="' , /*urlencode($f)*/ escape_html($f), '"', $form_decl, ">\n"; ?>
				</td>
				<td class="layout-fg algL">
					<label for="iF_<?= $arbitrary ?>"><?= escape_html($f) ?></label>
				</td>
				<td class="layout-fg algR">
					<?= render_bytes($stat['size'], 'auto', 1), "\n" ?>
				</td>
				<td class="layout-fg algC">
					<?= date(CQPWEB_UI_DATE_FORMAT, $stat['mtime']), "\n" ?>
				</td>
			</tr>
			
			<?php
			$arbitrary++;
		}
		?>
	
	</table>
	
	<?php
}


/**
 * Create generic upload form. The file itself has parameter name "uploadedFile". 
 * 
 * @param string $script_address  Filename of the script to use as the "action" in the form.
 * @param string $instruction     If set, a table row will be added above the form containing 
 *                                this string as an instruction. 
 * @param array $hidden_params
 */
function do_ui_upload_form_as_table(string $script_address, string $instruction = '', array $hidden_params = []) : void
{
	if (empty($instruction))
	{
		global $User;
		if ($User->is_admin())
			$instruction_html = "Files uploaded to CQPweb can be used as the input to indexing, or as database inputs.";
		else
			$instruction_html = false;
	}
	else
		$instruction_html = escape_html($instruction);
	?>
	
	<form enctype="multipart/form-data" action="<?= $script_address ?>" method="post">
		<?= print_hidden_inputs($hidden_params), "\n" ?>
		<table class="layout-frame fullwidth">
			
			<?php
			if ($instruction_html)
			{
				?>
				
				<tr>
					<td class="layout-bg comfy" colspan="2"><?= $instruction_html ?></td>
				</tr>
				
				<?php
			}
			?>
			
			<tr>
				<td class="layout-fg algR comfy" width="50%">
					<label for="uploadedFile">Choose a file to upload:</label>
				</td>
				<td class="layout-fg algL comfy" width="50%">
					<input id="uploadedFile" type="file" name="uploadedFile">
				</td>
			</tr>
			<tr>
				<td class="layout-fg algC comfy" colspan="2">
					<button type="submit">Upload file</button>
				</td>
			</tr>
		</table>
	</form>
	
	<?php
}


/**
 * 
 * @param string $username    If empty, the display target will be assumed to be the logged in user in normal view,
 *                            and the admin-upload-area in admin view. If supplied, the given user is the display target.
 * @param bool   $admin_view  If true, request admin view instead of normal view. 
 *                            (The former is viewable within "adm" rather than "usr".)
 */
function do_ui_display_upload_area(?string $username = null, bool $admin_view = false) : void
{
	global $Config;
	global $User;
	
	/* if not admin view - user's own view, so we can take the global User as target. */
	if (empty($username) && !$admin_view)
		$username = ( $User->logged_in ? $User->username : '' );
	
	/* only let people view their own upload area */
	if (!$User->is_admin())
		if ($admin_view || $User->username != $username)
			exiterror("You do not have permission to view that area.");
	
	if (empty($username))
	{
		/* main upload area */
		$in_admin_area = true;
		
		$area_path = $Config->dir->upload;
	}
	else 
	{
		/* user upload area */
		$in_admin_area = false;
		
		if (!($area_path = get_user_upload_area($username)))
			exiterror("Unable to work out path to upload area to display.");
	}
	
	$third_pers = $User->is_admin() && $User->username != $username;
	
	$file_list = scandir($area_path);
	$n_files   = count($file_list) - 2;
	natcasesort($file_list);
	
	$action_cols = $admin_view ? 5 : 3;
	$all_cols = 3 + $action_cols;
	
	?>
	
	<table class="layout-frame fullwidth">
		<tr>
			<th class="layout-strong" colspan="<?= $all_cols ?>">Files currently in the upload area</th>
		</tr>
		<?php
		echo "\n";
		if (0 == $n_files)
		{
			?>
			<tr>
				<td class="layout-fg algC comfy" colspan="<?= $all_cols ?>"><?= $third_pers ? "The" : "Your" ?> upload area is currently empty.</td>
			</tr>
			<?php
		}
		else
		{
			?>
			<tr>
				<th class="layout-strong">Filename</th>
				<th class="layout-strong">Size</th>
				<th class="layout-strong">Date modified</th>
				<th class="layout-strong" colspan="<?= $action_cols ?>">Actions</th>
			</tr>
			<?php
		}
		
		$total_files = 0;
		$total_bytes = 0;
		/* we keep a running total to avoid a second directory sweep from calling a separate function */
		
		foreach ($file_list as &$f)
		{
			$file = "$area_path/$f";
			if (!is_file($file)) 
				continue;
			
			$file_is_compressed      = (ARCHIVE_TYPE_UNKNOWN != compressed_file_get_type($f));
			$file_can_be_decompresed = compressed_file_is_single_nontar($f);
			
			$stat = stat($file);
			
			$total_files++;
			$total_bytes += $stat['size'];
			?>
			
			<tr>
				<td class="layout-fg algL"><?= escape_html($f) ?></td>
				<td class="layout-fg algR"><?= render_bytes($stat['size'], 'auto', 0) ?></td>
				<td class="layout-fg algC"><?= date(CQPWEB_UI_DATE_FORMAT, $stat['mtime']) ?></td>
				<td class="layout-fg hollow">
					<?php 
					if ($file_is_compressed)
						echo '<span class="control-box-off">[Can\'t view]</span>', "\n";
					else
						echo'<a class="control-box" href="', make_upload_action_url($f, 'view', !$in_admin_area, $username, $admin_view), '">[View]</a>', "\n";
					?>
				</td>
				<td class="layout-fg hollow">
					<a class="control-box" 
						href="<?= make_upload_action_url($f, 'download', !$in_admin_area, $username, $admin_view) ?>"
					>[Download]</a>
				</td>
				
				<?php
				if ($admin_view)
				{
					?>
					
					<td class="layout-fg hollow">
						<?php 
//						if (empty($username))
							if ( ! ($file_is_compressed && !$file_can_be_decompresed) )
								echo '<a class="control-box" href="'
									, make_upload_action_url($f, ($file_is_compressed ? 'de' : '').'compress', !$in_admin_area, $username, $admin_view)
									, '">['
									, ($file_is_compressed ? 'Dec' : 'C'), 'ompress]</a>' 
									;
						echo "\n";
						?>
					</td>
					<td class="layout-fg hollow">
						<?php 
// 						if (empty($username) && !$file_is_compressed)
						if (!$file_is_compressed)
							echo '<a class="control-box" href="', make_upload_action_url($f, 'fix_linebreaks', !$in_admin_area, $username, $admin_view), '">[Fix linebreaks]</a>';
						echo "\n";
						?>
					</td>
					
					<?php
				}
				?>
				
				<td class="layout-fg hollow">
					<a class="control-box" href="<?= make_upload_action_url($f, 'delete', !$in_admin_area, $username, $admin_view) ?>">[Delete]</a>
				</td>
			</tr>
			
			<?php
		}
		?>
		
		<tr>
			<td class="layout-bg algL" colspan="<?= $admin_view ? '8' : '6' ?>">
				<?php
				echo "<p>"
 					, ( empty($username) ? '' : ($third_pers ? 'This user&rsquo;s ' : 'Your ') . 'upload area currently contains ' )
					, number_format($total_files, 0) 
					, ' files (' 
 					, render_bytes($total_bytes, 1) 
					, ").</p>\n"
					;
				if (!empty($username))
				{
					if (0 == ($max = user_upload_space_limit($username)))
						$max = 0.0000000000000000000001;
					else
						$max = (float)$max;
					echo "\n\t\t\t\t<p>"
						, $third_pers ? 'This user is ' : 'You are '
						, ' currently using ', render_percent($total_bytes/$max, 1), ' of '
						, $third_pers ? 'their' : 'your' , ' maximum limit of '
						, render_bytes($max)
						, ".\n"
						;
				}
				?>
			</td>
		</tr>
	</table>
	
	<?php
}


/**
 * Creates a message box from $_GET "&message=" at the point where it's called.
 * 
 * Used in adminhome: could also be used elsewhere.
 */
function do_ui_message() : void
{
	?>
	
	<table class="layout-frame fullwidth">
		<tr>
			<th class="layout-strong" colspan="2">CQPweb says:</th>
		</tr>
		<tr>
			<td class="layout-fg comfy"><?= escape_html( $_GET['message'] ?? '' ) ?></td>
		</tr>
	</table>
	
	<?php
}


/**
 * Creates and renders a table listing corpus details. Parameters allow details to be set for use
 * in either the administrator's or user's interface.
 * 
 * @param string $which_ui    One of the following : 'admin_usercorpora', 'admin_systemcorpora, 'usercorpora'.
 * @param string $username    If which = user_corpora: do we want the corpora of a specific user?
 */
function do_ui_showcorpora(string $which_ui, string $username = '') : void
{
	global $Config;
	global $User;
	
	if (!$User->is_admin())
		$which_ui = 'usercorpora';
	
	$hdr_txt_map = array(
			'admin_usercorpora'    => 'Showing user-corpora installed by ' . (empty($username)?'all users':"user $username"),
			'admin_systemcorpora'  => 'Showing installed corpora',
			'usercorpora'          => 'Your installed corpora',
	);
	$hdr_txt = $hdr_txt_map[$which_ui];
	
	$show_disk_space_divided = ('usercorpora' != $which_ui);
	$owner_col               = ('admin_usercorpora' == $which_ui && empty($username));
	
	$ncols = 7;
	if ($show_disk_space_divided)
		$ncols++;
	if ($owner_col)
		$ncols++;
	?>
	
	<table class="layout-frame fullwidth">
		<tr>
			<th class="layout-strong" colspan="<?= $ncols ?>"><?= $hdr_txt ?></th>
		</tr>
		<tr>
			<th class="layout-strong" rowspan="2">Corpus</th>
			
			<?php
			if ($owner_col)
			{
				?>
				
				<th class="layout-strong" rowspan="2">Owner</th>
				
				<?php
			}
			?>
			
			<th class="layout-strong" rowspan="2">Indexing date</th>
			<th class="layout-strong" colspan="3">Size</th>
			<th class="layout-strong"<?= $show_disk_space_divided ? ' colspan="2"' : ' rowspan="2"' ?>>Disk space</th>
			<th class="layout-strong" rowspan="2">Actions</th>
		</tr>
		<tr>
			<th class="layout-strong">Tokens</th>
			<th class="layout-strong">Types</th>
			<th class="layout-strong">Texts</th>
			
			<?php
			if ($show_disk_space_divided)
			{
				?>
				
				<th class="layout-strong">Indexes</th>
				<th class="layout-strong">Freq tables</th>
				
				<?php
			}
			?>
		
		</tr>
	
	<?php
	$total_index_size = 0;
	$total_freqtable_size = 0;
	
	if ('admin_systemcorpora' == $which_ui)
		$array_of_corpus_objs = get_all_corpora_info();
	else
		$array_of_corpus_objs = get_all_user_corpora_info($username, !$User->is_admin());
		/* admin user can see unfinished (invisible) corpora; owner can't */
	
	if (empty($array_of_corpus_objs))
		echo "\n\t<tr>\n\t\t"
			, '<td class="layout-bg" colspan="', $ncols, '"><p>'
			, ('usercorpora' != $which_ui ? 'No corpora to show.' : 'You do not have any installed corpora.')
			, '</p></td>'
			, "\n\t</tr>\n"
			;
	
	foreach ($array_of_corpus_objs as $curr_corpus => $r)
	{
		$owned  = !empty($r->owner);
		$ui_url = ($owned ? get_user_corpus_web_path($r->id, $r->owner)  : "../$curr_corpus");
		$label  = escape_html($r->title) . ($User->is_admin() ? (' (' . $curr_corpus . ')') : '');
		$tip    = escape_html($r->title . ($User->is_admin() ? ('<br>Handle: <b>' . $curr_corpus . '</b><br>ID: <b>'. $r->id . '</b><br>SQL ID: <b>' . int_to_base_36($r->id) . '</b>') : ''));
		?>
		
		<tr>
			<td class="layout-fg hollow algL" id="corpusCell_<?= $curr_corpus ?>">
				<strong>
					<a id="goC:<?= $curr_corpus ?>" 
						class="control-box" 
						data-tooltip="<?= $tip ?>" 
						href="<?= $ui_url ?>"
					><?= $label ?></a>
				</strong>
			</td>
			<?= $owner_col ? ('<td class="layout-fg algC">' . $r->owner .'</td>') : '', "\n" ?>
			<td class="layout-fg algC"><?= render_sql_timestamp($r->date_of_indexing) ?></td>
			<td class="layout-fg algR"><?= number_format($r->size_tokens) ?></td>
			<td class="layout-fg algR"><?= number_format($r->size_types) ?></td>
			<td class="layout-fg algR"><?= number_format($r->size_texts) ?></td>
			
			<?php
			$total_index_size     += $r->size_bytes_index;
			$total_freqtable_size += $r->size_bytes_freq;
			$joinsize = (float)($r->size_bytes_index + $r->size_bytes_freq);
			
			if ($show_disk_space_divided)
			{
				?>
				
				<td class="layout-fg algR"><?= render_bytes($r->size_bytes_index, 'auto', 1) ?></td>
				<td class="layout-fg algR"><?= render_bytes($r->size_bytes_freq,  'auto', 1) ?></td>
				
				<?php
			}
			else
			{
				?><td class="layout-fg algR"><?= render_bytes($joinsize) ?></td><?php
			}
			
			/* in admin UIs, we need to specify in the call to admin-execute that this is a user corpus */
			$delete_for_user = ('usercorpora' != $which_ui ? ($owned ? '&userCorp=1' : '') : '');
			?>
			
			<td class="layout-fg hollow">
				<a class="control-box corpusToggle" 
					data-corpus="<?= $curr_corpus ?>" 
					href="index.php?ui=deleteCorpus&corpus=<?= $curr_corpus , $delete_for_user ?>"
				>[Delete corpus]</a>
			</td>
		</tr>
		
		<?php
	}
	?>
	
	</table>
	
	<?php
	if ('usercorpora' != $which_ui)
	{
		/* this implies that we want 2 x size columns */
		?>
		
		<table class="layout-frame fullwidth">
			<tr>
				<th class="layout-strong" colspan="2">Disk use totals</th>
			</tr>
			<tr>
				<td class="layout-bg">Total index data disk use</td>
				<td class="layout-fg algR"><?= render_bytes($total_index_size, 'auto', 1) ?></td>
			</tr>
			<tr>
				<td class="layout-bg">Total frequency table disk use</td>
				<td class="layout-fg algR"><?= render_bytes($total_freqtable_size, 'auto', 1) ?></td>
			</tr>
			<tr>
				<td class="layout-bg">Grand total </td>
				<td class="layout-fg algR"><?= render_bytes($total_index_size + $total_freqtable_size, 'auto', 1) ?></td>
			</tr>
			<tr>
				<td class="layout-bg" colspan="2">
					<p>
						Important note: the disk usage estimates for corpus indexes <strong>do not include</strong>
						the data for corpora which were inserted into CQPweb from pre-indexed CWB corpora,
						since these indexes are not under CQPweb's control, 
						even if they are physically stored in CQPweb's designated index-data folder
						(though the frequency-table indexes for all such &ldquo;external&rdquo; corpora 
						<em>are</em> counted).
					</p>
					<p>
						(For reference: the total disk space taken up by the index-data directory is 
						<strong><?= render_bytes(recursive_sizeof_directory($Config->dir->index), 'auto', 1) ?></strong>.)
					<p>
						User data such as queries, databases, and subcorpora are not counted towards the disk use total.
						Moreover, there is a certain amount of system overhead for the SQL database.
					</p>
				</td>
			</tr>
		</table>
		
		<?php
	}
	else
	{
		$allowance = get_user_corpus_disk_allocation($User->username);
		$overflow = ($total_index_size+$total_freqtable_size) - $allowance;
		?>
		
		<table class="layout-frame fullwidth">
			<tr>
				<th class="layout-strong" colspan="2">Your corpus data storage allowance</th>
			</tr>
			<tr>
				<td class="layout-bg">Amount of disk space your installed corpora currently take up:</td>
				<td class="layout-fg algR"><?= render_bytes($total_index_size+$total_freqtable_size, 'MB', 0) ?></td>
			</tr>
			<tr>
				<td class="layout-bg">Your disk space allocation for installed corpora:</td>
				<td class="layout-fg algR"><?= render_bytes($allowance, 'MB', 0) ?></td>
			</tr>
			
			<?php
			if (0 < $overflow)
			{
				?>
				
				<tr>
					<td class="layout-contrast algL" colspan="2">
						<p>
							The corpora you have installed now exceed your disk space allocation 
							by <strong><?= render_bytes($overflow, 'auto', 1) ?></strong>.
						</p>
						<p>
							You will not be able to access any of your installed corpora until you have 
							freed some disk space (by deleting one or more corpora).
						</p>
					</td>
				</tr>
				
				<?php
			}
			?>
		
		</table>
		
		<?php
	}
}




/*
 * ========================
 * THE HTML CACHE MECHANISM
 * ========================
 */


/** 
 * Creates an md5 which identifies the cached HTML as having certain parameters. 
 * 
 * @param  int     $type         Appropriate symbolic constant.
 * @param  array   $params       Array, possible empty, of defining parameters. 
 * @return string
 */
function hash_htmlcache_params(int $type, array $params) : string
{
	return md5($type . ':' . implode(':', $params));
}

/**
 * Looks up a bit of cached HTML in the system. Throws it way if it's over an hour old.
 * 
 * @param  int     $type         Appropriate symbolic constant.
 * @param  array   $params       Array, possible empty, of defining parameters. 
 * @return string                Chunk of HTML, or null if not found.
 */
function check_htmlcache(int $type, array $params) : ?string
{
	/* this func should not ever return anything too old. */
	clear_htmlcache_outdated();
	
	$hash = hash_htmlcache_params($type, $params);
	
	$r = do_sql_query("select `data` from system_htmlcache where type = $type and param_hash = '$hash'");
	
	if (0 == $r->num_rows)
		return null;
	else
		return $r->fetch_row()[0];
}

/**
 * This function throws away cached html that is more than 600 mins old. 
 */
function clear_htmlcache_outdated() : void
{
	do_sql_query("delete from `system_htmlcache` where cache_time < ". (time() - 36000) );	
}

function clear_htmlcache_specified(int $type, array $params) : void
{
	$hash = hash_htmlcache_params($type, $params);
	do_sql_query("delete from `system_htmlcache` where type = $type and param_hash = '$hash'");
}

/**
 * Add, or update, a chunk of data in the HTML cache. 
 * 
 * @param  int     $type         Appropriate symbolic constant.
 * @param  array   $params       Array, possible empty, of defining parameters. 
 * @param  string  $data         The HTML to be cached.
 */
function push_to_htmlcache(int $type, array $params, string $data) : void
{
	$sql_data = escape_sql($data);

	$hash = hash_htmlcache_params($type, $params);
	
	$o = get_sql_object("select * from system_htmlcache where type = $type and param_hash = '$hash'");
	
	if (!$o)
		do_sql_query("insert into system_htmlcache (type,param_hash,cache_time,data)values($type,'$hash',".time().",'$sql_data')");
	else
		do_sql_query("update system_htmlcache set data = '$sql_data' where id = {$o->id}");
}













/*
 * ==========================
 * JAVASCRIPT AND CSS-RELATED
 * ==========================
 */




/**
 * Uses the latedata mechanism to insert $n password candidates to the browser instance's environment. 
 */
function print_javascript_for_password_insert(callable $password_function = null, int $n = 49) : string
{
	global $Config;
	if (empty($password_function))
		$password_function = $Config->create_password_function;

	return print_latedata_complex('password-suggestions', $password_function($n));
	// new experimental approach above.
	/*
	$raw_array = array();
	foreach ($password_function($n) as $pwd)
		$raw_array[] = "'$pwd'";
	
	$array_initialisers = implode(',', $raw_array);
	
	return <<<END_OF_JAVASCRIPT
	
		<script>
			$( function() {  window.cqpwebInsertPasswordData = new Array( $array_initialisers );  } );
		</script>
	
	END_OF_JAVASCRIPT;
	*/
}


/**
 * password_insert_internal is the default function for CQPweb candidate passwords.
 * 
 * 
 * Whatever function you use must be in a source file included() in adminhome.inc.php
 * (i.e. you need to hack the code a little bit).
 * 
 * All password-creation functions must return an array of n candidate passwords.
 * 
 */
function password_insert_internal(int $n) : array 
{
	$pwd = array();
	for ( $i = 0 ; $i < $n ; $i++ )
		$pwd[$i] = sprintf("%c%c%c%c%d%d%c%c%c%c=%c%c%c%c%d%d%c%c%c%c",
						random_int(0x61, 0x7a), random_int(0x61, 0x7a), random_int(0x61, 0x7a), random_int(0x61, 0x7a),
						random_int(0,9), random_int(0,9),
						random_int(0x61, 0x7a), random_int(0x61, 0x7a), random_int(0x61, 0x7a), random_int(0x61, 0x7a),
						random_int(0x61, 0x7a), random_int(0x61, 0x7a), random_int(0x61, 0x7a), random_int(0x61, 0x7a),
						random_int(0,9), random_int(0,9),
						random_int(0x61, 0x7a), random_int(0x61, 0x7a), random_int(0x61, 0x7a), random_int(0x61, 0x7a)
						); 
	return $pwd;
}


/*
 * ================================================
 * functions dealing with different colour schemes.
 * ================================================
 */


/**
 * Return a string indicator of the type of colour scheme coded in the argument.
 * 
 * @param  string $colour_scheme  A colour scheme to check.
 * @return string                 One of: handle, json, cssfile, or null for an invalid specifier.
 *                                This is based on the incipit.
 */
function type_of_colour_scheme(string $colour_scheme) : ?string
{
	switch ($colour_scheme[0])
	{
	case '~':
		/* handle for a builtin colour scheme */
		return 'handle';
		
	case '{':
		/* JSON object (map of classes to colour value strings) */
		return 'json';
		
	default:
		if (preg_match('~^(https?://|../css/\S+.css$)~', $colour_scheme))
			return 'cssfile';
		return null;
	}
}


/**
 * Translates a colour scheme into a "root variables" CSS block.
 * 
 * Colour schemes can either be builtin, in which case they are a handle preceded
 * by a ~ -- ~blue, ~red, ~dusk, and so on. 
 * 
 * If not built in, then a colour scheme is a JSON encoded associative array,
 * with the same keys ('layout-fg' etc. as the builtin ones.
 * 
 * As an intermediate step, colour schemes can also be a CSS URL
 */
function print_css_root_variables(string $colour_scheme) : string
{
	switch (type_of_colour_scheme($colour_scheme))
	{
	case 'cssfile':
		/* if load fails, return empty string */
		return (string)file_get_contents($colour_scheme);
		
	case 'handle':
		$data = list_css_colour_schemes()[substr($colour_scheme, 1)];
		break;
		
	case 'json':
		$data = json_decode($colour_scheme, true);
		break;
		
	default: 
		/* invalid colour scheme - so use blue, and squawk a warning. */
		$data = list_css_colour_schemes()["blue"];
		squawk("Invalid colour scheme specifier '$colour_scheme'!");
	}
	
	/* everything except colour variables is now in 0system. */
	$css = "\n\t\t:root {\n";
	
	/* colours which a scheme MAY specify, but doesn't have to */
	$data_default = [
			'layout-bg'     => '#d5d5d5',
			'layout-data2'  => '#f0f0f0', /* a lighter grey, used for 2ndary data in the concordance. */
			'layout-outline'=> 'white',
			'tooltip-frame' => '#003399',
			'tooltip-bg'    => '#e6ecff',
			'tooltip-text'  => '#000066',
	];
	
	foreach($data_default as $key => $col)
		if (!isset($data[$key]))
			$data[$key] = $col;
	
	foreach ($data as $sfx => $colour)
		$css .= "\t\t\t--colour-$sfx\t:\t$colour;\n";
	$css .= "\t\t}\n\n";
	
	return $css;
}


/** 
 * Returns an array with the builtin colour schemes.
 * 
 * The array is a map: keys = scheme names, values = inner array of data.
 * 
 * Each inner array maps the variable suffixes, e.g. layout-fg, to their colour names or hex codes.
 */
function list_css_colour_schemes() : array
{
	return array(
		'blue' => [
			'layout-fg'       => '#ddddff',
			'layout-strong'   => '#bbbbff',
			'layout-contrast' => '#ffeeaa',
			
			'text-normal'     => 'black',
			'text-strong'     => 'black',
			'text-bright'     => 'red',
			'text-faint'      => 'gray',
		],
		
		'yellow' => [
			'layout-fg'       => '#ffeeaa',
			'layout-strong'   => '#ffbb77',
			'layout-contrast' => '#ddddff',
			
			'text-normal'     => 'black',
			'text-strong'     => 'black',
			'text-bright'     => 'blue',
			'text-faint'      => 'powderblue',
		],
		
		'green' => [
			'layout-fg'       => '#ccffcc',
			'layout-strong'   => '#66cc99',
			'layout-contrast' => '#ffeeaa',
			
			'text-normal'     => 'black',
			'text-strong'     => 'black',
			'text-bright'     => 'red',
			'text-faint'      => 'gray',
		],
		
		'red' => [
			'layout-fg'       => '#ffcfdd',
			'layout-strong'   => '#ff8899',
			'layout-contrast' => '#ddddff',
			
			'text-normal'     => 'black',
			'text-strong'     => 'black',
			'text-bright'     => 'blue',
			'text-faint'      => 'gray',
		],
		
		'brown' => [
			'layout-fg'       => '#eeaa77',
			'layout-strong'   => '#cd663f',
			'layout-contrast' => '#ffeeaa',
			
			'text-normal'     => 'darkbrown',
			'text-strong'     => 'yellow',
			'text-bright'     => 'darkcyan',
			'text-faint'      => 'gray',
		],
		
		'purple' => [
			'layout-fg'       => '#dfbaf5',
			'layout-strong'   => '#be71ec',
			'layout-contrast' => '#ffeeaa',
			
			'text-normal'     => 'black',
			'text-strong'     => 'black',
			'text-bright'     => 'yellow',
			'text-faint'      => 'gray',
		],
		
		'lime' => [
			'layout-fg'       => '#ecff6f',
			'layout-strong'   => '#b9ff6f',
			'layout-contrast' => '#00ffff',
			
			'text-normal'     => 'black',
			'text-strong'     => 'black',
			'text-bright'     => 'purple',
			'text-faint'      => 'gray',
		],
		
		'aqua' => [
			'layout-fg'       => '#b0ffff',
			'layout-strong'   => '#00ffff',
			'layout-contrast' => '#ffeeaa',
			
			'text-normal'     => 'black',
			'text-strong'     => 'black',
			'text-bright'     => 'red',
			'text-faint'      => 'gray',
		],
		
		'dusk' => [
			'layout-fg'       => '#d1a4ff',
			'layout-strong'   => '#8000ff',
			'layout-contrast' => '#ffeeaa',
			
			'text-normal'     => 'black',
			'text-strong'     => 'white',
			'text-bright'     => 'yellow',
			'text-faint'      => 'gray',
		],
		
		'gold' => [
			'layout-fg'       => '#c1c66c',
			'layout-strong'   => '#808000',
			'layout-contrast' => '#80ffff',
			
			'text-normal'     => 'black',
			'text-strong'     => 'black',
			'text-bright'     => 'hotpink',
			'text-faint'      => 'gray',
		],
		
		'navy' => [
			'layout-fg'       => '#33aadd',
//			'layout-bg'       => 'darkslategray',
			'layout-strong'   => '#0066aa',
			'layout-contrast' => '#ffeeaa',
			
			'text-normal'     => 'darkslategray',
			'text-strong'     => 'white',
			'text-bright'     => 'sienna',
			'text-faint'      => 'gray',
		],
		
		'rose' => [
			'layout-fg'       => '#f8bbd0',
			'layout-strong'   => '#f48fb1',
			'layout-contrast' => '#ccffff',
			
			'text-normal'     => 'darkslategray',
			'text-strong'     => 'darkslategray',
			'text-bright'     => 'navy',
			'text-faint'      => 'gray',
		],
		
		'teal' => [
			'layout-fg'       => '#d0ffff',
			'layout-strong'   => '#009090',
			'layout-contrast' => '#e67451',
			
			'text-normal'     => 'black',
			'text-strong'     => 'black',
			'text-bright'     => 'firebrick',
			'text-faint'      => 'gray',
		],
		
		'charcoal' => [
			'layout-fg'       => '#efefef',
			'layout-strong'   => 'black',
			'layout-contrast' => 'lightgoldenrodyellow',
			
			'text-normal'     => 'black',
			'text-strong'     => 'white',
			'text-bright'     => 'crimson',
			'text-faint'      => 'darkslateblue',
		],
		
		'snowflake' => [
			'layout-fg'       => '#d6efff',
			'layout-bg'       => '#c6d8d3',
			'layout-strong'   => '#709199',
			'layout-contrast' => '#efffd6',
			'layout-data2'    => '#dcf7f7',
			
			'text-normal'     => 'black',
			'text-strong'     => 'black',
			/* in v3.2 "bright" was #13266a; changed to make it show up against bg */
			'text-bright'     => '#f3266a',
			'text-faint'      => 'gray',
				
			'tooltip-frame'   => '#709199',
			'tooltip-bg'      => '#dcf7f7',
			'tooltip-text'    => '#13266a', 
		],
		
		'forest' => [
			'layout-fg'       => '#dad7cd',
			'layout-bg'       => '#588157', /* bg and strong switched from v3.2 */
			'layout-strong'   => '#3a5a40',
			'layout-contrast' => '#a3b18a',
			'layout-data2'    => '#a3b18a',
			
			'text-normal'     => 'black',
			'text-strong'     => '#b7a06e',
			'text-bright'     => '#baa900', 
			'text-faint'      => '#588157',
			
			'tooltip-frame'   => '#344E41',
			'tooltip-bg'      => '#DAD7CD',
			'tooltip-text'    => '#3A5A40',
		],
		
		'motley' => [
			'layout-fg'       => 'palegreen',
			'layout-strong'   => 'crimson',
			'layout-contrast' => 'deepskyblue',
			
			'text-normal'     => 'black',
			'text-strong'     => 'mediumspringgreen',
			'text-bright'     => 'blue',
				// TODO. I really wanted this to be crimson when OFF (against grey/palegreen) and lime or blue or similar when ON (against crimson).
				// how to accomplish ???
			'text-faint'      => 'gray',
		],
		
		'cinema' => [
			'layout-fg'       => 'lightsalmon',
			'layout-strong'   => '#0080a0',
			'layout-contrast' => 'lightcyan',
			
			'text-normal'     => 'black',
			'text-strong'     => '#ffad4d',
			'text-bright'     => 'darkmagenta',
			'text-faint'      => 'gray',
		],
		
		'skin' => [
			'layout-fg'       => 'tan',
			'layout-strong'   => 'sienna',
			'layout-contrast' => 'lime',
			
			'text-normal'     => 'black',
			'text-strong'     => 'white',
			'text-bright'     => 'blue',
			'text-faint'      => 'gray',
		],
		
		'floral' => [
			'layout-fg'       => 'lightyellow',
			'layout-strong'   => 'orchid',
			'layout-contrast' => 'paleturquoise',
			
			'text-normal'     => 'black',
			'text-strong'     => 'lemonchiffon',
			'text-bright'     => 'blue',
			'text-faint'      => 'gray',
		],
		
		'cappuccino' => [
			'layout-fg'       => '#ede0d4',
			'layout-bg'       => '#cb997e',
			'layout-strong'   => '#ddbea9',
			'layout-contrast' => '#dda15e',
			'layout-data2'    => '#e6ccb2',
			
			'text-normal'     => 'black',
			'text-strong'     => 'black',
			'text-bright'     => '#582f0e',
			'text-faint'      => '#9c6644',
			
			'tooltip-frame'   => '#7f5539',
			'tooltip-bg'      => '#f7ede2',
			'tooltip-text'    => '#582f0e',
		],
		
		'grapefruit' => [
			'layout-fg'       => '#ffe5d9',
			'layout-bg'       => '#fec5bb',
			'layout-strong'   => '#ffd7ba',
			'layout-contrast' => '#fcd5ce',
			'layout-data2'    => '#fae1dd',
			'layout-outline'  => '#ffd7ba',
			'text-normal'     => 'black',
			'text-strong'     => 'saddlebrown',
			'text-bright'     => 'cornflowerblue',/* used to be #582f0e, changed to make more contrastive */
			'text-faint'      => '#ffadad',
			
			'tooltip-frame'   => '#fec89a',
			'tooltip-bg'      => '#f8edeb',
			'tooltip-text'    => '#e29578',
		],
		
		'neon' => [
			'layout-fg'       => '#ffa6ff',
			'layout-strong'   => '#ff00ff',
			'layout-contrast' => '#00ff00',
			
			'text-normal'     => 'black',
			'text-strong'     => 'black',
			'text-bright'     => 'maroon',
			'text-faint'      => 'gray',
		],
		
		'periwinkle' => [ 
			'layout-fg'       => '#ccccff',
			'layout-bg'       => '#dcdcff',
			'layout-strong'   => '#adbaff',
			'layout-contrast' => 'lightgoldenrodyellow',
			'layout-data2'    => '#ebeefe',
			
			'text-normal'     => 'black',
			'text-strong'     => 'black',
			'text-bright'     => 'lightcoral',
			'text-faint'      => 'slategrey',
			
			'tooltip-frame'   => '#adbaff',
			'tooltip-bg'      => '#ebeefe',
			'tooltip-text'    => '#000066',
		],
		
		'saffron' => [
			'layout-fg'       => '#ffd257',
			'layout-bg'       => '#ff9320',
			'layout-strong'   => '#ffb638',
			'layout-contrast' => 'lightcyan',
			'layout-data2'    => '#ffd399',
			
			'text-normal'     => 'black',
			'text-strong'     => 'black',
			'text-bright'     => 'purple',
			'text-faint'      => 'gray',
			
			'tooltip-frame'   => '#ff9320',
			'tooltip-bg'      => '#fff3d6',
			'tooltip-text'    => '#cc6900',
		],
		
// 		'' => [
// 			'layout-fg'       => '',
// 			'layout-strong'   => '',
// 			'layout-contrast' => '',
			
// 			'text-normal'     => '',
// 			'text-strong'     => '',
// 			'text-bright'     => '',
// 			'text-faint'      => 'gray',
// 		],
	);
}

