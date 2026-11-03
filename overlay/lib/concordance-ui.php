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
 * This file contains the code for (a) doing and (b) displaying a corpus query as a concordance. 
 */


/* Allow for usr/x/xxxx/corpus: if we are 4 levels down instead of 2, move up 3 levels in the directory tree */
if (! is_dir('../lib'))
	chdir('../../../../exe');

require('../lib/environment.php');

/* include function library files */
require('../lib/sql-lib.php');
require('../lib/general-lib.php');
require('../lib/query-lib.php');
require('../lib/html-lib.php');
require('../lib/concordance-lib.php');
require('../lib/postprocess-lib.php');
require('../lib/ceqlparser.php');
require('../lib/cqp.php');
require('../lib/annotation-lib.php');
require('../lib/freqtable-lib.php');
require('../lib/corpus-lib.php');
require('../lib/metadata-lib.php');
require('../lib/exiterror-lib.php');
require('../lib/cache-lib.php');
require('../lib/scope-lib.php');
require('../lib/istream-lib.php');
require('../lib/db-lib.php');
require('../lib/useracct-lib.php');
require('../lib/usercorpus-lib.php');
require('../lib/plugin-lib.php');
require('../lib/xml-lib.php');



/* declare global variables */
$Corpus = $User = $Config = null;

cqpweb_startup_environment();


/* =============== *
 * QUERY VARIABLES *
 * =============== */

/* qid (query ID) is the overriding variable --- is it set?
 * If it is, then we are accessing an already-existing query in order to 
 * display it (or rather, an appropriate subset of it).
 * 
 * In this case, we don't need $qdata or anything like that.
 * 
 * If qid is not set, this is a new query (but it may be in cache, 
 * in which case we will get a query ID from the database). 
 */
if (isset($_GET['qid']))
{
	$qid = safe_qid_from_get();
	$incoming_qid_specified = true;
}
else
{
	$incoming_qid_specified = false;

	/* Handling of qdata && qmode && qstrategy. Also lookupType, if present.
	 *
	 * "qdata" is the contents of a query, either in CQP-syntax, or in
	 * the CEQL simple-syntax formalism. The qmode parameter indicates which
	 * of these it is. If a new query is to be performed, both these parameters
	 * are indispensible.
	 */
	if (!isset($_GET['qmode']))
		exiterror('No query mode was specified!');
	$qmode = prepare_query_mode($_GET['qmode'] ?? null);

	/* Ignore query strategy parameter in CQP syntax mdoe */
	$qstrat = ('cqp' == $qmode ? '' : ( $_GET['qstrategy'] ?? '' ));

	if (!isset($_GET['qdata']))
		exiterror('The content of the query was not specified!');
	$qdata = prepare_query_string($_GET['qdata'], $qstrat , $_GET['lookupType'] ?? null);

	/* cqpweb-new-ui: Case-sensitive box (case-insensitive unless ticked) */
	require_once('../lib/cqpweb-new-ui/site-lib.php');
	cp_apply_case_setting($qmode, $qdata);

	$case_sensitive = ($qmode === 'sq_nocase' ? false : true);

	$qscope = QueryScope::new_from_get();

	/* did the user specify restrictions that exclude the entirety of the corpus? */
	if (QueryScope::TYPE_EMPTY == $qscope->type)
		do_query_unsuccessful_page_and_exit('no_scope');
}

/*
 * Variable $postprocess describes all the postprocesses applied to a query; it
 * always starts as an empty string, but it may be added to later by new-postprocess,
 * or an existing postprocessor string may be loaded from memory.
 */
$postprocess = '';

/* load variables for new postprocesses */
$new_postprocess = null;
if (!empty($_GET['newPostP']))
{
	$new_postprocess = new CQPwebPostprocess();
	if ( !$new_postprocess->parsed_ok() )
	{
		flaunt($new_postprocess);
		exiterror('The parameters for query postprocessing could not be loaded!');
	}
	unset($_GET['pageNo']);
	/* so that we know it will go to page 1 of the postprocessed query */
}


