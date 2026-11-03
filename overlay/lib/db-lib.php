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
 * "Datablobs" are SQL tables created on the fly that store some subset of 
 * corpus data from a concordance result (extracted via query tabulation). 
 * 
 * Queries using the datablobs are then used to implement various facuilties
 * (sort, distribution, collocation, postprocesses ...)
 * 
 * This file contains functions for creation and management of these datablobs.
 */


/**
 * Object containing variables specifying a datablob type.
 * 
 * Each type also enfolds the "extra" variables that need to be passed to the database
 * creation function - to avoid that function having to make use of global variables.
 * 
 * SETUP: call new DbType() with the first argument being the appropriate DB_TYPE constant.
 * 
 * If there are additional needed arguments, then add those after that. The only case that
 * currently exists is when the type is COLLOC. In that case, the call is:
 * 
 * $t = new DbType(DB_TYPE_COLLOC, $colloc_atts, $colloc_range);
 * 
 * where $colloc_atts is string containing a '~'-delimited list of p-attributes 
 * (annotation handles).
 */
class DbType
{
	/** Contains one of the type constants: DB_TYPE_SORT and friends. */
	public $type;
	
	/* Specs only used for colloc */
	
	public $colloc_atts  = null;
	public $colloc_range = null;
	
	/**
	 * An array of valid type constants.
	 */
	private static $valid_types = [ DB_TYPE_DIST, DB_TYPE_COLLOC, DB_TYPE_SORT, DB_TYPE_CATQUERY, DB_TYPE_PARLINK ];
	
	/**
	 * A map of printable names for the type constants.
	 */
	const NAME = [
			DB_TYPE_DIST     => 'Distribution', 
			DB_TYPE_COLLOC   => 'Collocation', 
			DB_TYPE_SORT     => 'Sort', 
			DB_TYPE_CATQUERY => 'Categorization'
			// FIXME parlink 
	];
	
	/**
	 * A map of handle fragments for the type constants. (Previously used in the database. But, no longer.) 
	 */
	const HANDLE_FRAG = [
			DB_TYPE_DIST     => 'dist', 
			DB_TYPE_COLLOC   => 'colloc', 
			DB_TYPE_SORT     => 'sort', 
			DB_TYPE_CATQUERY => 'catquery'
			// FIXME parlink 
	];
	
	
	/**
	 * Constructor. 
	 * 
	 * Parameters after the first are variable; see documentation of the class.
	 * 
	 * @param int  $type   One of the type constants, e.g. DB_TYPE_SORT.
	 */
	public function __construct(int $type) 
	{
		switch ($this->type = $type)
		{
		case DB_TYPE_DIST:
			break;
		
		case DB_TYPE_COLLOC:
			/* PHP allows use of "..." with variable arguments, but 
			 * that would actually be less clear than the current code. */
			if (3 != func_num_args())
				exiterror("Invalid DB type setup for collocation: correct arguments not supplied.");
			
			/* the attributes are sorted here, so comparison with SQL strings always works; not necessary to sort the string anywhere else. */
			$list = explode('~', (string)func_get_arg(1));
			/* if we got passed a list with "word" in it, get rid of it, that's wrong */
			if ( false !== ($key = array_search('word', $list, true)) )
				unset($list[$key]);
			sort($list);
			$this->colloc_atts  = implode('~', $list);
			/* note this could be empty - that means, only do "word". */ 
			
			$this->colloc_range = (int)func_get_arg(2);
// TODO need control on this to avoid possibility of a number like 10000 overloading everything.
			break;
		
		case DB_TYPE_SORT:
			break;
			
		case DB_TYPE_CATQUERY:
			break;
			
		case DB_TYPE_PARLINK:
			break;
			
		default:
			exiterror("Code was reached that should never be reached! (In DbType)");
		}
		/* If, at any point, the other database types come to need extra info, 
		   add code in the switch above to set up the appropriate variables. */
	}
	
	/**
	 * Asserts that a given variable is, or type-juggles to, a valid DB_TYPE integer.
	 * Exits the program if not.
	 * 
	 * @param int    $var             Variable to check.
	 * @param string $assert_message  Optional: error message to use if the assertion fails.
	 */
	public static function assert(int $var, string $assert_message = '') : void
	{
		if (empty($assert_message))
			$assert_message = 'DbType: assertion of valid type failed.';
		if (!self::is_valid($var))
			exiterror($assert_message);
	}
	
