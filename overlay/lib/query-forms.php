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




/**
 * @file
 * 
 * This file contains the user interfaces for each of the primary corpus query entry points.  
 * Most of these functions (as in all "indexforms" files) print a table for the right-hand side interface.
 * Some are support functions, providing reusable chunks of HTML.
 */


/**
 * Builds and returns an HTML string containing the search-box and associated UI elements 
 * used in the Standard and Restricted Query forms.
 * 
 * @param  string $form_id                 ID of the form these inputs will be inserted into. Or an empty string or null.
 * @param  string $qstring                 A search pattern that will be inserted into the query textbox. Or an empty string or null.
 * @param  string $qmode                   The query-mode to pre-set in the query control. Or an empty string or null.
 * @param  int    $qsubcorpus              Integer ID of pre-selected subcorpus. Or null. 
 *                                         Only has any effect if $show_mini_restrictions is true. 
 * @param  bool   $show_mini_restrictions  Set to true if you want the "simple restriction" control for Standard Query. Defaults false.
 * @return string                          The assembled HTML. Unwrapped sequence of textarea, table, div.
 */
function print_search_box(?string $form_id = null, ?string $qstring = null, ?string $qmode = null, ?int $qsubcorpus = null, bool $show_mini_restrictions = false) : string
{
	global $Corpus;
	global $User;
	$m = null;
	 
	$render_cache = get_user_render_cache($User->id, $Corpus->id);
	
	/* GET VARIABLES READY: form attribute, if needed */
	$form_att = empty($form_id) ? '' : " form=\"$form_id\"";
	
	/* GET VARIABLES READY: contents of query box */
	$qstring = ( !empty($qstring) ? escape_html(prepare_query_string($qstring)) : '' );
	
	/* GET VARIABLES READY: strategy switcher */
	if (preg_match('/^\(\?\s*(\w+)\s*\)\s*/', $qstring, $m))
	{
		if (in_array($m[1], array('traditional', 'shortest', 'longest')))
			$strategy_insert = $m[1];
		else if ('standard' == $m[1])
			$strategy_insert = '0';
		$qstring = preg_replace('/^'.preg_quote($m[0], '/').'/', '', $qstring);
	}
	else
		$strategy_insert = '0';
	/* note, the above assumes the strategy is always fixed to the start of the ceql string;
	 * but, in future, this may not be the case (if qstrategy is store in history separately from 
	 * the text of the strategy. */

	/* GET VARIABLES READY: the query mode. */
	if (!array_key_exists($qmode, DESCRIBE_QUERY_MODE))
	{
		/* cqpweb-new-ui: CQP syntax also when the site's default query type is CQP syntax (Personalization page) */
		require_once('../lib/cqpweb-new-ui/brand-lib.php');
		if ($User->cqp_syntax || 'cqp' === cp_brand()['queryMode'])
			$qmode = 'cqp';
		else
			$qmode = ($Corpus->case_sensitive ? 'sq_case' : 'sq_nocase');
	}
	
	$mode_options = '';
	foreach (DESCRIBE_QUERY_MODE as $mode => $modedesc)
		$mode_options .= "\n\t\t\t\t\t\t\t<option value=\"$mode\"" . ($qmode == $mode ? ' selected' : '') . ">$modedesc</option>";
	
	/* GET VARIABLES READY: hidden attribute help */
	$style_display = ('cqp' != $qmode ? "display: none" : '');
	
	$p_atts = "\n";
	foreach(get_all_annotation_info($Corpus->id) as $p)
	{
		$p->description = escape_html($p->description);
		$tagset = escape_html((empty($p->tagset) ? '' : "(using {$p->tagset})"));
		$p_atts .= "\t\t\t\t\t<tr><td><code>{$p->handle}</code></td><td>{$p->description}$tagset</td></tr>\n";
	}
	
	$s_atts = "\n";
	foreach(list_xml_all($Corpus->handle) as $s => $s_desc)
		$s_atts .= "\t\t\t\t\t<tr><td><code>&lt;{$s}&gt;</code></td><td>" . escape_html($s_desc) . "</td></tr>\n";
	if ($s_atts == "\n")
		$s_atts = "\n<tr>\t<td colspan='2'><code>None.</code></td>\t</tr>\n";
	
	/* and, while we do the a-atts, simultaneously,  GET VARIABLES READY: aligned corpus display */
	$a_atts = "\n";
	$align_options = '';
	foreach(check_alignment_permissions(list_corpus_alignments($Corpus->id)) as $a=>$a_desc)
	{
		$a_atts .= "\t\t\t\t\t<tr>\t<td><code>&lt;{$a}&gt;</code></td>\t<td>" . escape_html($a_desc) . "</td>\t</tr>\n";
		$align_options .= "\n\t\t\t\t\t\t\t<option value=\"$a\">Show text from parallel corpus &ldquo;" . escape_html($a_desc) . "&rdquo;</option>";
	}
	if ($a_atts == "\n")
		$a_atts = "\n\t\t\t\t\t<tr><td colspan=\"2\"><code>None.</code></td></tr>\n";
	/* we do this for a-atts but not p/s-atts because there is always at least word and at least text/text_id */
	
	/* GET VARIABLES READY: hits per page select */
	$pp_options = '';
	$poss_per_page_values = array (10,50, 100, 250, 500, 1000);
	if (!in_array($render_cache->conc_per_page, $poss_per_page_values))
	{
		$poss_per_page_values[] = $render_cache->conc_per_page;
		sort($poss_per_page_values, SORT_NUMERIC);
	}
	foreach ($poss_per_page_values as $val)
		$pp_options .= "\n\t\t\t\t\t\t\t<option value=\"$val\""
			. ($render_cache->conc_per_page == $val ? ' selected' : '')
			. ">$val</option>"
			;
	if ($User->is_admin())
		$pp_options .=  "\n\t\t\t\t\t\t\t<option value=\"all\">show all</option>";
	
	/* ASSEMBLE ALIGNMENT DISPLAY CONTROL */
	$parallel_row = '';
	if (!empty($align_options))
		$parallel_row = <<<END_PARALLEL_ROW
		
						<tr>
							<td class="basicbox">Display alignment:</td>
							<td class="basicbox">
								<select$form_att name="showAlign">
									<option value="~~none" selected>Do not show aligned text in parallel corpus</option>
									$align_options
								</select>
							</td>
						</tr>
		
		END_PARALLEL_ROW;
	
	/* ASSEMBLE MATCH STRATEGY SWITCHER */
	$qstrat_disabled = 'cqp' == $qmode ? ' disabled' : '';
	/* Note that because "standard" is normal, we do not actually want to use a flag for it 
	   (or all queries would end up with a modifier!) - thus the "0". */
	$select_standard    = ($strategy_insert == '0'           ? ' selected' : '');
	$select_longest     = ($strategy_insert == 'longest'     ? ' selected' : '');
	$select_shortest    = ($strategy_insert == 'shortest'    ? ' selected' : '');
	$select_traditional = ($strategy_insert == 'traditional' ? ' selected' : '');
	
	$strategy_row = <<<END_STRATEGY_ROW
	
					<tr>
						<td class="basicbox">Match strategy:</td>
						<td class="basicbox">
							<select$form_att id="qstrategy" name="qstrategy"$qstrat_disabled>
								<option value="0"$select_standard>Standard</option>
								<option value="longest"$select_longest>Longest-possible match</option>
								<option value="shortest"$select_shortest>Shortest-possible match</option>
								<option value="traditional"$select_traditional>Old-style CQP matching</option>
							</select>
						</td>
					</tr>
	
	END_STRATEGY_ROW;
	
	/* ASSEMBLE THE RESTRICTIONS MINI-CONTROL TOOL */
	$restriction_row = '';
	if ($show_mini_restrictions)
	{
		$restriction_dropdown = print_mini_restriction_search_tool($form_id, $qsubcorpus);
		$restriction_row = <<<END_RESTRICT_ROW
		
						<tr>
							<td class="basicbox">Restriction:</td>
							<td class="basicbox">
								$restriction_dropdown
							</td>
						</tr>
		
		END_RESTRICT_ROW;
	}
	
	/* GET AN ABSOLUTE URL FOR THE CEQL SYNTAX PDF */
	$ceql_url = url_absolutify('cqpweb-simple-syntax-help.pdf', 'doc'); /*../doc/cqpweb-simple-syntax-help.pdf*/

	/* cqpweb-new-ui: the site's own CQP syntax tutorials, shown when "CQP syntax" is selected */
	require_once('../lib/cqpweb-new-ui/embed-lib.php');
	$cp_tutorial_callout = cp_tutorial_callout();

	/* ALL DONE: so assemble the HTML from the above variables && return it. */
	return <<<END_OF_HTML
	
				&nbsp;<br>
				
				<textarea
					$form_att
					id="searchBox"
					name="qdata" 
					rows="5" 
					cols="65" 
					style="font-size: 1rem"
					spellcheck="false" 
					autofocus
					required
				>$qstring</textarea>
				
				&nbsp;<br>
				&nbsp;<br>
				
				<table>
					<tr>
						<td class="basicbox">Query mode:</td>
						<td class="basicbox">
							<select$form_att id="qmode" name="qmode">
								$mode_options
							</select>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							<a id="ceqlManLink" 
									class="link external" 
									href="$ceql_url"
									data-tooltip="How to compose a search using the Simple Query language"
							>Simple query language syntax</a>
						</td>
					</tr>
					<tr>
						<td class="basicbox">Number of hits per page:</td>
						<td class="basicbox">
							<select$form_att name="pp">
								<option value="count">count hits</option>
								$pp_options
							</select>
						</td>
					</tr>
					
					$parallel_row
					$strategy_row
					$restriction_row
					
					<tr>
						<td class="basicbox">&nbsp;</td>
						<td class="basicbox">
							<button$form_att type="submit">Start query</button>
							<button$form_att type="button" id="resetQueryButton">Reset query</button>
						</td>
					</tr>
				</table>
				
				<div id="searchBoxAttributeInfo" style="$style_display">
					$cp_tutorial_callout
					<table>
						<tr>
							<td colspan="2"><strong>P-attributes in this corpus:</strong></td>
						</tr>
						<tr>
							<td width="40%"><code>word</code></td>
							<td><p>Main word-token attribute</p></td>
						</tr>
						$p_atts
						<tr>
							<td colspan="2">&nbsp;</td>
						</tr>
						<tr>
							<td colspan="2"><strong>S-attributes in this corpus:</strong></td>
						</tr>
						$s_atts
						<tr>
							<td colspan="2">&nbsp;</td>
						</tr>
						<tr>
							<td colspan="2"><strong>A-attributes in this corpus:</strong></td>
						</tr>
						$a_atts
					</table>
					<p>
						<a id="CqpTutorialLink" 
							class="link" 
							href="https://cwb.sourceforge.io/files/CQP_Manual.pdf"
							data-tooltip="Detailed help on CQP syntax"
						>The CWB project's CQP query language manual (English, PDF)</a>
					</p>
				</div>
	
	END_OF_HTML;

}