/* in API mode, rendering params are ignored. */
if (!$Config->Api)
{

	/* =================== *
	 * RENDERING VARIABLES *
	 * =================== */

	$render_options = get_user_render_cache($User->id, $Corpus->id);

	/* 
	 * &pp=count indicates that rather than show ANY hits,
	 * we should just display how many hits there were,
	 * plus print out the command bar to allow additional analysis.
	 * 
	 * This is used to override the "program" variable 
	 * (which presumes we are NOT just counting the hits).
	 * 
	 * &pp=all means "show all hits".
	 * 
	 * If program is "lookup", then we are going straight to breakdown;
	 * so the pp applies to the breakdown table, not to the concordance.
	 */
	if (isset($_GET['pp']))
	{
		if ('all' == $_GET['pp'])
		{
			$render_options->conc_show_all_hits = true;
			$page_no = 1;
			/* leave the cached "conc per page" where it is. */
		}
		
		else if ($_GET['pp'] == 'count')
			$_GET['program'] = 'count_hits_then_cease';
		
		else if ( ($_GET['program'] ?? '') == 'lookup')
			$render_options->table_per_page = abs((int)$_GET['pp']);
		
		else
			$render_options->conc_per_page = abs((int)$_GET['pp']);
	}
	
	
	/* page number (or specific hit) specified? If not, go to page 1 */
	$page_no = abs((int)($_GET['pageNo'] ?? 1));
	
	if (isset($_GET['batch'])) /* batch overrules pageNo */
	{
		$batch = (int)$_GET['batch'];
		$page_no = 1 + intdiv($batch, $render_options->conc_per_page);
	}
	else
		$batch = $render_options->conc_per_page * ($page_no-1); /* = index of first hit on page */
	
	
	/* when translation is showing, only line mode is possible */
	if ($Corpus->visualise_translate_in_concordance)
		$_GET['viewMode'] = 'line';
	
	if ( isset($_GET['viewMode']) && ('kwic' == $_GET['viewMode'] || 'line' == $_GET['viewMode']) )
		$render_options->conc_kwicview = ( 'kwic' == ($view_mode = $_GET['viewMode']) );
	else
		$view_mode = $render_options->conc_kwicview ? 'kwic' : 'line';
	
	
	/* any possible aligned data in this corpus? */
	$align_info = check_alignment_permissions(list_corpus_alignments($Corpus->id));
	
	/* do we need to show the aligned region for each match? */
	/* override: we do not allow BOTH translation viz AND parallel corpus viz */
	if ($Corpus->visualise_translate_in_concordance)
		unset($_GET['showAlign']);
	
	if (isset($_GET['showAlign']))
	{
		if ( isset($align_info[$_GET['showAlign']]) )
			$render_options->conc_show_align_att = $_GET['showAlign'];
		else
			$render_options->conc_show_align_att = null;
	}
	/* else, the value is taken from the render options cache */
	
	
	/* commit altered rendering settings, then create convenience variables */
	commit_user_render_cache($render_options);
	
	$show_all_hits         = $render_options->conc_show_all_hits;
	$per_page              = $render_options->conc_per_page;
	$alignment_att_to_show = $render_options->conc_show_align_att;
	$show_align            = !empty($alignment_att_to_show);
	
	
	/* the $program variable: filtered by a switch to admit only OK values;
	 * note this is only used for the RENDERING of the query */
	switch($_GET['program'] ?? null)
	{
	case 'sort':
	case 'lookup':
	case 'categorise':
	case 'count_hits_then_cease':
		$program = $_GET['program'];
		break;
	default:
		$program = 'search';
		break;
	}

}



/*
 * ====================================
 * END OF SETTING UP VARIABLES FROM GET
 * ====================================
 */

/* timer begins at this point */
$start_time = microtime(true);


/* start by assuming that an old query can be dug up */
$run_new_query = false;
/* this will, or will not, be disproven later on     */

/* and set $n_of_solutions so it fails-safe to 0   */
$n_of_solutions = 0;

/* and flag a history insertion as NOT done (this variable will be set to true when it is) */
$history_inserted = false;