	/**
	 * Checks whether a given value is, or type-juggles to, a valid DB_TYPE integer.
	 * 
	 * @param  int $var  Variable to check.
	 * @return bool      True if it's valid, false if not.
	 */	
	public static function is_valid(int $var) : bool
	{
		return in_array($var, self::$valid_types); // FIXME use key check in const array instead.
	}
}


function compose_db_tablename(int $db_id) : string 
{
	return TABLE_BASENAME_DB .  '_' . int_to_base_36($db_id);
}


function string_is_db_tablename(string $table) : bool
{
	return 0 < preg_match('/^' . TABLE_BASENAME_DB .  '_' . BASE36_DIGIT_REGEX . '+$/', $table);
}


/** 
 * Creates a db for the named query of the specified type & returns its name.
 * 
 * Note that although LOTS of the parameters come from a query record, we don't pass
 * in a QueryRecord object: see cache-lib.php for why (it's to support certain patterns
 * of postprocessed-query creation).
 * 
 * @param  DbType $db_type      Type of datablob to create, as a specifier object.
 * @param  int    $qid          ID of the cached query from which to create the DB.
 * @param  string $cqp_query    The cached query's actual CQP info (as in the query record)
 * @param  string $query_scope  The cached query's original query scope, serialised (as in the query record).
 * @param  string $postprocess  The cached query's postprocess string (as in the query record).
 * @return int                  The datablob's integer ID (i.e. the id field from the saved_dbs table).
 */
function create_db(DbType $db_type, int $qid, string $cqp_query, string $query_scope, string $postprocess) : int
{
	global $Config;
	global $User;
	global $Corpus;
	
	$cqp = get_global_cqp();
	$cqp->execute("set Context 0");
	$qname = cqpname_from_qid($qid);
	
	/* create a saved_dbs record. */
	$sql = "INSERT INTO saved_dbs (
			type,
			user,
			cache_time,
			cqp_query,
			query_scope,
			postprocess,
			colloc_atts,
			colloc_range,
			" . /*sort_position,*/ "
			corpus_id
		) VALUES (
			{$db_type->type},
			'{$User->username}',
			" . time() . ",
			'" . escape_sql($cqp_query)   . "',
			'" . escape_sql($query_scope) . "',
			'" . escape_sql($postprocess) . "',
			'" . ($db_type->type == DB_TYPE_COLLOC ? escape_sql($db_type->colloc_atts) : '') . "',
			"  . ($db_type->type == DB_TYPE_COLLOC ? $db_type->colloc_range : '0') . ",
			"  . /*($db_type->type == DB_TYPE_SORT ? $sort_position : '0') . ", */ "
			{$Corpus->id}
		)";
	/* note: sort position doesn't currently get used, so I have commented it out: sort databases are in corpus order */
	
	do_sql_query($sql);
	$db_id = get_sql_insert_id();
	$tbl = compose_db_tablename($db_id);
	
	/* register this script as working to create a DB, after checking there is room for it */
	if ( !check_sql_max_processes(DbType::HANDLE_FRAG[$db_type->type]) )
	{
		/* cqpweb-new-ui: remove the record just made, so that the automatic retry builds the table afresh */
		do_sql_query("delete from saved_dbs where id = $db_id");
		exiterror_toomanydbprocesses(DbType::HANDLE_FRAG[$db_type->type]);
	}
	
	register_sql_process($tbl, DbType::HANDLE_FRAG[$db_type->type]);
	
	/* delete old dbs if they are taking up too much space */
	delete_db_overflow();
	
	/* get this user's distribution db size limit from their username details */
	$table_max = $User->max_dbsize;	