/**
 * Builds and returns an HTML string containing a short, compressed run-a-query form
 * with many options from the main Standard/Restricted query missing. 
 * 
 * @param  string $form_id       ID to give to the form (defaults to 'miniSearchBoxForm').
 * @param  string $qsubcorpus    Integer ID of pre-selected subcorpus (as string).
 * @return string                The assembled HTML (sequence of 3 td's).
 */
function print_mini_search_box(string $form_id = 'miniSearchBoxForm', ?string $qsubcorpus = null) : string
{
	global $Corpus;
// qsubcorpus: might we want to have all new queries run in a particular subcorpus/restruicitonm? (because this is used by dispersion)
	
	/* do we have a form ID specified? */
	$id_att   = " id=\"$form_id\"";
	$form_att = " form=\"$form_id\"";
	
	/* GET VARIABLES READY: the query mode. */
	$qmode = ($Corpus->case_sensitive ? 'sq_case' : 'sq_nocase');
	$mode_options = '';
	foreach (DESCRIBE_QUERY_MODE as $mode => $modedesc)
		$mode_options .= "\n\t\t\t\t\t\t\t<option value=\"$mode\"" . ($qmode == $mode ? ' selected' : '') . ">$modedesc</option>";
	
	/* ASSEMBLE THE RESTRICTIONS MINI-CONTROL TOOL */
	$restriction_dropdown = print_mini_restriction_search_tool($form_id, $qsubcorpus);
	
	return <<<END_OF_HTML
	
				<td class="layout-fg">
					<form$id_att></form>
					<button$form_att type="submit">Run query</button>
					<input$form_att type="text" name="qdata" placeholder="Enter query">
				</td>
				<td class="layout-fg">
					Query mode:
					<select$form_att name="qmode">
						$mode_options
					</select>
				</td>
				<td class="layout-fg">
					Restriction:
					$restriction_dropdown
				</td>
	
	END_OF_HTML;
}


/**
 * Helper for print_restriction_block()
 * 
 * @param  array  $header_row
 * @param  array  $body_row
 * @return string
 */
function render_restriction_cells_from_arrays(array $header_row, array $body_row) : string
{
	return "\n\t\t\t\t<tr>\n\t\t\t\t" 
			. $header_row[0] . $header_row[1] . $header_row[2] . "</tr>\n\t\t\t\t<tr>\n\t\t\t\t"
			. $body_row[0]   . $body_row[1]   . $body_row[2]   . "</tr>"
			;
//was rpreviously:  
// 			$header_row[0] . $header_row[1] . $header_row[2] . "</tr>\n\t\t\t\t<tr>\n\t\t\t\t"
// 			. $body_row[0]   . $body_row[1]   . $body_row[2]   . "</tr>\n\t\t\t\t<tr>\n\t\t\t\t"
// 			;
// but this meant that at least one tr ended up in the wrong place. 
// the change to the above pattern *should* fix that
}

/**
 * This provides the metadata restrictions block that is used for queries and for subcorpora.
 * 
 * @param  Restriction $insert_restriction       If not empty, contains a Restriction to be rendered in the form.
 * @param  string      $thing_to_produce         String labelling the thing the form will produce: "query", "subcorpus"
 * @param  string      $form_id                  If set, inputs will be given a form="THIS" attriburte. 
 * @return string                                HTML of the restriction block.
 */
function print_restriction_block(?Restriction $insert_restriction, string $thing_to_produce, ?string $form_id = null) : string
{
	global $Corpus;
	
	$t_tick = 0;
	
	$fid_string = is_null($form_id) ? '' : " form=\"$form_id\"" ;
	
	$block = <<<END_ROW
	
				<tr>
					<th class="layout-strong" colspan="3">
						Select the text-type restrictions for your $thing_to_produce:
					</th>
				</tr>
	
	END_ROW;
	
	/* TEXT METADATA */
	
	/* get a list of classifications and categories from sql; print them here as tickboxes */
	$classifications = list_text_metadata_classifications_as_desc_map($Corpus->id);
	
	$header_row = array();
	$body_row = array();
	$i = 0;
	
	foreach ($classifications as $c_handle => $c_desc)
	{
		$header_row[$i] = '<td width="33%" class="layout-bg algC">' .escape_html($c_desc) . '</td>';
		$body_row[$i] = '<td class="layout-fg nowrap" valign="top">';
		
		$catlist = list_text_metadata_categories_as_desc_map($Corpus->handle, $c_handle);
		
		foreach ($catlist as $handle => $desc)
		{
			$input_id = 'R:' . $c_handle . ':' . $handle;
			$t_value = '-|'   . $c_handle . '~' . $handle;
			$check = ( ( $insert_restriction && $insert_restriction->form_t_value_is_activated($t_value) ) ? 'checked ' : '');
			$body_row[$i] .= 
				"\n<input$fid_string id=\"$input_id\"" . ' type="checkbox" name="t~' . $t_tick++ . '" value="' . $t_value . '" ' . $check 
				. '> <label for="' . $input_id . '">' . ('' == $desc ? $handle : escape_html($desc)) . '</label><br>';
		}
		
		$body_row[$i] .= "\n&nbsp;</td>";
		
		$i++;
		/* print three columns at a time */
		if ( $i == 3 )
		{
			$block .= render_restriction_cells_from_arrays($header_row, $body_row);
			$i = 0;
		}
	}
	
	if ($i > 0) /* not all cells printed */
	{
		while ($i < 3)
		{
			$header_row[$i] = '<td class="layout-bg algC">&nbsp;</td>';
			$body_row[$i] = '<td class="layout-fg">&nbsp;</td>';
			$i++;
		}
		$block .= render_restriction_cells_from_arrays($header_row, $body_row);
	}
	
	if (empty($classifications))
		$block .= <<<END_ROW
		
					<tr>
						<td colspan="3" class="layout-bg algC comfy">There are no text classification schemes set up for this corpus.</td>
					</tr>
		
		END_ROW;
	
	$classification_elements_matrix = array();
	$idlink_elements_matrix         = array();
	
	$xml = get_all_xml_info($Corpus->handle);
	foreach ($xml as $x)
		if ($x->datatype == METADATA_TYPE_NONE)
			$classification_elements_matrix[$x->handle] = array();
	foreach ($xml as $x)
		if ($x->datatype == METADATA_TYPE_CLASSIFICATION)
			$classification_elements_matrix[$x->att_family][] = $x->handle;
		else if ($x->datatype == METADATA_TYPE_IDLINK)
			foreach (get_all_idlink_field_info($Corpus->handle, $x->handle) as $k=> $field)
				if ($field->datatype == METADATA_TYPE_CLASSIFICATION)
					$idlink_elements_matrix[$x->handle][$k] = $field;
	
	foreach($classification_elements_matrix as $k=>$c)
		if (empty($c))
			unset($classification_elements_matrix[$k]);
	
	/* we now know which elements we need a display for. */
	
	foreach ($classification_elements_matrix as $el => $class_atts)
	{
		/* We have already done <text>-level, above. Don't allow <text> to be a sub-text element. */
		if ('text' == $el)
			continue;
		
		$el_desc = escape_html($xml[$el]->description);
		$block .= <<<END_HTML
		
					<tr>
						<th class="layout-strong" colspan="3">Select sub-text restrictions for your $thing_to_produce &ndash; for <em>$el_desc</em> regions:</th>
					</tr>
		
		END_HTML;
		
		$header_row = array();
		$body_row = array();
		$i = 0;
		
		foreach($class_atts as $c)
		{
			$header_row[$i] = '<td width="33%" class="layout-bg algC">' . escape_html($xml[$c]->description) . '</td>';
			$body_row[$i] = '<td class="layout-fg nowrap" valign="top">';
			
			$catlist = list_xml_categories_as_desc_map($Corpus->handle, $c);
			
			$t_base_c = preg_replace("/^{$el}_/",  '', $c);
			
			foreach ($catlist as $handle => $desc)
			{
				$input_id = 'RXC: ' . $el . ':' . $t_base_c . ':' . $handle;
				$t_value = $el . '|'. $t_base_c . '~' . $handle;
				$check = ( ( $insert_restriction && $insert_restriction->form_t_value_is_activated($t_value) ) ? 'checked ' : '');
				$body_row[$i] .= 
						"\n<input$fid_string id=\"$input_id\"" . ' type="checkbox" name="t~' . $t_tick++ . '" value="' . $t_value . '" ' . $check 
						. '> <label for="' . $input_id . '">' . ($desc == '' ? $handle : escape_html($desc)) . '</label><br>';
			}
			
			/* whitespace is gratuitous for readability */
			$body_row[$i] .= '&nbsp;</td>';
			
			$i++;
			/* print three columns at a time */
			if (3 == $i)
			{
				$block .= render_restriction_cells_from_arrays($header_row, $body_row);
				$i = 0;
			}
		}
		
		if ($i > 0) /* not all cells printed */
		{
			while ($i < 3)
			{
				$header_row[$i] = '<td class="layout-bg algC"></td>';
				$body_row[$i] = '<td class="layout-fg"></td>';
				$i++;
			}
			$block .= render_restriction_cells_from_arrays($header_row, $body_row);
		}
	}
	
	//TODO we also need xml-within. Pattern for IDs: "RXW" for "XML within" IDs following "RXC" for "XML classifications". " 
	
	//TODO a lot of stuff is now repeated 3 times, for text metadata, xml classification, and idlink classifications. Look at factoring some of it out. 
	
	
	foreach ($idlink_elements_matrix as $el => $idlink_classifications)
	{
		$el_desc  = escape_html($xml[$el]->description);
		$fam_desc = escape_html($xml[$xml[$el]->att_family]->description);
		$block .= <<<END_HTML
		
					<tr>
						<th class="layout-strong" colspan="3">
							Select restrictions on <em>$el_desc</em> 
							for your $thing_to_produce &ndash; affects <em>$fam_desc</em> regions:
						</th>
					</tr>
		
		END_HTML;
		
		$header_row = array();
		$body_row = array();
		$i = 0;
		
		foreach ($idlink_classifications as $field_h => $field_o)
		{
			$header_row[$i] = '<td width="33%" class="layout-bg algC">' . escape_html($field_o->description) . '</td>';
			$body_row[$i] = '<td class="layout-fg nowrap" valign="top">';
			
// a bodge arising from tghe mixure of handle/handle and xml_id parmaters -- see call to list_idlink_categories_as_desc_map below.
			$field_o->att_handle = get_xml_info_by_id($field_o->xml_id)->handle;
			
			$catlist = list_idlink_categories_as_desc_map($Corpus->handle, $field_o->att_handle, $field_h);
			
			$t_base = preg_replace("/^{$xml[$el]->att_family}_/",  '', $el) . '/' . $field_h;
			
			foreach ($catlist as $handle => $desc)
			{
				$input_id = 'RID: ' . $el . ':' . $field_h . ':' . $handle;
				$t_value = $xml[$el]->att_family . '|'. $t_base . '~' . $handle;
				$check = ( ( $insert_restriction && $insert_restriction->form_t_value_is_activated($t_value) ) ? 'checked ' : '');
				$body_row[$i] 
						.= "\n<input$fid_string id=\"$input_id\"" . ' type="checkbox" name="t~' . $t_tick++ . '" value="' . $t_value . '" ' . $check 
						. '> <label for="' . $input_id . '">' . ($desc == '' ? $handle : escape_html($desc)) . '</label><br>';
			}
			
			/* whitespace is gratuitous for readability */
			$body_row[$i] .= '&nbsp;</td>';
			
			$i++;
			/* print three columns at a time */
			if ( $i == 3 )
			{
				$block .= render_restriction_cells_from_arrays($header_row, $body_row);
				$i = 0;
			}
		}
		
		if ($i > 0) /* not all cells printed */
		{
			while ($i < 3)
			{
				$header_row[$i] = '<td class="layout-bg algC">&nbsp;</td>';
				$body_row[$i]   = '<td class="layout-fg">&nbsp;</td>';
				$i++;
			}
			$block .= render_restriction_cells_from_arrays($header_row, $body_row);
		}
	}
	
	return $block;
}