/* ------------------------------------------------------------------------ *
 * START OF CHUNK THAT CHECKS THE CACHE AND PREPARES THE QUERY IF NO RESULT *
 *                                                                          *
 * Based on the "if" below, we follow either a method for                   *
 * a query that is in cache or a method for a query that isn't              *
 * ------------------------------------------------------------------------ */

if ( $incoming_qid_specified )
{
	/* CHECK FOR CACHED QUERY WITH GIVEN QID */
	
	/* check the cache ; if query not found in cache, abort */
	if  ( !($cache_record = QueryRecord::new_from_qid($qid)) )
		exiterror("The query you requested (no. $qid) was not found in the query cache. Please return to the search screen and start again.");
	if ( 0 == ($n_of_solutions = $cache_record->hits()) )
		exiterror("The query you requested (no. $qid) does not appear to contain any data. Please return to the search screen and start again.");
	
	/* if we've got this far, the cached query has been found and it DOESN'T contain 0 solutions */
	
	/* touch the query, updating its "time" to now */
	$cache_record->touch();
	
	/* take info from the cache record, and copy it to script variables */
	$qmode        = $cache_record->query_mode;
	$cqp_query    = $cache_record->cqp_query;
	$simple_query = $cache_record->simple_query;
	/* overwrite the previously-established $qscope since we have loaded a cached query */
	$qscope       = $cache_record->qscope;
	$postprocess  = $cache_record->postprocess;
	
	/* next stop on this track is POSTPROCESS then DISPLAYING THE QUERY */
}
else
{
	/* QID WAS NOT SPECIFIED: PERFORM QUERY OR RETRIEVE FROM CACHE */
	
	/* derive the $cqp_query and $simple_query variables and put the query into history */
	if ('cqp' == $qmode)
	{
		$simple_query = '';
		$cqp_query = $qdata;
	}
	else /* if this is a simple query */
	{
		/* keep a record of the simple query */
		$simple_query = $qdata;
		
		$ceql_errors = null;
		
		/* convert the simple query to a CQP query */
		if (is_null($cqp_query = process_simple_query($qdata, $case_sensitive, $ceql_errors)))
		{
			/* if conversion fails, add to history & then add syntax error code : -1 = CEQL error. */
			$history_record_id = history_insert('', $qscope->serialise(), $simple_query, ($case_sensitive ? 'sq_case' : 'sq_nocase'));
			history_update_hits($history_record_id, -1);
			
			/* and then call an error with the array of diagnostic strings from CEQL. */
			exiterror($ceql_errors);
		}
	}
	
	/* we now have the query in CQP-syntax: the query can now go into history. */
	$history_record_id = history_insert($cqp_query, $qscope->serialise(), $simple_query, $qmode);
	$history_inserted = true;
	
	/* look in the cache for a query that matches this one on crucial parameters */
	$cache_record = QueryRecord::new_from_params($Corpus->handle, $qmode, $cqp_query, $qscope);
	
	if  ( is_null($cache_record) || 0 == ($n_of_solutions = $cache_record->hits()) )
	{
		/* query is not found in cache at all - therefore, it needs to be run anew,
		 * and said new query inserted into cache with a brand-new qname.
		 * Queries with no solutions are also re-run.
		 */
		$run_new_query = true;
		
		/* for new queries, we must check whether to apply the user's auto-randomise setting;
		 * but this is only applied if no other postprocess has been asked for. */
		if (!$new_postprocess && !$User->conc_corpus_order)
		{
			$_GET['newPostP'] = 'rand';
			$new_postprocess = new CQPwebPostprocess();
			/* no need to check whether it parsed correctly, cos we know it did! */
			$page_no = 1;
			/* so that we know the display will go to page 1 of the postprocessed query */
		}
	}
	else
	{
		/* we have a query in the cache with the same cqp_query, qmode, etc. */
		
		/* take info from the cache record, and copy it to script variables;
		 * note: the other parameter variables are what we matched on, so no need to copy */
		$qid = $cache_record->id;
		
		/* touch the query, updating its "time" to now */
		$cache_record->touch();
		/* next stop from here is POSTPROCESS then DISPLAYING THE QUERY */
	}
}


/* time to start using CQP */
$cqp = get_global_cqp();