// TODO replace with a call to the $User object that interrogates privileges.
// 	$User->max_dbsize()   // or similar    // but don't just use the raw database variable!
	
	$num_of_rows = $cqp->querysize($qname);
	
	if ( DB_TYPE_COLLOC == $db_type->type || DB_TYPE_PARLINK == $db_type->type )
		$num_of_rows *= $Config->colloc_db_premium;   /// TODO aaagh. Instead, [pass the $type to USer method. And have a separate func to clcualte it. 
	
	if ($num_of_rows > $table_max)
	{
		unregister_sql_process();
		delete_db($db_id);
		$table_max_print = number_format($table_max);
		exiterror("The action you have requested uses up a lot of diskspace.\n"
			. "Your limit is currently set to $table_max_print instances.\n"
			. "Please contact your system administrator if you need access to the information you requested."
			);
	}
	
	/* name for a file containing table with result of tabulation command */
	$tabfile = "{$Config->dir->cache}/db-tab-$db_id.tbl";
	/* name for a file containing the awk script */
	$awkfile = "{$Config->dir->cache}/db-awk-$db_id.awk";
	
	/* get the tabulate, awk, and create table commands for this type of database */
	$commands = db_commands($db_id, $db_type, $qid);
	
	if (!empty($commands['awk']))
	{
		/* if an awk script to intervene between cqp and sql input has been returned,
		 * create an awk script file ... */
		file_put_contents($awkfile, $commands['awk']);
		$tabulate_dest = "\"| {$Config->path_to_utils}awk -f '$awkfile' > '$tabfile'\"";
	}
	else
		$tabulate_dest = "'$tabfile'";
	
	/* create the empty table */
	do_sql_query($commands['create']);
	
	/* create the tabulation : either using a pipe as per above, or a filtering closure. */
	if (!is_callable($commands['filter_func']))
		$cqp->execute("{$commands['tabulate']} > $tabulate_dest");
	else
	{
		$cqp->raw_execute($commands['tabulate']);
		$dst = fopen($tabfile, "w");
		while ($l = $cqp->raw_read())
			fputs($dst, $commands['filter_func']($l));
		fclose($dst);
	}
	
	
	/* We need to check if the CorpusCharset is other than ASCII/UTF8. 
	 * If it is, we need to call the library function that runs over it with iconv. */
	if ('utf8' != ($corpus_charset = $cqp->get_corpus_charset()))
	{
		$utf8_filename = $tabfile .'.utf8.tmp';
		change_file_encoding($tabfile, 
		                     $utf8_filename, 
		                     CQP::translate_corpus_charset_to_iconv($corpus_charset), 
		                     CQP::translate_corpus_charset_to_iconv('utf8') . '//TRANSLIT'
		                     );
		unlink($tabfile);
		rename($utf8_filename, $tabfile);
		/* so now, either way, we need to work further on $tabfile. */
	}
	
	do_sql_infile_query($tbl, $tabfile, true);
	
	/* and delete the file from which the table was created, plus the awk-script if there was one */
	if (is_file($tabfile))
		unlink($tabfile);
	if (is_file($awkfile))
		unlink($awkfile);
	
	/* now that the SQL table exists, we can record the size. */
	do_sql_query("update saved_dbs set db_size = " . calculate_db_size($db_id) . " where `id` = $db_id");
	
	unregister_sql_process();
	
	return $db_id;
}


/**
 * Get three commands : one for CQP (a tabulate command); one for an optional awk pipe to filter the tabulate output on 
 * its way to SQL; and one for creation of the SQL table that will receive the data.
 * 
 * To avoid being dependent on Awk, it's possible to supply a PHP closure to do the work of the Awk pipe instead. 
 * 
 * @param  int     $db_id    Integer ID of the database's record in saved_dbs.
 * @param  DbType  $db_type  DB type as defined by an object w/ additional variables if necessary.
 * @param  int     $qid      Query from which the DB is to be created.
 * @return array             Associative array with 3 entries. Keys are 'tabulate', 'awk' OR 'filter_func', and 'create'; values are the 
 *                           values are the commands needed for DB creation, or false if absent (e.g. there is not always an awk filter).
 */