/**
 * Create the HTML for a "restriction" dropdown in a search form.
 * 
 * @param  string $form_id                  ID of form these will be added to.
 * @param  int    $subcorpus_to_preselect   Integer subcorpus ID; if specified,
 *                                          that subcorpus will be preselected.
 * @return string                           HTML of the mini restriction dropdown (hidden input delimiters around a select).
 */
function print_mini_restriction_search_tool(string $form_id = '', ?int $subcorpus_to_preselect = null) : string
{
	global $Corpus;
	global $User;
	
	$form_att = empty($form_id) ? '' : " form=\"$form_id\"";
	
	/* create options for the Primary Classification */
	
	/* first option is always whole corpus */
	$restrict_options = "\n\t\t\t\t\t\t\t<option value=\"\"" . ( is_null($subcorpus_to_preselect) ? ' selected' : '' ) . '>None (search whole corpus)</option>' ;
	if ($field = $Corpus->primary_classification_field)
		foreach (list_text_metadata_categories_as_desc_map($Corpus->handle, $field) as $h => $c)
			$restrict_options .= "\n\t\t\t\t\t\t\t<option value=\"-|$field~$h\">".(empty($c) ? $h : escape_html($c))."</option>";
	
	/* list the user's subcorpora for this corpus, including the last set of restrictions used */
	$result = do_sql_query("select * from saved_subcorpora where corpus_id = {$Corpus->id} and user_id = {$User->id} order by savename");
	while ($sc = Subcorpus::new_from_db_result($result))
		if ($sc->savename == '--last_restrictions')
			$restrict_options .= "\n\t\t\t\t\t\t\t<option value=\"--last_restrictions\">Last restrictions ("
				. $sc->print_size_tokens() . ' words in ' 
				. $sc->print_size_items()  . ')</option>'
				;
		else
			$restrict_options .= "\n\t\t\t\t\t\t\t<option value=\"~sc~{$sc->id}\""
				. ($subcorpus_to_preselect == $sc->id ? ' selected' : '')
				. '>Subcorpus: ' . $sc->savename . ' ('
				. $sc->print_size_tokens() . ' words in ' 
				. $sc->print_size_items()  . ')</option>'
				;
	
	/* we now have all the subcorpus/restrictions options, so assemble the HTML */
	return <<<END_RESTRICT_DROPDOWN
	
							<select$form_att name="t">
								$restrict_options
							</select>
	
	END_RESTRICT_DROPDOWN;
	/* note above - no t-ticker, because this form only has one t. */
}


function do_ui_search() : void
{
	/* most of the hard work of this function is done by the inner "print search box" function
	 * and this function merely wraps it, yanks vars from GET, and begins/ends the form. */
	?>
	
	<table class="layout-frame fullwidth">
		<tr>
			<th class="layout-strong">Standard Query</th>
		</tr>
		<tr>
			<td class="layout-fg">
				<form class="runQueryForm" action="concordance.php" accept-charset="UTF-8" method="get"> 
					<?php
					echo print_search_box(
						'',
						$_GET['insertString']    ?? null,
						$_GET['insertType']      ?? null,
						$_GET['insertSubcorpus'] ?? null,
						true
					)
					, "\n";
					?>
				</form>
			</td>
		</tr>
	</table>
	
	<?php
}


function do_ui_restricted() : void
{
	/* insert restrictions: passed through to the restriction box build function. */
	if (isset($_GET['insertRestrictions']))
		$insert_r = Restriction::new_by_unserialise($_GET['insertRestrictions']);
	?>
	
	<form class="runQueryForm" action="concordance.php" accept-charset="UTF-8" method="get"> 
		<table class="layout-frame fullwidth">
			<tr>
				<th class="layout-strong" colspan="3">Restricted Query</th>
			</tr>
			<tr>
				<td class="layout-fg" colspan="3">
					<?php
					echo print_search_box(
						'',
						$_GET['insertString']  ?? null,
						$_GET['insertType']    ?? null,
						null,
						false
					)
					, "\n";
					?>
				</td>
			</tr>
			<?php  echo print_restriction_block($insert_r ?? null, 'query'), "\n";  ?>
		</table>
	</form>
	
	<?php
}


function do_ui_lookup() : void
{
	/* much of this is the same as the form for freq list, but simpler */
	
	global $Corpus;
	global $User;
	
	/* do we want to allow an option for "showing both words and tags"? */
	$annotation_available = !empty($Corpus->primary_annotation);
	
	$per_page = get_user_render_cache($User->id, $Corpus->id)->table_per_page ;
	
	?>
	
	<form class="runQueryForm" action="concordance.php" method="get">
		<input type="hidden" name="program" value="lookup">
		<?php echo $annotation_available ? '' : '<input type="hidden" name="lookupShowWithTags" value="0">' , "\n"; ?>
		<table class="layout-frame fullwidth">
			<tr>
				<th class="layout-strong" colspan="2">Word lookup</th>
			</tr>
			<tr>
				<td class="layout-bg comfy" colspan="2">
					You can use this search to find out how many words matching the form 
					that you look up occur in the corpus, and the different tags that they have.
				</td>
			</tr>
			<tr>
				<td class="layout-fg">Enter the word-form you want to look up</td>
				<td class="layout-fg">
					<input type="text" name="qdata" size="32" required>
					<br>
					<em>(N.B. you can use the normal wild-cards of Simple Query language)</em>
					<input type="hidden" name="qmode" value="<?php echo $Corpus->case_sensitive ? 'sq_case' : 'sq_nocase' ; ?>">
				</td>
			</tr>
			<tr>
				<td class="layout-fg">Show only words ...</td>
				<td class="layout-fg">
					<table>
						<tr>
							<td class="basicbox">
								<p class="nowrap">
									<input type="radio" id="lookupType:begin" name="lookupType" value="begin" checked>
									<label for="lookupType:begin">starting&nbsp;with</label>
								</p>
								<p class="nowrap">
									<input type="radio" id="lookupType:end" name="lookupType" value="end">
									<label for="lookupType:end">ending&nbsp;with</label>
								</p>
								<p class="nowrap">
									<input type="radio" id="lookupType:contain" name="lookupType" value="contain">
									<label for="lookupType:contain">containing</label>
								</p>
								<p class="nowrap">
									<input type="radio" id="lookupType:exact" name="lookupType" value="exact">
									<label for="lookupType:exact">matching&nbsp;exactly</label>
								</p>
							</td>
							<td class="basicbox" style="vertical-align:middle">... the pattern you specified</td>
						</tr>
					</table>
				</td>
			</tr>
			
			<?php
			if ($annotation_available)
			{
				?>
				
				<tr>
					<td class="layout-fg">List results by word-form, or by word-form AND tag?</td>
					<td class="layout-fg">
						<select name="lookupShowWithTags">
							<option value="1" selected>List by word-form and tag</option>
							<option value="0">Just list by word-form</option>
						</select>
					</td>
				</tr>
				
				<?php
			}
			?>
			
			<tr>
				<td class="layout-fg">Number of items shown per page:</td>
				<td class="layout-fg">
					<select name="pp">
						<option value="10"   <?php if ($per_page == 10)   echo 'selected'; ?>>10</option>
						<option value="50"   <?php if ($per_page == 50)   echo 'selected'; ?>>50</option>
						<option value="100"  <?php if ($per_page == 100)  echo 'selected'; ?>>100</option>
						<option value="250"  <?php if ($per_page == 250)  echo 'selected'; ?>>250</option>
						<option value="500"  <?php if ($per_page == 500)  echo 'selected'; ?>>500</option>
						<option value="1000" <?php if ($per_page == 1000) echo 'selected'; ?>>1000</option>
						<?php
						if ($User->is_admin())
							echo  "<option value=\"all\">show all</option>"; 
						echo "\n" ; 
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="layout-fg algC comfy" colspan="2">
					<button type="submit">Lookup</button>
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					<button type="reset">Clear the form</button>
				</td>
			</tr>
		</table>
	</form>
	
	<?php
}