if ($run_new_query)
{
	/* ------------------------------- *
	 * RUN THE QUERY AND GET SOLUTIONS *
	 * ------------------------------- */
	
	/* if we are here, it is a brand new query -- not saved or owt like that. Get a name to save it under. */
	$temp_name = cqpname_create_temp();
	
	/* set restrictions / activate subcorpus */
	$qscope->insert_to_cqp();
	
	/* Load user macros! */
	load_user_macros_to_cqp($User->id);
	
	/* this is the business end */
	$cqp->query("$temp_name = $cqp_query");
	
	/* now that we have the query, find out its size */
	
	if (0 == ($n_of_solutions = $cqp->querysize($temp_name)) )
	{
		/* no solutions: update the history, then send the user a message and exit */
		if ($history_inserted)
			history_update_hits($history_record_id, 0);
		do_query_unsuccessful_page_and_exit();
	}
	else
	{
		/* otherwise, save the query file to disk, and create a cache record. 
		 * we save the query under a temp name rather than use "$qname = $temp_name
		 * because CQP doesn't have copy-on-write, so this would double the maount of RAM used;
		 * not a problem in some situations, but we might have a HUGE set of intervals here.
		 */
		$cqp->save($temp_name);
		
		$n_of_texts = count( $cqp->execute("group $temp_name match text_id") );
		/* note that this field in the record always refers to the ORIGINAL n of texts
		 * so, it is OK to set it here and not anywhere else (as postprocesses don't affect it) */
		
		/* put the query in the cache and get a cache-record object.*/
		$cache_record = QueryRecord::create(
				$User->id, 
				$Corpus->id, 
				$qmode, 
				$simple_query, 
				$cqp_query, 
				$qscope,
				$n_of_solutions, 
				$n_of_texts
				);
		$cache_record->assure_cqp_file_from_temp_save($temp_name);
		$cache_record->save();
		
		$cqp->discard($temp_name); /* release RAM, and avoid any issues with the savefile rename */
		
		$qid = $cache_record->id;
		$qname = $cache_record->qname();
		
		/* delete a cache file with this name if it exists (it shouldn't); then relocate the temp file thither. */
		cqp_file_unlink($qname);
		cqp_file_move($temp_name, $qname);
		refresh_directory_global_cqp();
		/* ... so that CQP knows about the named-query-result under its new name */
	}
}
else
	/* whereas if ! $run_new_query, the query has been retrieved from cache;
	 * all we need to do is get the qname. */
	$qname = $cache_record->qname();



/* set flag in history for query completed (IFF a record was created): overwrite default "run error" value of -3  */
if ($history_inserted)
	history_update_hits($history_record_id, $n_of_solutions);




/* ----------------------- *
 * START OF POSTPROCESSING *
 * ----------------------- */