function db_commands(int $db_id, DbType $db_type, int $qid) : array
{
	global $Corpus;
	global $Config;
	
	$qname = cqpname_from_qid($qid);
	
	/* since the awk filter is not used for all types, set these initially to false */
	$awk_script      = false;
	$nonawk_callback = false;
	
	/* table name */
	$tbl = compose_db_tablename($db_id);
	
	/* get the types we need for different kinds of text field */
	$sql_types = get_sql_type_definitions();
	
	/* collation for main language-data fields ('word') */
	$word_collate = $Corpus->sql_collation_for_word;
	/* and for annotations */
	$annot_collate = [];
	foreach(get_all_annotation_info($Corpus->id) as $a)
		$annot_collate[$a->handle] = get_best_sql_collation($a->case_sensitive, $a->accent_sensitive);
	
	switch($db_type->type)
	{
	case DB_TYPE_DIST:
		
		/* do we need to add columns for XML IDLINK? */
		$idlink_field_handles = array();
		foreach(get_all_xml_info($Corpus->handle) as $xml)
			if (METADATA_TYPE_IDLINK == $xml->datatype)
				$idlink_field_handles[] = $xml->handle;
		
		$tabulate_command = "tabulate $qname match text_id";
		foreach($idlink_field_handles as $if)
			$tabulate_command .= ", match $if";
		$tabulate_command .= ', match, matchend';
		
		$extra_sql_fields = $extra_sql_keys = '';
		foreach($idlink_field_handles as $if)
		{
			$extra_sql_fields .= "`$if` {$sql_types['type_handle_key']->item_id}, ";
			$extra_sql_keys   .= "key(`$if`), ";
		}
		
		/* WHAT REFNUMBER MEANS HERE:
		 * Refers to a specific hit in the dist table, numbered in order of intake from CQP. */
		$create_statement = "CREATE TABLE `$tbl` (
				`text_id`       {$sql_types['type_handle_key']->item_id},
				$extra_sql_fields
				`cpos_begin`    {$sql_types['type_n_cpos_bytes']},
				`cpos_end`      {$sql_types['type_n_cpos_bytes']},
				`refnumber`     {$sql_types['type_primary_key_id_big']},
				
				$extra_sql_keys
				primary key(`refnumber`),
				key(`text_id`) 
			) ";
			/* 
			 * note the use of a binary collation for distribution DBs, since
			 * they always contain handle IDs, not word or tag material.
			 */
		break;
		
		
	case DB_TYPE_COLLOC:
		
		$att_array = ( empty($db_type->colloc_atts) ? array() : explode('~', $db_type->colloc_atts) );
		/* empty array so that foreach loops have valid reference, but don't loop. */
		
		/* create tabulate command */
		$tabulate_command = "tabulate $qname match, matchend, match text_id";
		
		for ($c = -$db_type->colloc_range ; $c <= -1 ; $c++)
		{
			$tabulate_command .= ", match[$c] word";
			foreach ($att_array as $att)
				$tabulate_command .= ", match[$c] $att";
		}
		for ($c = 1 ; $c <= $db_type->colloc_range ; $c++)
		{
			$tabulate_command .= ", matchend[$c] word";
			foreach ($att_array as $att)
				$tabulate_command .= ", matchend[$c] $att";
		}
		
		if ($Config->use_unix_tools)
		{
			/*
			 * Convert one line of tabulate output to multiple lines of SQL input: awk pipe
			 */
			/* create awk field variables */
			$awkvar_match    = '$1';
			$awkvar_matchend = '$2';
			$awkvar_text_id  = '$3';
			/* the next field after the pre-sets above: $f increments after every use */
			$f = 4;
			
			/* and now use them to write the awk script that converts one row to many */
			$awk_script = 'BEGIN{ OFS = FS = "\t" }' . "\n";
			
			for ($i = -$db_type->colloc_range ; $i <= $db_type->colloc_range ; $i++)
			{
				/* skip 0'th position, because there is no data in the tabulate output for $i == 0  */
				if ($i == 0)
					continue;
				/* before the output code: add the condition that prevents out-of-bounds tokens being included in the DB..... */
				$awk_script .= "($awkvar_match + $i >= 0 && $awkvar_matchend + $i < {$Corpus->size_tokens}) ";
				/* now, the actual command to print the sql input fields:
				 *                      text_id          cpos_begin     cpos_end          refno dist word */
				$awk_script .= "{ print $awkvar_text_id, $awkvar_match, $awkvar_matchend, NR-1, $i,  \$$f";
				$f++;       /* increment $f after each field is set */
				foreach($att_array as $att)
				{
					$awk_script .= ", \$$f";
					$f++;
				}
				$awk_script .= " }\n";
			}
		}
		else
		{
			/*
			 * Convert one line of tabulate output to multiple lines of SQL input: callback closure
			 */
			$nonawk_callback = function ($line) use ($db_type, $att_array, $word_collate)
			{
				static $n_inputs = 0;

				global $Corpus;
				
				$tabvals = explode("\t", rtrim($line, "\r\n"));
				
				/* the first three things in for the sql input are text_id, match, match_end; 
				 * then comes the refnum, which is how many input lines have been processed 
				 * (starting 0 = 1st match in query). TO WIT:
				 *            text_id   bpos     epos     refnumber */
				list($cpos_b, $cpos_e, $text_id) = $tabvals;
				$line_base = "$text_id\t$cpos_b\t$cpos_e\t$n_inputs";
				$tv_ix = 3;   /* index into the tabvals, tracking 4th col onwards */
				
				$block = "";
				
				/* for each position in the span, add the distance, then each attribute value from the tabulation at that posiiton */
				for ($dist = -$db_type->colloc_range ; $dist <= $db_type->colloc_range ; $dist++)
				{
					/* don't attempt to add to the block for dist = 0 as the tab output doesn't contain the node. */
					if (0 == $dist)
						continue;
					/* out-of-bounds check: don't add lines for cpos that would be outside [0, $corpus_size] */
					if ( 0 > ($dist + $cpos_b) || $Corpus->size_tokens <= ($dist + $cpos_e) )
					{
						$tv_ix += (1 + count($att_array)); /* skip token column and any tag columns for this dist. */ 
						continue;
					}
// TODO this si where we'd check wihtin $some_xml (eg s) presuambly if we're going to!   see how bncweb did it?

					/* add distance after the base fields ; then add "word", which we always have */ 
					$block .= $line_base . "\t" . $dist . "\t" . $tabvals[$tv_ix++];
					/* now copy one column per extra p-attribute */
					for ($j = 0, $n = count($att_array) ; $j < $n ; $j++)
						$block .= "\t" . $tabvals[$tv_ix++];
					
					/* this line of output is done, so we can add a newline; on Win, will be "\r\n" */
					$block .= PHP_EOL;
				}
				
				return $block;
			};
		}
		
		$len = $Config->colloc_max_comparison_length; /* length of the DB's attribute fields. */
		
		/* WHAT REFNUMBER MEANS HERE:
		 * Refers to a specific hit in the query (line no. in tabfile) - but this means it is NOT unique within this table. Ergo not primary. */
		$create_statement = <<<END_SQL
			CREATE TABLE `$tbl` (
				`text_id`       {$sql_types['type_handle_key']->item_id},
				`cpos_begin`    {$sql_types['type_n_cpos_bytes']},
				`cpos_end`      {$sql_types['type_n_cpos_bytes']},
				`refnumber`     {$sql_types['type_n_cpos_bytes']},
				`dist`          smallint NOT NULL,                                         /*not really any reason why this isn't `distance` */
				`word`          varchar($len) CHARSET utf8mb4 COLLATE $word_collate NOT NULL,
		END_SQL;
		/* add a field for each positional attribute */
		foreach ($att_array as $att)
			$create_statement .= "`$att` varchar($len) collate {$annot_collate[$att]} NOT NULL, ";
		$create_statement .= "\n key (`refnumber`) \n)";
//TODO maybe unique key refnumber dist?? Or, a spearate key for dist? could sped things up. 
// Also refnumber shouldn'#t really be used for a non-key!!
		
		break;
		
		
	case DB_TYPE_SORT:
		
		$len = $Config->sort_max_comparison_length;
		$multi_len = 2048; /* node can contain many tokens; guesstimate! we're using varchar so waste of disk space should not be major. */
		
		$type_worddata       = "varchar($len)       COLLATE $word_collate NOT NULL";
		$type_worddata_multi = "varchar($multi_len) COLLATE $word_collate NOT NULL";
		
		$att = $Corpus->primary_annotation ?? null;
		if (!empty($att))
		{
			$type_tagdata        = "varchar($len)       COLLATE {$annot_collate[$att]} NOT NULL";
			$type_tagdata_multi  = "varchar($multi_len) COLLATE {$annot_collate[$att]} NOT NULL";
		}
		/* the above makes sure the attribute collations propagate. */
		
		$tabulate_command = "tabulate $qname "
			/* wordform : -5 to -1, then 1 to 5 */
			. "match[-5]   word, match[-4]   word, match[-3]   word, match[-2]   word, match[-1]   word, "
			. "matchend[1] word, matchend[2] word, matchend[3] word, matchend[4] word, matchend[5] word, "
			/* wordform : all in the node */
			. "match .. matchend word, "
				
			. (empty($att) ? '' : 
					/* primary tag : -5 to -1, then 1 to 5 */
					  "match[-5]   $att, match[-4]   $att, match[-3]   $att, match[-2]   $att, match[-1]   $att, "
					. "matchend[1] $att, matchend[2] $att, matchend[3] $att, matchend[4] $att, matchend[5] $att, "
					/* primary tag : all in the node */ 
					. "match .. matchend $att, "
				)
			
			/* tracking fields */
			. "match text_id, match, matchend"
			;
		
		$att_fields = empty($att) ? '' : <<<END_SQL
				tagbefore5 $type_tagdata,
				tagbefore4 $type_tagdata,
				tagbefore3 $type_tagdata,
				tagbefore2 $type_tagdata,
				tagbefore1 $type_tagdata,
				tagafter1  $type_tagdata,
				tagafter2  $type_tagdata,
				tagafter3  $type_tagdata,
				tagafter4  $type_tagdata,
				tagafter5  $type_tagdata,
				tagnode    $type_tagdata_multi, 
		END_SQL;
		
		/* WHAT REFNUMBER MEANS HERE : a specific hit in the underlying concordance. */
		$create_statement = <<<END_SQL
			CREATE TABLE `$tbl` (
				before5 $type_worddata,
				before4 $type_worddata,
				before3 $type_worddata,
				before2 $type_worddata,
				before1 $type_worddata,
				after1  $type_worddata,
				after2  $type_worddata,
				after3  $type_worddata,
				after4  $type_worddata,
				after5  $type_worddata,
				node    $type_worddata_multi,
				$att_fields
				`text_id`       {$sql_types['type_handle_key']->item_id},
				`cpos_begin`    {$sql_types['type_n_cpos_bytes']},
				`cpos_end`      {$sql_types['type_n_cpos_bytes']},
				`refnumber`     {$sql_types['type_primary_key_id_big']},
				primary key(`refnumber`)
			)
		END_SQL;
		
		break;
		
		
	case DB_TYPE_CATQUERY:
		
		$tabulate_command = "tabulate $qname match, matchend";
		
		/* conc_ix = index for specific hit in the concordance being categorised. 
		 * Order of fields is weird for a reason!! */
		$create_statement = "CREATE TABLE `$tbl` (
				`cpos_begin`    {$sql_types['type_n_cpos_bytes']},
				`cpos_end`      {$sql_types['type_n_cpos_bytes']},
				`conc_ix`       {$sql_types['type_primary_key_id_big']},
				`category`      {$sql_types['type_symbolic_const']},
				primary key(`conc_ix`),
				key(`category`)
			) ";
		break;
		
		
	case DB_TYPE_PARLINK:
		// TODO fill in parlink cod ein v 3.3.2 or summat
		break;
		
		
	default:
		exiterror("db_commands was called with database type [{$db_type->type}]. This is not a recognised type of database!");
		break;
	}
	
	/* and, we're done! */
	return array(
		'tabulate'    => $tabulate_command, 
		'awk'         => $awk_script, 
		'filter_func' => $nonawk_callback,
		'create'      => $create_statement
	);
}