function do_ui_keywords() : void
{
	global $Config;
	global $Corpus;
	global $User;
	
	/* =================================================
	 * create the options for frequency lists to compare
	 * ================================================= */
	
	/* THE ORDER IN LIST 1:
	 *       - Subcorpora (local, owned)               (lus)
	 *       - Granted subcorpora (local)              (lgs)
	 *       - Public subcorpora (local, non-owned)    (lps)
	 *       - Entire corpus                           (***)
	 * 
	 * THE ORDER IN LIST 2: 
	 *       - Remainder                               (***)
	 *       - Subcorpora (local, owned)               (lus)
	 *       - Granted subcorpora (local)              (lgs)
	 *       - Public subcorpora (local, non-owned)    (lps)
	 *       - Entire corpus                           (***)
	 *       - System corpora (any access level)       (fsc)
	 *       - User's own corpora.                     (fuc)
	 *       - Granted corpora.                        (fgc)
	 *       - User's nonlocal subcorpora              (fus)
	 *       - Public subcorpora (nonlocal, nonowned)  (fps)
	 *       - Granted subcorpora (nonlocal, nonowned) (fgs)
	 */
	
	// TODO, in future, maybe these could be filtered by the corpus->language value??
	// ................ this would save the list from getting super long.
	
	/* id / handle => title maps; needed for both local and public subcorpora */
	$sc_lookup   = array_map('escape_html', get_subcorpus_savename_mapper());
	$corp_lookup = array_map('escape_html', list_corpora_with_titles());
	$uc_lookup   = array_map('escape_html', list_user_corpora_with_titles());
	/* tool to lookup what corpus an sc is found in */
	$sc_corp_lookup = get_subcorpus_corpus_mapper();
	
	/* build option strings of various kinds */
	
	$option_groups = [
			'fsc'=>'',     'fuc'=>'',     'fgc'=>'',  /* foreign system corpora  | foreign user corpora | foreign granted corpora */
			'lus'=>'',     'lgs'=>'',     'lps'=>'',  /* local user subcorpora   | local granted SCs    | local public SCs        */
			'fus'=>'',     'fgs'=>'',     'fps'=>'',  /* foreign user subcorpora | foreign granted SCs  | foreign public SCs      */
	];
	
	
	/* fsc */
	foreach ($corp_lookup as $fc_name => $fc_desc)
		if ($fc_name != $Corpus->handle)  /* don't show the corpus we're presently "in". */
			if (PRIVILEGE_TYPE_NO_PRIVILEGE < max_user_privilege_level_for_corpus($User->username, $fc_name))
				$option_groups['fsc'] .=  "\t\t\t\t\t<option value=\"fsc~$fc_name\">Corpus: {$fc_desc}</option>\n" ;
	
	if ($Config->user_corpora_enabled)
	{
		/* fuc */
		foreach (list_user_corpora($User->username) as $uc_name)
			if ($uc_name != $Corpus->handle)
				$option_groups['fuc'] .=  "\t\t\t\t\t<option value=\"fuc~$uc_name\">Your corpus: {$uc_lookup[$uc_name]}</option>\n" ;
		
		/* fgc */
		if ($Config->colleaguate_system_enabled)
			foreach(get_all_user_corpus_grants_incoming($User->id) as $gr)
				if ($gr->corpus != $Corpus->handle)
					$option_groups['fgc'] .=  "\t\t\t\t\t<option value=\"fgc~{$gr->corpus}\">Corpus shared with you: {$uc_lookup[$gr->corpus]}</option>\n" ;
	}
	
	/* lus */
	foreach (list_freqtabled_subcorpora($User->username, true, true, true) as $sc_id)
		$option_groups['lus'] .= "\t\t\t\t\t<option value=\"lus~$sc_id\">Subcorpus: {$sc_lookup[$sc_id]}</option>\n" ;
	
	/* lgs */
	if ($Config->colleaguate_system_enabled)
		foreach (list_freqtabled_subcorpora($User->username, true, false, false) as $sc_id)
			$option_groups['lgs'] .= "\t\t\t\t\t<option value=\"lus~$sc_id\">Subcorpus shared with you: {$sc_lookup[$sc_id]}</option>\n" ;
	
	/* lps */
	foreach (list_public_freqtabled_subcorpora(true, false) as $sc_id)
		$option_groups['lps'] .= "\t\t\t\t\t<option value=\"lps~$sc_id\">Public subcorpus: {$sc_lookup[$sc_id]}</option>\n" ;
	
	if ($Config->user_corpora_enabled)
	{
		/* fus */
		foreach (list_freqtabled_subcorpora($User->username, false, true, false) as $sc_id)
			$option_groups['fus'] .= "\t\t\t\t\t<option value=\"fus~$sc_id\">Subcorpus: {$sc_lookup[$sc_id]}; from &ldquo;{$corp_lookup[$sc_corp_lookup[$sc_id]]}&rdquo;</option>\n" ;
		
		/* fgs */
		if ($Config->colleaguate_system_enabled)
			foreach (list_freqtabled_subcorpora($User->username, false, false, false) as $sc_id)
				$option_groups['fgs'] .= "\t\t\t\t\t<option value=\"fgs~$sc_id\">Subcorpus shared with you: {$sc_lookup[$sc_id]}; from &ldquo;{$corp_lookup[$sc_corp_lookup[$sc_id]]}&rdquo;</option>\n" ;
	}
	
	/* fps */
	foreach (list_public_freqtabled_subcorpora(false, true) as $sc_id)
		$option_groups['fps'] .= "\t\t\t\t\t<option value=\"fps~$sc_id\">Public subcorpus: {$sc_lookup[$sc_id]}</option>\n" ;
	
	
	/* create the options for selecting an attribute */
	$att_options = '<option value="word">Word forms</option>' . "\n";
	foreach (get_all_annotation_info($Corpus->id) as $a)
		if ($a->ft_required)
			$att_options .= "\t\t\t\t\t<option value=\"$a->handle\">" . escape_html($a->description) . "</option>\n";
	
	/* create the statistic-choice dropdown options, using the user's setting to add a "selected" in the right place. */
	$keystat_options = print_key_statistic_form_options($User->key_statistic);
	
	?>
	
	<form id="kwForm" class="greyoutOnSubmit" action="keywords.php" method="get">
		<input id="kwMethodInput" type="hidden" name="kwMethod" value=""><!-- value set dynamically to etiher "comp" or "key" -->
		<table class="layout-frame fullwidth">
			<tr>
				<th class="layout-strong" colspan="4">Keywords and key tags</th>
			</tr>
			<tr>
				<td class="layout-bg" colspan="4">
					<p>Keyword lists are calculated by comparing frequency lists you have set up for different subcorpora.</p>
					<p><a class="control-inline" href="index.php?ui=subcorpus">Click here to create/view subcorpus frequency lists</a>.</p>
				</td>
			</tr>
			<tr>
				<td class="layout-fg">Select frequency list 1:</td>
				<td class="layout-fg">
					<select id="kwTable1" name="kwTable1">
						<option disabled selected>Choose a subcorpus...</option>
						<?php echo "\n", $option_groups['lus'], $option_groups['lgs'], $option_groups['lps']; ?>
						<option value="::entire_corpus">Whole of <?php echo escape_html($Corpus->title); ?></option>
					</select>
				</td>
				<td class="layout-fg">Select frequency list 2:</td>
				<td class="layout-fg">
					<select id="kwTable2" name="kwTable2">
						<option value="::remainder" selected>Compare subcorpus to rest of this corpus</option>
						<?php echo "\n", $option_groups['lus'], $option_groups['lgs'], $option_groups['lps']; ?>
						<option value="::entire_corpus">Whole of <?php echo escape_html($Corpus->title); ?></option>
						<?php 
						echo "\n"
							, $option_groups['fsc'], $option_groups['fuc'], $option_groups['fgc']
							, $option_groups['fus'], $option_groups['fps'], $option_groups['fgs']
							;
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="layout-fg">Compare:</td>
				<td class="layout-fg" colspan="3">
					<select id="kwCompareAtt" name="kwCompareAtt">
						<?= $att_options ?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="layout-bg algC" colspan="4">
					<p class="spacer"></p>
				</td>
			</tr>
			<tr>
				<th class="layout-strong" colspan="4">Options for keyword analysis:</th>
			</tr>
			<tr>
				<td class="layout-fg">Show:</td>
				<td class="layout-fg">
					<input type="radio" id="kwWhatToShow:allKey"  name="kwWhatToShow" value="allKey" checked>
					&nbsp;&nbsp;
					<label for="kwWhatToShow:allKey">All keywords</label>
					<br>
					<input type="radio" id="kwWhatToShow:onlyPos" name="kwWhatToShow" value="onlyPos">
					&nbsp;&nbsp;
					<label for="kwWhatToShow:onlyPos">Positive keywords</label>
					<br>
					<input type="radio" id="kwWhatToShow:onlyNeg" name="kwWhatToShow" value="onlyNeg">
					&nbsp;&nbsp;
					<label for="kwWhatToShow:onlyNeg">Negative keywords</label>
					<br>
					<input type="radio" id="kwWhatToShow:lock"    name="kwWhatToShow" value="lock">
					&nbsp;&nbsp;
					<label for="kwWhatToShow:lock">Lockwords</label>
				</td>
				<td class="layout-fg">Display as:</td>
				<td class="layout-fg nowrap">
					<input type="radio" id="kwRender:table"  name="kwRender" value="table" checked>
					&nbsp;&nbsp;
					<label for="kwRender:table">Key items table (full results)</label>
					<br>
					<input type="radio" id="kwRender:clWmatrix" name="kwRender" value="clWmatrix">
					&nbsp;&nbsp;
					<label for="kwRender:clWmatrix">Textual wordcloud (Wmatrix-style)</label>
					<br>
					<input type="radio" id="kwRender:clGraphix" name="kwRender" value="clGraphix">
					&nbsp;&nbsp;
					<label for="kwRender:clGraphix">Graphical wordcloud (colorful style)</label>
				</td>
			</tr>
			<tr>
				<td class="layout-fg">Comparison statistic:</td>
				<td class="layout-fg">
					<select id="kwStatistic" name="kwStatistic">
						<?php echo $keystat_options; ?>
					</select>
				</td>
				<td class="layout-fg">
					Significance cut-off point:
					<br>(or confidence interval width)
				</td>
				<td class="layout-fg">
					<select id="kwAlpha" name="kwAlpha">
						<option value="0.05"           >5%</option>
						<option value="0.01"           >1%</option>
						<option value="0.001"          >0.1%</option>
						<option value="0.0001" selected>0.01%</option>
						<option value="0.00001"        >0.001%</option>
						<option value="0.000001"       >0.0001%</option>
						<option value="0.0000001"      >0.00001%</option>
						<option value="2.0"            >No cut-off</option>
					</select>
					<br>
					<input id="kwFamilywiseCorrect:Y" name="kwFamilywiseCorrect" value="Y" type="checkbox" checked>
					<label for="kwFamilywiseCorrect:Y">Use Šidák correction?</label>
				</td>
			</tr>
			<tr>
				<td class="layout-fg">Min. frequency (list 1):</td>
				<td class="layout-fg">
					<select id="kwMinFreq1" name="kwMinFreq1">
						<option>1</option>
						<option>2</option>
						<option selected>3</option>
						<option>4</option>
						<option>5</option>
						<option>6</option>
						<option>7</option>
						<option>8</option>
						<option>9</option>
						<option>10</option>
						<option>15</option>
						<option>20</option>
						<option>50</option>
						<option>100</option>
						<option>500</option>
						<option>1000</option>
					</select>
				</td>
				<td class="layout-fg">Min. frequency (list 2):</td>
				<td class="layout-fg">
					<select id="kwMinFreq2" name="kwMinFreq2">
						<option>0</option>
						<option>1</option>
						<option>2</option>
						<option selected>3</option>
						<option>4</option>
						<option>5</option>
						<option>6</option>
						<option>7</option>
						<option>8</option>
						<option>9</option>
						<option>10</option>
						<option>15</option>
						<option>20</option>
						<option>50</option>
						<option>100</option>
						<option>500</option>
						<option>1000</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="layout-fg algC comfy" colspan="4">
					<input type="button" id="kwSubmitWithKey" value="Calculate keywords">
				</td>
			</tr>
			<tr>
				<td class="layout-bg algC" colspan="4">
					<p class="spacer"></p>
				</td>
			</tr>
			<tr>
				<th class="layout-strong" colspan="4">View unique words or tags on one frequency list:</th>
			</tr>
			<tr>
				<td class="layout-fg" colspan="2" rowspan="2">Display items that occur in...</td>
				<td class="layout-fg" colspan="2">
					<input type="radio" id="kwEmpty:f1" name="kwEmpty" value="f1" checked>
					<label for="kwEmpty:f1">... frequency list 1 but NOT frequency list 2</label>
				</td>
			</tr>
			<tr>
				<td class="layout-fg" colspan="2">
					<input type="radio" id="kwEmpty:f2" name="kwEmpty" value="f2">
					<label for="kwEmpty:f2">... frequency list 2 but NOT frequency list 1</label>
				</td>
			</tr>
			<tr>
				<td class="layout-fg algC comfy" colspan="4">
					<input type="button" id="kwSubmitWithComp" value="Show unique items on list">
				</td>
			</tr>
		</table>
	</form>
	
	<?php
}