/* note that, for reasons of auto-thinning queries for users with restricted access, all this bit is inside a once-only loop */ 
do
{
	if ($new_postprocess)
	{
		/* Add the new postprocess to the existing postprocessor string, and look it up
		 * by parameter (using cqp_query, query_mode, query_scope, postprocessor string)  */
		
		$postprocess = $new_postprocess->add_to_postprocess_string($postprocess);
		
		$check_cache_record = QueryRecord::new_from_params($Corpus->handle, $qmode, $cqp_query, $qscope, $postprocess);
		
		/* If it exists, the orig qname is replaced by this one */
		if ( !is_null($check_cache_record) )
		{
			/* dump the cache record retrieved or created above and use this one */
			$cache_record = $check_cache_record;
			$cache_record->touch();
			$qid   = $cache_record->id;
			$qname = $cache_record->qname();
		}
		else
		{
			/* If it doesn't exist, the postprocess is applied to the cache record previously found/created */
			
			/* do_postprocess: returns false if the postprocess did not work. */
			if ( ! $cache_record->do_postprocess($new_postprocess) )
				do_query_unsuccessful_page_and_exit('empty_postproc');
			
			/* calling do_postprocess() re-sets cr->postprocess and cr->hits_left etc. in the new query that is created; 
			 * it also touches the time, caches, and sets the new query to unsaved. */
			
			/* and, because this means we are dealing with a query new-created in cache... */
			$run_new_query = true;
			/* so that it won't say the answer was retrieved from cache in the heading */
		}
		
		$qid   = $cache_record->id;
		$qname = $cache_record->qname();
		/* this is the ID of the cached query which the rest of the script will render */
	}
	/* endif $new_postprocess */
	
	
	
	/* --------------------- *
	 * END OF POSTPROCESSING *
	 * --------------------- */
	
	/* we can stop the timer now */
	$time_taken = microtime(true) - $start_time;
	
	/* whatever happened above, $n_of_solutions contains the number of solutions in the original query.
	 * BUT a postprocess can reduce the number of solutions that get rendered and thus the number of pages.
	 * $n_of_solutions_final == the number of solutions all AFTER postprocessing.
	 */
	$n_of_solutions_final = $cache_record->hits();
	
	/* we can now check if there are too many hits in the query for a restricted access query! 
	 * if there are, we need to set up for a *reduce the query via random thin* postprocess, then
	 * use a "continue" in this while-true-break to repeat the application of postprocessing. */
	if (PRIVILEGE_TYPE_CORPUS_RESTRICTED >= $Corpus->access_level)
	{
		/* we only need to look at the section searched initially, 
		 * because we don't care about subsequent distribution-postproc reductions. */
		$restrict_access_max = max_hits_when_restricted($cache_record->get_tokens_searched_initially());
		
		if ($n_of_solutions_final > $restrict_access_max)
		{
			/* we DO need to thin down the concordance result to a smaller n of hits! 
			 * To do this: set new values to trigger a THIN of the query. */
			$_GET['newPostP'] = 'thin';
			$_GET['newPostP_thinTo'] = $restrict_access_max;
			$_GET['newPostP_thinHitsBefore'] = $n_of_solutions_final;
			$_GET['newPostP_thinReallyRandom'] = 0; /* we use REPRODUCIBLE thin so what users get to see is deterministic. */
			/* We do not need to remove any existing newPostP parameters: they were all cleared out 
			 * the last time the CQPwebPostprocess object was constructed (or, there were none to begin 
			 * with && the CQPwebPostprocess constructor has never been called).  */
			$new_postprocess = new CQPwebPostprocess();
			$page_no = 1;
			/* so that we know the display will go to page 1 of the postprocessed query */
			continue;
		}
	}
	
	/* otherwise (or, AFTER the re-loop) we just proceed out of the do-while-false "loop" and carry on ..... */
	break;

} while (true);


/* so we can work out how many pages there are (also == the # of the last page) */
if ($show_all_hits && 0 < $n_of_solutions_final)
	$per_page = $n_of_solutions_final;
	/* which will make the next statement set $n_of_pages to 1 */

$n_of_pages = ceil($n_of_solutions_final / $per_page);

/* make sure current page number is within feasible scope */
if ($page_no > $n_of_pages)
	$page_no = $n_of_pages;



/* ----------------------- *
 * DISPLAY THE CONCORDANCE *
 * ----------------------- */

/* if we're in Api mode, we donm't display a concordance at all. We just send back the query id (as integer not base36). */
if ($Config->Api)
{
	$Config->Api->set_response_content($cache_record->id);
	cqpweb_shutdown_environment();
	exit(0);
}

/* if program is word-lookup, we don't display here - we go straight to freqlist. */
if ('lookup' == $program)
{
	header("Location: breakdown.php?qid=".urlqid_from_qid($qid)."&concBreakdownOf=" . (0==$_GET['lookupShowWithTags'] ? 'words' : 'both'));
	cqpweb_shutdown_environment();
	exit(0);
}

/* that's all the checks for alternative ways of display */


if ('search' == $program)
	/* $program == sort comes from the detection of a sort being done */
	if ($cache_record->last_postprocess_is_sort())
		$program = 'sort';


/* list of JS files: any specified extra code files, plus the standard categorise func. */
$js_scripts = deduce_extra_code_files_for_header('conc', 'js');
$js_scripts[] = 'new-query';
if ('categorise' == $program)
	$js_scripts[] = 'categorise';

	//TODO other ui should have headings like this! Collocation, distribution, dispersion, breakdown, .... freqlist etc.