/** does nothing to the specified db, but refreshes its cache_time to = now */
function touch_db(int $db_id) : void
{
	do_sql_query("update saved_dbs set cache_time = " . time() . " where id = $db_id");
}


/** Returns total size in bytes of the SQL data/index structures of the specified DB. */
function calculate_db_size(int $db_id) : int
{
	$tbl = compose_db_tablename($db_id);
	
	/* this forces an update of the sizes of table available by show-table-status */
	do_sql_query("analyze table `$tbl`");
	
	$info = get_sql_object("SHOW TABLE STATUS LIKE '$tbl'");
	
	return $info->Data_length + $info->Index_length;
}


/** Repairs the size data for datablobs (for if they become fragmented) */
function repair_all_db_sizes() : void
{
	global $User;
	if (!$User->is_admin())
		return;
	
	$n_changes = 0;
	$unchanged = 0;
	
	foreach(list_sql_values_as_map("select id, db_size from saved_dbs", 'id', 'db_size') as $id => $size)
	{
		$tbl = compose_db_tablename($id);
		if (!($status = get_sql_object("SHOW TABLE STATUS LIKE '$tbl'")))
			continue;
		
		$realsize = $status->Data_length + $status->Index_length; 
		
		if ($realsize != $size)
		{
			squawk("Datablob $id/$tbl: updating from $size to $realsize...");
			do_sql_query("update saved_dbs set db_size = $realsize where `id`=$id");
			++$n_changes;
		}
		else
			++$unchanged;
	}
	
	squawk("Total of $n_changes DB sizes repaired. $unchanged were fine and have been left as-is.");
}