function do_ui_freqlist() : void
{
	/* much of this is the same as the form for keywords, but simpler */
	global $Corpus;
	global $User;
	
	$per_page = get_user_render_cache($User->id, $Corpus->id)->table_per_page;
	
	/* create the options for frequency list */
	$list_options = "<option value=\"::entire_corpus\">Whole of ". escape_html($Corpus->title) . "</option>\n";
	
	/* subcorpora belonging to this user that have freqlists compiled (list of IDs returned) */
	$subcorpora = list_freqtabled_subcorpora($User->username, true, true, true);
	/* public freqlists - corpora */
	
	$subc_mapper = array_map('escape_html', get_subcorpus_savename_mapper());
	foreach ($subcorpora as $s)
		$list_options .= "\t\t\t\t\t<option value=\"$s\">Subcorpus: {$subc_mapper[$s]}</option>\n";
	
	/* and the options for selecting an attribute */
	$att_options = '<option value="word">Word forms</option>' . "\n";
	
	foreach (list_annotations_with_freqtable_as_desc_map($Corpus->id, true) as $annot => $label)
		if ('word' != $annot)
			$att_options .= "\t\t\t\t\t<option value=\"$annot\">$label</option>\n";
	?>
	
	<form action="freqlist.php" method="get">
		<table class="layout-frame fullwidth">
			<tr>
				<th class="layout-strong" colspan="2">Frequency lists</th>
			</tr>
			<tr>
				<td class="layout-bg algL" colspan="2">
					<p>
						You can view the frequency lists of the whole corpus and frequency lists for subcorpora you have created. 
					</p>
					<p>
						<a class="control-inline" href="index.php?ui=subcorpus">Click here to create/view subcorpus frequency lists.</a>
					</p>
				</td>
			</tr>
			<tr>
				<td class="layout-fg">View frequency list for ...</td>
				<td class="layout-fg">
					<select name="flTable">
						<?= $list_options ?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="layout-fg">View a list based on ...</td>
				<td class="layout-fg">
					<select name="flAtt">
						<?= $att_options ?>
					</select>
				</td>
			</tr>
			<tr>
				<th class="layout-strong" colspan="2">Frequency list option settings</th>
			</tr>
			<tr>
				<td class="layout-fg">Filter the list by <em>pattern</em> &ndash; show only words/tags ...</td>
				<td class="layout-fg">
					<table>
						<tr>
							<td class="basicbox" style="padding-right: 4em;">
								<p class="nowrap">
									<input type="radio" id="flFilterType:begin" name="flFilterType" value="begin" checked>
									<label for="flFilterType:begin">starting&nbsp;with</label>
								</p>
								<p class="nowrap">
									<input type="radio" id="flFilterType:end" name="flFilterType" value="end">
									<label for="flFilterType:end">ending&nbsp;with</label>
								</p>
								<p class="nowrap">
									<input type="radio" id="flFilterType:contain" name="flFilterType" value="contain">
									<label for="flFilterType:contain">containing</label>
								</p>
								<p class="nowrap">
									<input type="radio" id="flFilterType:exact" name="flFilterType" value="exact" >
									<label for="flFilterType:exact">matching&nbsp;exactly</label>
								</p>
							</td>
							<td class="basicbox" rowspan="4">
								<input type="text" name="flFilterString" size="32">
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td class="layout-fg">Filter the list by <em>frequency</em> &ndash; show only words/tags ...</td>
				<td class="layout-fg">
					with frequency between
					<input type="text" name="flFreqLimit1" size="8">
					and
					<input type="text" name="flFreqLimit2" size="8">
				</td>
			</tr>
			<tr>
				<td class="layout-fg">Number of items shown per page:</td>
				<td class="layout-fg">
					<select name="pp">
						<option value="10"   <?php if ($per_page == 10)   echo 'selected'; ?>>10</option>
						<option value="50"   <?php if ($per_page == 50)   echo 'selected'; ?>>50</option>
						<option value="100"  <?php if ($per_page == 100)  echo 'selected'; ?>>100</option>
						<option value="250"  <?php if ($per_page == 250)  echo 'selected'; ?>>250</option>
						<option value="500"  <?php if ($per_page == 500)  echo 'selected'; ?>>500</option>
						<option value="1000" <?php if ($per_page == 1000) echo 'selected'; ?>>1000</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="layout-fg">List order:</td>
				<td class="layout-fg">
					<select name="flOrder">
						<option value="desc" selected>most frequent at top</option>
						<option value="asc">least frequent at top</option>
						<option value="alph">alphabetical order</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="layout-fg algC" colspan="2">
					<p class="spacer"></p>
					<p>
						<button type="submit">Show frequency list</button>
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						<button type="reset">Clear form</button>
					</p>
					<p class="spacer"></p>
				</td>
			</tr>
		</table>
	</form>
	
	<?php
}


function do_ui_corpusmetadata() : void
{
	global $Corpus;
	global $User;
	
	/* set up tokens / types / texts in suitable format for HTML print */
	$tokens_in_all_texts = empty($Corpus->size_tokens) ? 'Cannot be displayed  (wordcount not cached)'        : number_format($Corpus->size_tokens);
	$types_in_corpus     = empty($Corpus->size_types)  ? 'Cannot be calculated (frequency tables not set up)' : number_format($Corpus->size_types);
	$texts_in_corpus     = empty($Corpus->size_texts)  ? 'Cannot be calculated (text count not set up)'       : number_format($Corpus->size_texts);
	$type_token_ratio    = (empty($Corpus->size_tokens) || empty($Corpus->size_types))
							? 'Cannot be calculated (type or token count not available)'
							: number_format( ((float)$Corpus->size_types / (float)$Corpus->size_tokens) , 4) . ' types per token';
	if (1000 > $Corpus->size_tokens)
		$standard_ttr = 'Cannot be calculated (corpus too small for this statistic)';
	else
		$standard_ttr = empty($Corpus->sttr_1kw)   ? 'Cannot be displayed  (STTR not cached)'             : number_format($Corpus->sttr_1kw, 4) . ' types per token';

	/* this is a temporary place to stick the admin control for sttr. */
	if (empty($Corpus->sttr_1kw) && 1000 <= $Corpus->size_tokens)
		if ($User->is_admin())
			$standard_ttr .= ' &nbsp;&nbsp;&nbsp;<a class="control-inline greyoutOnSubmit" href="execute.php?function=update_corpus_sttr&args=' . $Corpus->id . '&locTo=' . urlencode('index.php?ui=corpusMetadata') . '">[Calculate and store!]</a>';
	?>
	
	<table class="layout-frame fullwidth">
		<tr>
			<th class="layout-strong" colspan="2">Metadata for <?php echo escape_html($Corpus->title); ?></th>
		</tr>
		
		<?php
		if ($User->is_admin())
		{
			?>
			
			<tr>
				<td class="layout-bg" width="50%"><strong>Corpus ID number</strong></td>
				<td class="layout-fg" width="50%"><?php echo '<strong>', $Corpus->id, '</strong> (base 36 code: <strong>', int_to_base_36($Corpus->id), '</strong>)'; ?></td>
			</tr>
			<tr>
				<td class="layout-bg" width="50%">Date of installation on system</td>
				<td class="layout-fg" width="50%"><?php echo render_sql_timestamp($Corpus->date_of_indexing); ?></td>
			</tr>
			
			<?php
		}
		?>
		
		<tr>
			<td class="layout-bg" width="50%">Corpus title</td>
			<td class="layout-fg" width="50%"><?php echo escape_html($Corpus->title); ?></td>
		</tr>
		<tr>
			<td class="layout-bg">CQPweb's short handles for this corpus</td>
			<td class="layout-fg"><?= "{$Corpus->handle} / {$Corpus->cqp_name}" ?></td>
		</tr>
		<tr>
			<td class="layout-bg">Total number of texts in corpus</td>
			<td class="layout-fg"><?= $texts_in_corpus ?></td>
		</tr>
		<tr>
			<td class="layout-bg">Total word tokens in all corpus texts</td>
			<td class="layout-fg"><?= $tokens_in_all_texts ?></td>
		</tr>
		<tr>
			<td class="layout-bg">Word types in the corpus</td>
			<td class="layout-fg"><?= $types_in_corpus ?></td>
		</tr>
		<tr>
			<td class="layout-bg">Standardized type:token ratio (1,000-token basis)</td>
			<td class="layout-fg"><?= $standard_ttr ?></td>
		</tr>
		<tr>
			<td class="layout-bg">Non-standardized type:token ratio</td>
			<td class="layout-fg"><?= $type_token_ratio ?></td>
		</tr>
		
		<?php
		/* VARIABLE METADATA */
		foreach(get_all_variable_corpus_metadata($Corpus->id) as $metadata)
		{
			/* if it looks like a URL, linkify it */
			if (0 < preg_match('|^https?://\S+$|', $metadata->value))
				$metadata->value = "<a class=\"link\" href=\"" . escape_html($metadata->value) . '">' . escape_html($metadata->value) . '</a>';
			else
				$metadata->value = escape_html($metadata->value);
			?>
			
			<tr>
				<td class="layout-bg comfy"><?= escape_html($metadata->attribute) ?></td>
				<td class="layout-fg comfy"><?= $metadata->value ?></td>
			</tr>
			
			<?php
		}
		?>
		
		<tr>
			<th class="layout-strong" colspan="2">Text metadata and word-level annotation</th>
		</tr>
		
		<?php
		/* TEXT CLASSIFICATIONS */
		$metadata_fields = list_text_metadata_classifications_as_desc_map($Corpus->id);
		$num_rows = count($metadata_fields);
		?>
		
		<tr>
			<td rowspan="<?php echo max([$num_rows, 1]); ?>" class="layout-bg">The database stores the following information for each text in the corpus:</td>
			<?php
			$i = 0;
			foreach($metadata_fields as $field_handle => $field_desc)
			{
				echo "\n\t\t\t\t", '<td class="layout-fg">', escape_html($field_desc), "</td>\n";
				if (++$i < $num_rows)
					echo "\n\t\t\t</tr>\n\t\t\t<tr>\n"; /* done every time except the last */
				if (!empty($Corpus->primary_classification_field))
					if ($field_handle == $Corpus->primary_classification_field)
						$description_primary = $field_desc;
			}
			if (0 == $num_rows)
				echo "\n\t\t", '<td class="layout-fg">There is no text-level metadata for this corpus.</td>'; 
			echo "\n";
			?>
		</tr>
		<tr>
			<td class="layout-bg">The <strong>primary</strong> classification of texts is based on:</td>
			<td class="layout-fg"><?php echo empty($description_primary) ? 'A primary classification scheme for texts has not been set.' : escape_html($description_primary) ; ?></td>
		</tr>
		
		<?php
		/* ANNOTATIONS */
		$array_of_annotations = get_all_annotation_info($Corpus->id);
		$num_rows = count($array_of_annotations);
		?>
		
		<tr>
			<td rowspan="<?php echo max([$num_rows, 1]); ?>" class="layout-bg">Words in this corpus are annotated with:</td>
			<?php
			$i = 0;
			foreach ($array_of_annotations as $annotation)
			{
				echo "\n\t\t\t\t", '<td class="layout-fg">';
				if (!empty($annotation->description))
				{
					echo escape_html($annotation->description);
					/* while we're looking at the description, save it for later if this is the primary annotation */
					if ($Corpus->primary_annotation == $annotation->handle)
						$primary_annotation_html = escape_html($annotation->description);
				}
				else
					echo $annotation->handle;
				
				if (!empty($annotation->tagset))
				{
					echo ' (';
					if (!empty($annotation->external_url))
						echo '<a target="_blank" href="', escape_html($annotation->external_url), '">', escape_html($annotation->tagset), '</a>';
					else
						echo escape_html($annotation->tagset);
					echo ')';
				}
				
				echo "</td>\n";
				if (++$i < $num_rows)
					echo "\t\t\t</tr>\n\t\t\t<tr>\n";
			}
			if(0 == $num_rows)
				echo "\n\t\t<td class=\"layout-fg comfy\">There is no word-level annotation in this corpus.</td>\n";
			?>
		</tr>
		<tr>
			<td class="layout-bg">The <strong>primary</strong> word-level annotation scheme is:</td>
			<td class="layout-fg"><?php echo empty($primary_annotation_html) ? 'No primary word-level annotation scheme has been set' : $primary_annotation_html; ?></td>
		</tr>
		
		<?php
		/* EXTERNAL URL */
		if (!empty($Corpus->external_url))
		{
			$esc_url = escape_html($Corpus->external_url);
			?>
			
			<tr>
				<td class="layout-bg">Further information about this corpus is available on the web at:</td>
				<td class="layout-fg"><a target="_blank" href="<?= $esc_url ?>"><?= $esc_url ?></a></td>
			</tr>
			
			<?php
		}
		?>
	
	</table>
	
	<?php
}