echo print_html_header(
							$cache_record->print_query_search_term() . escape_html(" | $Corpus->title | CQPweb Concordance"), 
							$Config->colour_scheme, 
							$js_scripts, 
							deduce_extra_code_files_for_header('conc', 'css')
						);


/* ----------------------------- *
 * page heading && control lines *
 * ----------------------------- */
?>

<table class="layout-frame fullwidth">
	<tr>
<!--
		not activated because it's all pretty shonky; do full reorg of "exit" options when prog=categorise
		<th class="layout-strong" colspan="<?= ('categorise' == $program ? 1 : 0) + (!empty($align_info) && 'count_hits_then_cease' != $program) ? 9 : 8 ?>">
-->
		<th class="layout-strong" colspan="<?= (!empty($align_info) && 'count_hits_then_cease' != $program) ? 9 : 8 ?>">
			<?= $cache_record->print_solution_heading(), print_time_taken_as_span($time_taken, $run_new_query), "\n" ?>
		</th>
	</tr>

	<?php
	$control_row = print_control_row(
						$cache_record,
						array_merge(
							array(
								'program'     => $program, 
								'num_of_pages'=> $n_of_pages, 
								'page_no'     => $page_no, 
								'view_mode'   => $view_mode, 
								'align_info'  => $align_info,
							), 
							$show_align ? ['alignment_att'=> $alignment_att_to_show] : []
						)
					);
	if ('sort' == $program)
	{
		/* if the query being displayed is sorted, then we need to put the sort position 
		 * into the control row, and we also need a second control row for the sort. */
		$sort_pos_recall = 0;
		$sort_control_row = print_sort_control($cache_record, $sort_pos_recall);
		echo add_sortposition_to_control_row($control_row, $sort_pos_recall) , $sort_control_row ;
	}
	else
		echo $control_row;
	
	
	/* having done the control row, it is time to exit if we are in count-hits mode */
	if ('count_hits_then_cease' == $program)
	{
		echo "</table>\n";
	
		/* we need the popup forms though! */
		do_conc_popup_colloc_control($cache_record);
		do_conc_popup_thin_control($cache_record);
		do_conc_popup_modify_bounds_control($cache_record);
	
		echo print_html_footer('concordance');
		cqpweb_shutdown_environment();
		exit(0);
	}


	/* 
	 * -----------------------------------------------------------------------------------------------------------
	 * set up CQP options for the concordance display and store them in the kwic line manager for the concordance.
	 * -----------------------------------------------------------------------------------------------------------
	 */
	
	
	/* turn on use of our various non-ambiguous delimiters, not confusable with token data when we parse CQP KWIC lines. */ 
	activate_delimiters_global_cqp();
	
	$cqp->execute("set Context {$Corpus->conc_scope} " . ($Corpus->conc_scope_is_based_on_s ? $Corpus->conc_s_attribute : 'words'));
	
	$kwic_engine = new KwicLineManager();
	
	/* what p-attributes to show? (annotations); declare them to the kwic engine too */
	
	$cqp->set_show_attribute('word', true);
	
	if ($Corpus->visualise_gloss_in_concordance)
	{
		$cqp->set_show_attribute($Corpus->visualise_gloss_annotation, true);
		$kwic_engine->declare_shown_annotation($Corpus->visualise_gloss_annotation);
	}
	else if (!empty($Corpus->primary_annotation))
	{
		/* note that $Corpus->primary_annotation should only be empty in an unannotated corpus. */
		$cqp->set_show_attribute($Corpus->primary_annotation, true);
		$kwic_engine->declare_shown_annotation($Corpus->primary_annotation);
	}

	foreach (list_annotations_for_token_data($Corpus->id, 'conc') as $handle)
	{
		/* both the CQP and KwicLineManager objects check for duplicate insertion, so we don't need to */
		$cqp->set_show_attribute($handle, true);
		$kwic_engine->declare_shown_annotation($handle, true); /* note the "true" : we are also declaring these as the span-data annotations */
	}


	/* what inline s-attributes to show? (xml elements) */
	$xml_tags_to_show = xml_visualisation_s_atts_to_show('conc');
	if ( !empty($xml_tags_to_show) )
		$cqp->execute('show +' . implode(' +', $xml_tags_to_show));
	
	
	/* what a-attributes to show? (parallel lines) */
	if ($show_align)
		$cqp->execute('show +' . $alignment_att_to_show);
	/* what start-of-kwic locator s-attributes to show? */
	$cqp->execute('set PrintStructures "' 
					. ($Corpus->visualise_translate_in_concordance ? "{$Corpus->visualise_translate_s_att} " : '') 
					. 'text_id'
					. ($Corpus->visualise_position_labels ? " {$Corpus->visualise_position_label_attribute}" : '')
					. '"'
					); /* these settings don't need to be passed to the KwicLineManager: it will refer to the same corpus settings. */
	
	
	
	/* give the kwic engine the highlight-positions table */
	
	$highlight_show_tag = false; /* which the next call *may* change to true */
	$kwic_engine->add_highlight_position_table($cache_record->get_highlight_position_table($highlight_show_tag));

	/* even if tags are to be shown, don't do so if no primary annotation is specified, or if we are glossing the text */
	$highlight_show_tag = ( $highlight_show_tag && !empty($Corpus->primary_annotation) && !$Corpus->visualise_gloss_in_concordance );
	$kwic_engine->set_display_param('highlight_show_tag', $highlight_show_tag);


	/* other params to pass into the kwic_engine */

	$kwic_engine->set_display_param('view_mode', $view_mode);
	$kwic_engine->set_display_param('qid', $qid);
	$kwic_engine->set_display_param('show_align', $show_align);
	if ($show_align)
		$kwic_engine->set_display_param('alignment_att_to_show', $alignment_att_to_show);


	/* what number does the concordance start and end at? 
	 *     conc_  = numbers that are shown
	 *     batch_ = numbers for CQP, which are one less */
	$conc_start = (($page_no - 1) * $per_page) + 1; 
	$conc_end   = min( [$conc_start + $per_page - 1, $n_of_solutions_final] );
	$kwic_engine->set_line_numbering($conc_start);    // CHECK I think this is right, but might it need to be $conc_start - 1 ???? 

	$batch_start = $conc_start - 1;
	$batch_end   = $conc_end   - 1;

	/* get an array containing the lines of the query to show this time */
	$kwic = $cqp->execute("cat $qname $batch_start $batch_end");

		//--> poss feature enhancement: get a dump of the query in order to know if there is a target/keyword ... ansd if there is apply a special css class to them when printing?

	/* n = number of concordances we have to display in this run of the script (may be less than $per_page) */
	$n_conc_lines = count($kwic);
	/* NOTE: if we have requested parallel corpus display, $n is 2 x the number of conc lines, and every other array entry is an align-line! */

	?>