/**
 * Returns an object for the located DB's record in SQL,
 * or null if it could not be found.
 */
function get_db_info(int $db_id) : ?object
{
	return get_sql_object("SELECT * from saved_dbs where id = $db_id");
}


/**
 * Looks for a database identical to a set of parameters.
 * 
 * A database's "identity" is determined by the parameters involved in DB creation. 
 * That is, a DB is "identical" to a provided set of parameters iff:
 * 
 *  - they have the same DbType (including not only the type constant, but any ancillary variables,
 *    such as (for collocation) the attributes used and the range.
 *  - they are for the same fundamental query (CQP syntax)..
 *  - ... run in the same query scope.
 *  - ... in the same corpus (enforced automatically).
 *  - ... with the same sequence of postprocesses.
 * 
 * Returns an stdClass DB object for the located db's record,
 * or null if no record could be found.
 * 
 * @param  DbType $db_type      DB type specifier object including extra variables where necessary 
 *                              (colloc atts, sort position). As in create_db.
 * @param  string $cqp_query    As in create_db.
 * @param  string $query_scope  As in create_db.
 * @param  string $postprocess  As in create_db.
 * @return object               DB record object or null if not found.
 */
function get_db_info_by_parameters(DbType $db_type, string $cqp_query, string $query_scope, string $postprocess) : ?object
{
	global $Corpus;
	
	/* set up the options that are particular to certain types of db (currently only colloc) */
	
	$extra_conditions = '';
	
	switch ($db_type->type)
	{
	case DB_TYPE_DIST:
		break;
		
	case DB_TYPE_COLLOC:
		if ($db_type->colloc_range == 0)
			exiterror("The collocation range cannot be zero!");
		
		/* note, we can make use of a DB where the range is *greater* than we want, and *more* atts are available
		 * but this is tricky because tho the ordering is deterministic, there could be other atts in the middle 
		 * of our type. So, we do it thus: we look for strings that include our att string. This might miss some.
		 * E.g. if you seek "lem~semtag", then "lem~pos~semtag" would be overlooked. But it catches easy cases,
		 * e.g. accepting "class~lem~semtag" or "lem~semtag~zzz" for "lem~semtag". To do this, we look for the exact
		 * att string either initially, or after ~ ; finally, or before ~. */
		if (!empty($db_type->colloc_atts))
		{
			$rx = escape_sql("(^|~)$db_type->colloc_atts(~|$)"); 
			/* \b isn't available in MySQL < 8 or MariaDB < 10... */
			
			$extra_conditions .= " and colloc_atts REGEXP '$rx'";
		}
		/* else ... nothing. Because if no atts are specified, we are only looking for "word". And "word" isn't stated explicitly. */
		
		$extra_conditions .= " and colloc_range >= {$db_type->colloc_range}";
		
		break;
		
	case DB_TYPE_SORT:
		break;
		
	case DB_TYPE_PARLINK:
		break;
		
	default:
		exiterror("check_dblist_parameters was called with database type # {$db_type->type} . This is not a recognised type of database!");
	}
	
	/* now look for a db that matches all of that! */
	$sql = "SELECT * from saved_dbs 
				where   type        = {$db_type->type} 
					and corpus_id   = {$Corpus->id}
					and cqp_query   = '" . escape_sql($cqp_query)   . "'
					and query_scope = '" . escape_sql($query_scope) . "'
					and postprocess = '" . escape_sql($postprocess) . "'
					$extra_conditions
					limit 1";
	/* note: we ONLY match against colloc_atts / colloc_range when type is COLLOC; 
	 * and we NEVER match against sort_position, since it seems never to be used. */
	
	/* this returns null if nothing found */
	return get_sql_object($sql);
}