function do_ui_analysecorpus() : void
{
	// TODO this is shonky. Use collapsable menus. Use an object hierarchy.
	if (!empty($_GET['showMatrix']))
	{
		do_ui_showmatrix();
		return;
	}
	if (!empty($_GET['aboutMatrix']))
	{
		do_ui_aboutmatrix();
		return;
	}
	
	global $Config;
	global $Corpus;
	global $User;
	
	$subc_mapper = get_subcorpus_savename_mapper($Corpus->id);
	
	?>
	<table class="layout-frame fullwidth">
		<tr>
			<th class="layout-strong" colspan="3">Analyze corpus</th>
		</tr>
		<tr>
			<td class="layout-contrast" colspan="3">
				<p>
					This page contains controls for advanced corpus analysis functions.
					<strong>WARNING</strong>: currently under development. You have been warned.
				</p>
				<?php 
				if (!$Config->hide_experimental_features)
				{
					// temp link
					// TODO when well developed, will be an optioon on the proper "analyse corpus" menu below.
					?>
					<p class="algC">
						<a class="control-inline" href="index.php?ui=lgcurve">Click here for the experimental Lexical Growth Curve tool</a>
					</p>
					<?php 
					// dropdown may need to be "redirect" rather than a jQuery gubbins??? we'll see
				}
				?>
			</td>
		</tr>
		<tr>
			<th class="layout-strong" colspan="3">Select analysis</th>
		</tr>
		<tr>
			<td class="layout-bg comfy" width="33.3%">Choose an option for corpus analysis:</td>
			<td class="layout-fg algC comfy" width="33.3%">
				<select id="analysisToolChoice">
					<!-- values match the ID of the hideable element they refer to -->
					<option value="featureMatrixDesign" selected>Design feature matrix for multivariate analysis</option>
					<option value="featureMatrixList"           >View existing feature matrix analyses</option>
					<!-- More options will be added here later. -->
					<!-- Also: interface to corpus analysis plugins will be added here. -->
				</select>
			</td>
			<td class="layout-fg algC comfy" width="33.3%">
				<button type="button" id="analysisToolChoiceGo">Show analysis controls</button>
			</td>
		</tr>
	</table>
	
	<!-- begin saved feature matrix list block -->
	
	<table id="featureMatrixList" class="layout-frame fullwidth">
		<tr>
			<th class="layout-strong" colspan="9">Saved feature matrices</th>
		</tr>
		<tr>
			<th class="layout-strong">Name</th>
			<th class="layout-strong">Subcorpus</th>
			<th class="layout-strong">Object unit</th>
			<th class="layout-strong">N&nbsp;features</th>
			<th class="layout-strong">N&nbsp;objects</th>
			<th class="layout-strong">Date created</th>
			<th class="layout-strong" colspan="3">Actions</th>
		</tr>
		
		<?php
		$list = get_all_feature_matrices_info($Corpus->handle, $User->username);
		if (empty($list))
			echo '<tr><td class="layout-bg comfy" colspan="7">You have no saved features matrices.</td></tr>', "\n";
		foreach($list as $fm)
			echo "\t\t<tr>"
				, '<td class="layout-fg"><strong>' , escape_html($fm->savename), '</strong></td>'
				, '<td class="layout-fg">'
					, (empty($fm->subcorpus) ? '(whole corpus)' : escape_html($subc_mapper[$fm->subcorpus]) )
				, '</td>'
				, '<td class="layout-fg algC">' , $fm->unit , '</td>'
 				, '<td class="layout-fg algC">' , number_format(feature_matrix_n_of_variables($fm->id),0), '</td>'
 				, '<td class="layout-fg algC">' , number_format(feature_matrix_n_of_objects($fm->id),0), '</td>'
				, '<td class="layout-fg algC">' , substr($fm->date_of_user_save, 0, -3) , '</td>'
				, '<td class="layout-fg hollow">'
					, '<a class="control-box" href="index.php?ui=analyseCorpus&aboutMatrix=', $fm->id, '">[View/Analyze]</a>'
				, '</td>'
				, '<td class="layout-fg hollow">'
					, '<a class="control-box" href="multivariate-act.php?mvAction=downloadFeatureMatrix&matrix=', $fm->id, '">[Download]</a>'
				, '</td>'
				, '<td class="layout-fg hollow">'
					, '<a class="control-box" href="multivariate-act.php?mvAction=deleteFeatureMatrix&matrix=', $fm->id, '">[Delete]</a>'
				, '</td>'
				, "</tr>\n"
				;
		?>
	
	</table>
	
	<form id="featureMatrixDesign" action="multivariate-act.php" method="post">
		<input type="hidden" name="mvAction" value="buildFeatureMatrix">
		<table class="layout-frame fullwidth">
			<tr>
				<th class="layout-strong" colspan="2">Design feature matrix for multivariate analysis</th>
			</tr>
			<tr>
				<td class="layout-bg comfy" colspan="2">
					<?php /* TODO missing ui text */  ?>Explanation of the use of feature matrices goes here.
					Note it can be used for PCA, cluster analysis or factor analysis.
				</td>
			</tr>
			<tr>
				<th class="layout-strong" colspan="2">Select unit of analysis</th>
			</tr>
			<tr>
				<td class="layout-bg">Choose a unit of analysis (for factoring, clustering, etc.)</td>
				<td class="layout-fg">
					At the moment, the only choice is "text".
					<?php
					//However in the future, we might want ot make it possible to use other levels of XML in the corpus
					//e.g. utterance, paragraph, chapter, etc·
					?>
				</td>
			</tr>
			<tr>
				<th class="layout-strong" colspan="2">Define object labelling method</th>
			</tr>
			<tr>
				<td class="layout-bg">
					All data objects (e.g. texts) in a feature matrix need to have a label.
					<br>
					Choose one of the methods opposite for creation of object labels.
				</td>
				<td class="layout-fg">
					<select name="labelMethod">
						<option value="id"  selected>Use &ldquo;id&rdquo; attributes, if available (recommended!)</option> 
						<option value="n"           >Use &ldquo;n&rdquo; attributes, if available</option> 
						<option value="seq"         >Assign a number to each object in order (fallback method)</option>
					</select>
				</td> 
			</tr>
			<tr>
				<th class="layout-strong" colspan="2">Select texts<!--or, units more generally --></th>
			</tr>
			<tr>
				<td class="layout-bg" width="50%">
					Select a subcorpus or the full corpus.
					<br>
					Only the texts in the subcorpus you select will be included in the feature matrix.
					<!--
					<br>
					(when we add other possible levels, it will be possible to use subcorpora based on those divisions)
					-->
				</td>
				<td class="layout-fg">
					<select name="corpusSubdiv">
						<option selected value="~~full~corpus~~">Use the entire corpus</option>
						<?php
						$result = do_sql_query("select * from saved_subcorpora where corpus_id={$Corpus->id} and user_id={$User->id} order by savename");
						while ($sc = Subcorpus::new_from_db_result($result))
							if ('text' == $sc->get_item_type() && 'id' == $sc->get_item_identifier())
								echo "\n\t\t\t\t\t\t<option value=\"{$sc->id}\">"
									, "Subcorpus &ldquo;" , escape_html($sc->savename) , "&rdquo; (", $sc->print_size_items() , ")" 
									, "</option>"
									;
						echo "\n";
						?>
					</select>
				</td>
			</tr>
		</table>
		<table class="layout-frame fullwidth">
			<tr>
				<th class="layout-strong" colspan="5">Select features (from saved queries)</th>
			</tr>
			<tr>
				<td class="layout-bg" colspan="5">Use the tickboxes below to select the saved queries you want to include as features.</td>
			</tr>
			<tr>
				<th class="layout-strong">Use?</th>
				<th class="layout-strong">Name</th>
				<th class="layout-strong">No. of hits</th>
				<th class="layout-strong">Date</th>
				<th class="layout-strong">
					Discount?
					<br>
					<span style="font-size: 70%;">(100% = No discount)</span>
				</th>
			</tr>
			
			<?php
			$saved_qs_log = array();
			$result = do_sql_query("select * from `saved_queries` where corpus_id=$Corpus->id and user_id=$User->id and save_status=" . CACHE_STATUS_SAVED_BY_USER );
			for ($i = 0 ; null !== ($q = QueryRecord::new_from_db_result($result)) ; ++$i)
			{
				$saved_qs_log[$q->id] = $q->savename = escape_html($q->savename);
				echo "\n<tr>"
					, "\n\t<td class=\"layout-fg algC\"><input type=\"checkbox\" value=\"{$q->qid_for_url()}\" name=\"useQuery$i\"></td>"
					, "\n\t<td class=\"layout-fg algC\">{$q->savename}</td>"
					, "\n\t<td class=\"layout-fg algC\">", number_format($q->hits()), "</td>"
					, "\n\t<td class=\"layout-fg algC\">", $q->print_cache_time(), "</td>"
					, "\n\t<td class=\"layout-fg algC\"><input type=\"number\" size=\"4\" value=\"100\" name=\"q{$i}DR\"></td>"
					, "\n</tr>\n"
					;
			}
			if (empty($saved_qs_log))
				echo "\n<tr>"
					, "\n\t<td class=\"layout-bg algC\" colspan=\"5\">&nbsp;<br>You don't have any saved queries.<br>&nbsp;</td>"
					, "\n</tr>\n"
					;
			?>
		</table>
		<table class="layout-frame fullwidth">
			<tr>
				<th class="layout-strong" colspan="5">Select features (based on query permutation)</th>
			</tr>
			<tr>
				<td class="layout-fg" colspan="5">
					This is for features whose value can only be deduced by mathemtaical manipulation of more than one saved query.
					Typical example: where a feature is equal to (search for soemthing ) minus (search for something elsE) 
				</td>
			</tr>
			<tr>
				<th class="layout-strong">Use?</th>
				<th class="layout-strong">Operand # 1</th>
				<th class="layout-strong">Op</th>
				<th class="layout-strong">Operand # 2</th>
				<th class="layout-strong">
					Discount?
					<br>
					<span style="font-size: 70%;">(100% = No discount)</span>
				</th>
			</tr>
			
			<?php
// TODO have 15 rows fr now;  later, an embiggenable form will be needed. 
			$n_arithmetic = 15;
			if (0 == count($saved_qs_log))
			{
				$n_arithmetic = 0;
				echo "\n<tr><td class=\"layout-bg algC\" colspan=\"5\">&nbsp;<br>You don't have any saved queries.<br>&nbsp;</td></tr>\n";
			}
			
			for ($i = 1 ; $i <= $n_arithmetic ; $i++)
			{
				$use_name = "useManip" . $i;
				
				$q1_name = "manip{$i}q1";
				$q2_name = "manip{$i}q2";
				
				$op_input_name = "manip{$i}op";
				
				$sq_options = '<option disabled selected>Select a saved query ...</option>';
				
				foreach ($saved_qs_log as $qid => $sn)
					$sq_options .= "\n\t\t\t<option value=\"" . int_to_base_36($qid) . "\">$sn</option>";
				?>
				
				<tr>
					<td class="layout-fg algC">
						<input type="checkbox" name="<?php echo $use_name; ?>" value="1">
					</td>
					<td class="layout-fg algC" >
						<select name="<?php echo $q1_name; ?>">
							<?php echo $sq_options, "\n"; ?>
						</select>
					</td>
					<td class="layout-fg algC" >
						<input type="radio" id="<?php echo $op_input_name, ':', MultivarFeatureDef::OP_ADD ; ?>" 
							name="<?php echo $op_input_name; ?>" value="<?php echo MultivarFeatureDef::OP_ADD; ?>">
						<label for="<?php echo $op_input_name, ':', MultivarFeatureDef::OP_ADD ; ?>">
							<?php echo MultivarFeatureDef::get_op_string(MultivarFeatureDef::OP_ADD), "\n"; ?>
						</label>
						<br>
						<input type="radio" id="<?php echo $op_input_name, ':', MultivarFeatureDef::OP_SUBTRACT ; ?>" 
							name="<?php echo $op_input_name; ?>" value="<?php echo MultivarFeatureDef::OP_SUBTRACT; ?>" checked>
						<label for="<?php echo $op_input_name, ':', MultivarFeatureDef::OP_SUBTRACT ; ?>">
							<?php echo MultivarFeatureDef::get_op_string(MultivarFeatureDef::OP_SUBTRACT), "\n"; ?>
						</label>
						<br>
						<input type="radio" id="<?php echo $op_input_name, ':', MultivarFeatureDef::OP_MULTIPLY ; ?>" 
							name="<?php echo $op_input_name; ?>" value="<?php echo MultivarFeatureDef::OP_MULTIPLY; ?>">
						<label for="<?php echo $op_input_name, ':', MultivarFeatureDef::OP_MULTIPLY ; ?>">
							<?php echo MultivarFeatureDef::get_op_string(MultivarFeatureDef::OP_MULTIPLY), "\n"; ?>
						</label>
						<br>
						<input type="radio" id="<?php echo $op_input_name, ':', MultivarFeatureDef::OP_DIVIDE ; ?>" 
							name="<?php echo $op_input_name; ?>" value="<?php echo MultivarFeatureDef::OP_DIVIDE; ?>">
						<label for="<?php echo $op_input_name, ':', MultivarFeatureDef::OP_DIVIDE ; ?>">
							<?php echo MultivarFeatureDef::get_op_string(MultivarFeatureDef::OP_DIVIDE), "\n"; ?>
						</label>
					</td>
					<td class="layout-fg algC">
						<select name="<?php echo $q2_name; ?>">
							<?php echo $sq_options, "\n"; ?>
						</select>
					</td>
					<td class="layout-fg algC" >
						<input type="text" size="4" name="manip<?php echo $i; ?>DR" value="100">&nbsp;%
					</td>
				</tr>
				
				<?php
			}
			?>
		
		</table>
		<table class="layout-frame fullwidth">
			<tr>
				<th class="layout-strong" colspan="2">Select additional features</th>
			</tr>
			<tr>
				<th class="layout-strong">Use?</th>
				<th class="layout-strong">Description</th>
			</tr>
			<tr>
				<td class="layout-fg" colspan="2">
					<p>Allow extra features to be added that are not queries. The list of these is:</p>
					<ul>
						<li>Standardized type-token ratio (by increments of 400/1,000/2,000)</li>
						<li>Average word length (optionally limited to regular forms)</li>
<!-- 						<li>Average sub-unit length (as indicated by any XML element: s, p)</li>
						<li>Lexical density</li> 
						nb, lexical density can be done as e.g. _[N,V,J,R] or similar -->
					</ul>
					<p>Other statistical features can be defined via the saved-query feature function: e.g. lexical density.</p>
				</td>
			</tr>
			<tr>
				<td class="layout-bg algC">
					<input type="checkbox" id="useSpeshSttr04:1" name="useSpeshSttr04" value="1">
				</td>
				<td class="layout-fg">
					<label for="useSpeshSttr04:1">Standardized type-token ratio (400 token segments)</label>
				</td>
			</tr>
			<tr>
				<td class="layout-bg algC">
					<input type="checkbox" id="useSpeshSttr10:1" name="useSpeshSttr10" value="1">
				</td>
				<td class="layout-fg">
					<label for="useSpeshSttr10:1">Standardized type-token ratio (1,000 token segments)</label>
				</td>
			</tr>
			<tr>
				<td class="layout-bg algC">
					<input type="checkbox" id="useSpeshSttr20:1" name="useSpeshSttr20" value="1">
				</td>
				<td class="layout-fg">
					<label for="useSpeshSttr20:1">Standardized type-token ratio (2,000 token segments)</label>
				</td>
			</tr>
<!-- 			<tr> -->
<!-- 				<td class="layout-bg algC"> -->
<!-- 					<input type="checkbox" id="useSpeshTtr:1" name="useSpeshTtr" value="1"> -->
<!-- 				</td> -->
<!-- 				<td class="layout-fg"> -->
<!-- 					<label for="useSpeshTtr:1">Type-token ratio</label> -->
<!-- 				</td> -->
<!-- 			</tr> -->
			<tr>
				<td class="layout-bg algC">
					<input type="checkbox" id="useSpeshAvgWdLen:1" name="useSpeshAvgWdLen" value="1">
				</td>
				<td class="layout-fg">
					<label for="useSpeshAvgWdLen:1">Average word length: all wordforms</label>
					<br>
					Every token, including punctuation, is included.
				</td>
			</tr>
			<tr>
				<td class="layout-bg algC">
					<input type="checkbox" id="useSpeshAvgWdLenClean:1" name="useSpeshAvgWdLenClean" value="1">
				</td>
				<td class="layout-fg">
					<label for="useSpeshAvgWdLenClean:1">Average word length: with wordform cleanup.</label>
					<br>
					Excludes tokens that are punctuation, formulae, or otherwise unlikely to be &ldquo;real&rdquo; words.
				</td>
			</tr>
<!-- 			<tr> -->
<!-- 				<td class="layout-bg"> -->
<!-- 					<input type="checkbox" id="useSpeshXXXX:1" name="useSpeshXXXX" value="1"> -->
<!-- 				</td> -->
<!-- 				<td class="layout-fg"> -->
<!-- 					<label for=""></label> -->
<!-- 				</td> -->
<!-- 			</tr> -->
<!-- 			<tr> -->
<!-- 				<td class="layout-bg"> -->
<!-- 					<input type="checkbox" id="useSpeshXXXX:1" name="useSpeshXXXX" value="1"> -->
<!-- 				</td> -->
<!-- 				<td class="layout-fg"> -->
<!-- 					<label for=""></label> -->
<!-- 				</td> -->
<!-- 			</tr> -->
		</table>
		<table class="layout-frame fullwidth">
			<tr>
				<td class="layout-bg" width="50%">Enter a name for this new feature matrix:</td>
				<td class="layout-fg" width="50%">
					<input type="text" name="matrixName" size="70" maxlength="<?php echo NONHANDLE_MAX_STRING; ?>" placeholder="Enter a name to save the matrix under">
				</td>
			</tr>
		</table>
		<table class="layout-frame fullwidth">
			<tr>
				<td class="layout-fg algC" colspan="2">
					<button type="submit">Build feature matrix database!</button>
				</td>
			</tr>
		</table>
	</form>
	
	<?php
	/*
	
	TODO    development plan for the "analyse corpus" UI
	
	Here is what will be on the controls for a saved feature matrix.
	
	(1) Export feature matrix.
		- as a plain-text file for offline analysis 
	
	(2) Configure factor analysis.
		Anything that is not a pre-calculated statistic (avg word lenghtr etc.)
		is normalised by dividing by text length.
	
	
	THE KMO test - code is in the HTML file I downloaded from the web,
	
	it requires the ginv function from the MASS library, but full code for ginv()
	is available in the MASS manual.
	
	Have an option to do it.
	
	*/
}