</table>

<?php

if ('categorise' == $program)
{
	echo '<form id="mainCategoriseInputForm" class="autoSubmit" action="savequery-act.php" method="get"></form>'
		, '<input form="mainCategoriseInputForm" type="hidden" id="mainCategoriseQid" name="qid" value="' , int_to_base_36($qid) , '">'
		, ($show_align ? '<input form="mainCategoriseInputForm" type="hidden" name="showAlign" value="' . $alignment_att_to_show . '">' : '')
		, '<input form="mainCategoriseInputForm" type="hidden" name="pageNo" value="' , $page_no , '">'
		, "\n"
		;
	/* and note, in this case we will need info on categories for the drop-down controls */
	$map_of_categories  = list_catquery_categories_as_desc_map($qid);
	$category_table = get_catquery_categorisation_table($qid, $conc_start, $conc_start+($show_align?$n_conc_lines/2:$n_conc_lines)-1);
}




/* ====================== *
 * MAIN CONCORDANCE TABLE *
 * ====================== */


/* 4 printable variables for the conc table header */
$print_conc_start = number_format($conc_start);
$print_conc_end   = number_format($conc_end);
$print_page_no    = number_format($page_no);
$print_n_of_pages = number_format($n_of_pages);

?>
<table class="layout-frame fullwidth">
	<tr>
		<th class="layout-strong">No</th>
		<th class="layout-strong">Text</th>
		<th class="layout-strong"<?php echo $view_mode == 'kwic' ? ' colspan="3"' : ''; ?>>
			<?php echo "Solution $print_conc_start to $print_conc_end &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Page $print_page_no / $print_n_of_pages\n"; ?>
		</th>
		<?php echo 'categorise' == $program ? '<th class="layout-strong">Category</th>' : '', "\n"; ?>
	</tr>
	<?php
	
	/* --------------------------- *
	 * concordance line print loop *
	 * --------------------------- */
	