/**
 * Deletes a datablob from the system.
 */
function delete_db(int $db_id) : bool
{
	return drop_sql_table(compose_db_tablename($db_id))
			&&
			do_sql_query("delete from saved_dbs where id = $db_id");
}


/** 
 * Deletes all datablobs associated with a given corpus. 
 * @param int $corpus_id   The corpus to purge of DBs.
 */
function uncache_dbs_by_corpus(int $corpus_id) : void
{
	foreach(list_sql_values("select id from saved_dbs where corpus_id = $corpus_id") as $id)
		delete_db($id);
}


/** 
 * Purge the datablob cache of all the oldest items, to get the cache size under the limit again.
 * This function works ACROSS CORPORA && across types of db (except catquery). 
 */
function delete_db_overflow() : void
{
	global $Config;
	
	$attempts_per_loop = 20;
	$max_attempts_via_web = 50;
	$attempts = 0;
	
	while (true)
	{
		/* step one: how many bytes in size is the db cache RIGHT NOW? */
		$current_size = get_sql_value("select sum(`db_size`) from saved_dbs");
		if ($current_size <= $Config->db_cache_size_limit)
			break;
		
		/* step 2 : get a list of deletable tables;
		 * note that catquery dbs are excluded;
		 * because they are not re-generate-able,
		 * they contain user data. */
		$result = do_sql_query("select `id`, `db_size` from `saved_dbs` 
									where `type` != " . DB_TYPE_CATQUERY . "
									order by `cache_time` asc limit $attempts_per_loop" );
		if(1 > $result->num_rows)
			exiterror(array(
				"CRITICAL ERROR - DATABASE CACHE OVERLOAD!",
				"CQPweb tried to clear database cache space but failed!",
				"Please report this error to the system administrator."
			));
		
		/* step 3: delete till the db cache is small enough */
		while ( $current_size > $Config->db_cache_size_limit )
			if (!($db_to_delete = mysqli_fetch_object($result)))
				break;
			else if (delete_db($db_to_delete->id))
				$current_size -= $db_to_delete->db_size;
		/* next loop's check of $current_size will break if the above did the trick. */
		
		/* avoid infinite loop if not on command line */
		if ('cli' != $Config->output_interface)
			if (++$attempts > $max_attempts_via_web)
				break;
	}
}


/**
 * Deletes a specified datablob from SQL, unconditionally.
 * 
 * Note it only works on datablob tables!
 * 
 * If passed an array rather than a single table name, it will iterate across 
 * the array, deleting each specified table.
 * 
 * Designed for cleaning up datablob tables that have become unmoored from
 * the record in saved_dbs that would normally enable their deletion.
 * 
 * @param string|array $table  One or more SQL table names.
 */
function delete_stray_db_table(/* string|array */ $table) : void
{
	if (!is_array($table))
		$table = array($table);
	
	foreach ($table as $t)
		if (string_is_db_tablename($t))
			drop_sql_table($t);
}


/**
 * Deletes a db record from the cache table, unconditionally.
 * 
 * If passed an array rather than a single db name, it will iterate across 
 * the array, deleting each specified db record.
 * 
 * @param int|array $db_id  One or more IDs for db records.
 */
function delete_stray_db_entry(/* int|array */ $db_id) : void
{
	if (!is_array($db_id))
		$db_id = array($db_id);
	
	foreach ($db_id as $id)
		do_sql_query("delete from saved_dbs where `id` = " . (int)$id);
}


/**
 * Often, in results from database, a multi-token node will be represented thus:
 * 
 * word1 word2 word3_tag1 tag2 tag3
 * 
 * due to queries that concatenate (word, _, tag).
 * The same can end up embedded in postprocess strings.
 *  
 * This function changes that representation to the more easily rendered
 * 
 *       word1_tag1 word2_tag2 word3_tag3
 * 
 * as standard.
 */
function rearrange_db_word_and_tag(string $input_words, string $input_tags) : string
{
	$seq_w = explode(' ', $input_words);
	$seq_t = explode(' ', $input_tags);
	$joined = [];
	for($i = 0 ; isset($seq_w[$i]) ; $i++)
		$joined[$i] = $seq_w[$i] . '_' . $seq_t[$i];
	
	return implode(' ', $joined);
}