// TODO more fruity treats needed in this display.
function do_ui_lgcurve() : void
{
	global $Config;
	global $User;
	global $Corpus;
	
	if ($Config->hide_experimental_features)
		exiterror("lgcurves are an experimental feature, disabled in your system.");
	?>
	
	<table class="layout-frame fullwidth">
		<tr>
			<th class="layout-strong" colspan="9">Lexical Growth Curve Tool</th>
		</tr>
		<tr>
			<td class="layout-contrast comfy" colspan="9"><strong>WARNING</strong> &ndash; Lexical Growth Curves are currently <strong>experimental</strong>.</td>
		</tr>
		<tr>
			<th class="layout-strong" colspan="9">Lexical growth curves currently stored on the system</th>
		</tr>
		<tr>
			<th class="layout-strong">ID</th>
			<th class="layout-strong">Annotation</th>
			<th class="layout-strong">Token-interval width</th>
			<th class="layout-strong">No. of datapoints</th>
			<th class="layout-strong">Date created</th>
			<th class="layout-strong">Time taken</th>
			<th class="layout-strong" colspan="3">Actions</th>
		</tr>
		<?php
		$lgc_list = get_all_lgcurves_info_by_corpus($Corpus->id);
		if (empty($lgc_list))
			echo "\n\t\t<tr><td class=\"layout-bg\" colspan=\"8\">Currently no Lexical Growth Curve data exists for this corpus.</td></tr>\n";
		
		$del_a = $User->is_admin()? '<a class="control-box" data-are-you-sure-q="Really delete this LG curve data?" href="lgcurve-act.php?lgAction=delete&lgcToDelete=~~ID~~">[Delete]</a>' : '';
		
		foreach ($lgc_list as $lgcurve)
			echo "\n\t\t<tr>"
				, "\n\t\t\t<td class=\"layout-bg\">" , $lgcurve->id , "</td>"
				, "\n\t\t\t<td class=\"layout-fg\">" , $lgcurve->annotation , "</td>"
				, "\n\t\t\t<td class=\"layout-fg\">" , number_format($lgcurve->interval_width), "</td>"
				, "\n\t\t\t<td class=\"layout-fg\">" , $lgcurve->n_datapoints , "</td>"
				, "\n\t\t\t<td class=\"layout-fg\">" , date(CQPWEB_UI_DATE_FORMAT, $lgcurve->create_time), "</td>"
				, "\n\t\t\t<td class=\"layout-fg\">" , number_format($lgcurve->create_duration), " s</td>"
				, "\n\t\t\t<td class=\"layout-fg hollow\">", '<a class="control-box" href="lgcurve-act.php?lgAction=download&lgcurve=', $lgcurve->id, '">[Download]</a></td>'
				, "\n\t\t\t<td class=\"layout-fg hollow\">", '<a class="control-box" href="dataviz.php?viz=lgc&curves=', $lgcurve->id, '">[Plot graph]</a></td>'
				, "\n\t\t\t<td class=\"layout-fg hollow\">", str_replace('~~ID~~', "$lgcurve->id", $del_a), '</td>'
				, "\n\t\t</tr>\n"
				;
		?>
	</table>
	<table class="layout-frame fullwidth">
		<tr>
			<th class="layout-strong" colspan="2">Generate new Lexical Growth Curve data</th>
		</tr>
		<tr>
			<td class="layout-bg" colspan="2">
				<p>
					To generate a lexical growth curve, pick which annotation you wish to apply it to (by default: just words),
					and the token interval at which you want to plot the number of cumulative types overserved.
				</p>
				<p>
					If you request a lexical growth curve with the same annotation and interval as one which already exists,
					it will <strong>not</strong> be recreated.
				</p>
			</td>
		</tr>
		<tr>
			<td class="layout-bg">Select the annotation on which to build a lexical growth curve:</td>
			<td class="layout-fg" width="50%">
				<form id="lgcurveGenerateForm" class="greyoutOnSubmit" action="lgcurve-act.php" method="get"></form>
				<input form="lgcurveGenerateForm" type="hidden" name="lgAction" value="generate">
				<select form="lgcurveGenerateForm" name="annotation">
					<option value="word" selected>Word</option>
					<?php
					foreach (get_all_annotation_info($Corpus->id) as $att)
						echo "\n\t\t\t\t\t\t<option value=\"", $att->handle, '">', escape_html($att->description), '</option>';
					echo "\n";
					?>
				</select>
			</td>
		</tr>
		<tr>
			<td class="layout-bg">Select your preferred interval (a good value is 1 thousandth the size of the corpus)</td>
			<td class="layout-fg" width="50%">
				<select form="lgcurveGenerateForm" name="intervalWidth">
					<?php
					/* nb. this setup is a bit shonky, mgiht need a tidy later. */
					$int_values = [1000, 10000, 50000, 100000, 500000, 1000000, 5000000, 10000000];
					$preselected = 1000;
					
					/* we don't need to worry about rounding here, we are just trying to pick one on roughly the right order of magnitude. */
					while ($Corpus->size_tokens/$preselected > 2000.0)
						$preselected *= 10;
					/* e.g. if corpus is 2 billion, this will lead to 1,000,000 (2 thousand points) */
					
					foreach($int_values as $v)
						echo "\n\t\t\t\t\t\t<option value=\""
							, $v
							, ($preselected == $v ? '" selected>': '">')
							, number_format($v)
							, '</option>'
							;
					echo "\n";
					?>
				</select>
			</td>
		</tr>
		<tr>
			<td class="layout-fg algC comfy" colspan="2">
				<button form="lgcurveGenerateForm" type="submit">Generate lexical growth curve data</button>
			</td>
		</tr>
	</table>
	
	<?php
}