// 	/* display parameter hash for print_concordance_line() */
// 	$display_params = array(
// 		'qid'                 => $qid,
// 		'view_mode'           => $view_mode,
// 		'highlight_show_tag'  => $highlight_show_tag,
// 		'line_number'         => $conc_start-1, 
// 		'show_align'          => $show_align,
		

// 	);
// 	if ($show_align)
// 		$display_params['alignment_att_to_show'] = $alignment_att_to_show;
	
// 	$use_highlighting = !empty($highlight_positions_array);
	
	for ( $i = 0 ; $i < $n_conc_lines ; $i++ )
	{
// 		/* set the display parameters specific to this iteration of the loop */
// 		$display_params['line_number'] = ($show_align ? $conc_start + ($i / 2) : $conc_start + $i);
// 		if ($use_highlighting)
// 			$display_params['highlight_position'] = (int)$highlight_positions_array[$display_params['line_number'] - 1] ;
		
		/*            AND NOW  ....  the sharp end!           */
// 		$line = print_concordance_line($kwic[$i], $display_params);
		$line = print_concordance_line($kwic_engine, $kwic[$i], ($show_align ? $kwic[++$i] : null) );
		/* NOTE the extra increment to $i if $show_align, because in this case the array contains twice as many lines (see above) */

		
		echo "\n\t<tr>\n\t\t", $line;
		if ('categorise' == $program)
// 			echo print_categorise_cell($display_params, $map_of_categories, $category_table);
			echo print_categorise_cell($kwic_engine, $map_of_categories, $category_table);
		echo "\n\t</tr>\n";
		
		/* print an extra row iff we have parallel corpus data from an alignment attribute */
		if ($show_align)
// 			echo "\n\t<tr>", print_aligned_line($kwic[++$i], $display_params), "</tr>\n";
			echo "\n\t<tr>", $kwic_engine->fetch_subkwic(KwicLineManager::ALIGNED), "</tr>\n";

	}
	/* end of concordance line print loop */

	/* the categorise control row */
	if ('categorise' == $program)
		echo print_categorise_control($view_mode);

	?>

</table>

<?php

// //now done:  make the categorise form have an id that all its inputs refer to. Cos having a /form here is just shonky. 
// // maybe id=catForm ? cos there will only ever be one per page. 
// if ('categorise' == $program)
// 	echo "</form>\n";

/* show the control row again at the bottom if there are more than 15 lines on screen (but NOT in "categorise" mode) ) */
// TODO, get rid, if we can freeze the page-top part...
if ('categorise' != $program && $n_of_solutions_final > 15 && $per_page > 15)
	echo "\n<table class=\"layout-frame fullwidth\">\n"
 		, str_replace('id="concordanceMainDropdown"', 'id="concordanceLowerDropdown"', $control_row)
 		, "\n</table>\n"
 		;

/* print out the hidden forms for the menu */

do_conc_popup_colloc_control($cache_record);

do_conc_popup_thin_control($cache_record);

do_conc_popup_modify_bounds_control($cache_record);


/* Based on the program, vary the helplink. */

if ('sort' == $program || 'categorise' == $program)
	$helplink = $program;
else
	$helplink = 'concordance';


echo print_html_footer($helplink);

disconnect_browser_and_continue();


/* clear out old stuff from the query cache (left till here to increase speed for user) */
delete_cache_overflow();

/* and update the last restrictions (ditto) */
$qscope->commit_last_restriction();

cqpweb_shutdown_environment();


/* and we're done! */