function do_ui_export() : void
{
	global $Corpus;
	global $User;
	
	if (PRIVILEGE_TYPE_CORPUS_FULL > $Corpus->access_level)
		exiterror("You do not have permission to use this function.");
	?>
	
<!--	<form class="greyoutOnSubmit" action="export-corpus.php" method="get"> -->
	<form action="index.php" method="get">
		<input type="hidden" name="ui" value="exportGo">
		<table class="layout-frame fullwidth">
			<tr>
				<th class="layout-strong" colspan="2">Export corpus or subcorpus</th>
			</tr>
			<tr>
				<td class="layout-bg" colspan="2">
					<p>If you &ldquo;export&rdquo; a corpus, you download a copy of the whole text of the corpus (or one of your subcorpora),  allowing you to analyze it offline.</p>
					<p>Be warned: exports can be very big files, and may take a long time to generate and download!</p>
				</td>
			</tr>
			<tr>
				<td class="layout-fg">What do you want to export?</td>
				<td class="layout-fg">
					<select name="exportWhat">
						<option selected value="~~corpus">Whole corpus</option>
						<?php
						$sql = "select id, savename 
									from     saved_subcorpora 
									where    corpus_id  = {$Corpus->id} 
									and      user_id    = {$User->id} 
									and      savename  != '--last_restrictions' 
									order by savename";
						foreach (list_sql_values_as_map($sql, 'id', 'savename') as $id => $savename)
							echo "\n\t\t\t\t\t\t<option value=\"sc~", $id, '">Subcorpus &ldquo;', escape_html($savename), '&rdquo;</option>';
						echo "\n";
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="layout-fg" rowspan="3">Choose an export format:</td>
				<td class="layout-fg">
					<input type="radio" name="format" value="standard" id="format_standard" checked>
					<label for="format_standard">Standard plain text</label>
				</td>
			</tr>
			<tr>
				<td class="layout-fg">
					<input type="radio" name="format" value="word_annot" id="format_word_annot" >
					<label for="format_word_annot">Word-and-tag format (joined with forward-slash)</label>
				</td>
			</tr>
			<tr>
				<td class="layout-fg">
					<input type="radio" name="format" value="col" id="format_col">
					<label for="format_col">Columnar with all tags (CWB input format)</label>
				</td>
			</tr>
<!-- 			<tr> -->
<!-- 				<td class="layout-fg"> -->
<!-- 					<input type="radio" name="format" value="xml">XML format with all tags-->
<!-- 				</td> -->
<!-- 			</tr>	 -->
			<tr>
				<td class="layout-fg">Choose the operating system on which you will use the file:</td>
				<td class="layout-fg">
					<select name="exportLinebreak">
						<?php echo print_download_crlf_options(), "\n"; ?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="layout-fg" rowspan="2">What kind of download do you want?</td>
				<td class="layout-fg">
					<input type="radio" id="downloadZip:0" name="downloadZip" value="0" checked>
					<label for="downloadZip:0">A single text file</label>
				</td>
			</tr>
			<tr>
			
			<?php
			if (extension_loaded('zip'))
			{
				?>
				
				<td class="layout-fg">
					<input type="radio"id="downloadZip:1" name="downloadZip" value="1" >
					<label for="downloadZip:1">A zip file with separate files for each corpus text</label>
				</td>
				
				<?php
			}
			else
			{
				?>
				
				<td class="layout-contrast"><em>Zip file download not available (PHP "zip" extension missing)</em></td>
				
				<?php
			}
			?>
			
			</tr>
			<tr>
				<td class="layout-fg">Enter a name for the downloaded file:</td>
				<td class="layout-fg">
					<input type="text" name="exportFilename" value="<?php echo $Corpus->handle; ?>-export">
				</td>
			</tr>
			<tr>
				<td colspan="2" class="layout-fg algC comfy">
					<button type="submit">Export corpus data!</button>
				</td>
			</tr>
		</table>
	</form>
	
	<?php
}


function do_ui_exportgo()
{
	/* in order to not create a throbber on the export spec form page, we go to this page, trigger the download,
           and then let it keep going undisturbed by subsequent activities. */

	$dl_params = $_GET;
	unset($dl_params['ui']);

	$url = 'export-corpus.php?' . print_url_params($dl_params);

	$sub = preg_match('/^sc~/', $dl_params['exportWhat']) ? 'sub' : '' ;

	?>

	<script>

	$( function () { window.location.href = "<?=$url?>"; } );

	</script>

	<table class="layout-frame fullwidth">
		<tr>
			<th class="layout-strong">Your exported <?=$sub?>corpus is downloading</th>
		</tr>
		<tr>
			<td class="layout-fg comfy">You can continue to use CQPweb while the file downloads.</td>
		</tr>
		<tr>
			<td class="layout-bg comfy">Be warned: exports can be very big files, and may take a long time to generate and download!</td>
		</tr>
	</table>

	<?php
}
