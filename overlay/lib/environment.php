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
 * This file contains several things:
 * 
 * (1) Constant definitions for the system.
 * 
 * (2) The three global objects ($Config, $User, $Corpus) into which everything is stuffed.
 * 
 * (3) The environment startup and shutdown functions that need to be called to get things moving.
 * 
 */

	// TODO how to embed a const: $_ = function($v){return $v;};   echo " {$_(CACHE_STATUS_SAVED_BY_USER)} "



/*
 * ------------------------------- *
 * Constant definitions for CQPweb *
 * ------------------------------- *
 */


/*
 * version number of CQPweb 
 */
const CQPWEB_VERSION                        =  '3.3.19' ;


/*
 * FLAGS for cqpweb_startup_environment()
 */

const CQPWEB_STARTUP_NO_FLAGS               =  0  ;
const CQPWEB_STARTUP_DONT_CONNECT_CQP       =  1  ;
const CQPWEB_STARTUP_DONT_CONNECT_SQL       =  2  ;
const CQPWEB_STARTUP_CHECK_OWNER_OR_ADMIN   =  4  ;
const CQPWEB_STARTUP_CHECK_ADMIN_USER       =  8  ;
const CQPWEB_STARTUP_DONT_CHECK_USER        =  16 ;
const CQPWEB_STARTUP_ALLOW_ANONYMOUS_ACCESS =  32 ;   /* which is *not* the same thing as the previous! */
const CQPWEB_STARTUP_ENFORCE_CLI            =  64 ;


/*
 * Run location constants 
 */

const RUN_LOCATION_CORPUS                   =  1 ;
const RUN_LOCATION_MAINHOME                 =  2 ;
const RUN_LOCATION_ADM                      =  3 ;
const RUN_LOCATION_USR                      =  4 ;
const RUN_LOCATION_RSS                      =  5 ;
const RUN_LOCATION_USERCORPUS               =  6 ;

/**
 * Bases for relative URLs according to run location;
 * all values end in a / unless empty.
 */
const RELATIVE_URL_BASE = [ 
		RUN_LOCATION_CORPUS                 => '../',
		RUN_LOCATION_MAINHOME               => '',
		RUN_LOCATION_ADM                    => '../',
		RUN_LOCATION_USR                    => '../',
		RUN_LOCATION_RSS                    => '../',
		RUN_LOCATION_USERCORPUS             => '../../../../',
	];




/*
 * Metadata type constants (for texts and XML segments)
 */

const METADATA_TYPE_NONE                    =  0 ;
const METADATA_TYPE_CLASSIFICATION          =  1 ;
const METADATA_TYPE_FREETEXT                =  2 ;
const METADATA_TYPE_UNIQUE_ID               =  3 ;
const METADATA_TYPE_IDLINK                  =  4 ;
// TODO the next two aren't implemented yet.
const METADATA_TYPE_INT                     =  5 ;
const METADATA_TYPE_REAL                    =  6 ;

const METADATA_TYPE_DATE_ANY                =  128 ; /* b10000000 : the 128-bit = this is one of the DATE types (bitwise-and mask) */
const METADATA_TYPE_DATETIME_ANY            =  129 ; /* b10000001 : the 1-bit   = this is a DATETIME            (bitwise-and mask) */
const METADATA_TYPE_DATETIME_LO             =  129 ;
const METADATA_TYPE_DATETIME_HI             =  193 ;
const METADATA_TYPE_HISTDATE_ANY            =  130 ; /* b10000010 : the 2-bit   = this is a HISTDATE            (bitwise-and mask) */
const METADATA_TYPE_HISTDATE_LO             =  130 ;
const METADATA_TYPE_HISTDATE_HI             =  194 ;


const METADATA_TYPE_IS_ENABLED = [
		METADATA_TYPE_NONE                  => false,
		METADATA_TYPE_CLASSIFICATION        => true,
		METADATA_TYPE_FREETEXT              => true,
		METADATA_TYPE_UNIQUE_ID             => true,
		METADATA_TYPE_IDLINK                => true,
		METADATA_TYPE_INT                   => false,
		METADATA_TYPE_REAL                  => false,
		METADATA_TYPE_DATETIME_HI           => false,
		METADATA_TYPE_DATETIME_LO           => false,
		METADATA_TYPE_HISTDATE_HI           => false,
		METADATA_TYPE_HISTDATE_LO           => false,
	];


/** Printable descriptions of the meanings of the METADATA_TYPE constants. */
const DESCRIBE_METADATA_TYPE = [
		METADATA_TYPE_NONE                  => 'No data: XML element',
		METADATA_TYPE_CLASSIFICATION        => 'Classification',
		METADATA_TYPE_FREETEXT              => 'Free text',
		METADATA_TYPE_UNIQUE_ID             => 'Unique ID',
		METADATA_TYPE_IDLINK                => 'ID link',
		METADATA_TYPE_DATETIME_LO           => 'Date',
		METADATA_TYPE_DATETIME_HI           => 'Time',
		METADATA_TYPE_HISTDATE_LO           => 'Year',
		METADATA_TYPE_HISTDATE_HI           => 'Date (historical)',
	];

const DATETIME_REGEX = [
		METADATA_TYPE_DATETIME_LO           => '/^(\d{4}|\d{2})-?(\d\d+)-?(\d\d+)$/',
		METADATA_TYPE_DATETIME_HI           => '/^(\d{4}|\d{2})-?(\d\d+)-?(\d\d+)\s*(\d\d?)[\.:]?(\d\d?)[\.:]?(\d\d?)$/',
		METADATA_TYPE_HISTDATE_LO           => '/^([+\-]\d{4})$/',
		METADATA_TYPE_HISTDATE_HI           => '/^([+\-]\d{4})(\d\d)(\d\d)$/',
];
			

/*
 * User-database type constants
 */

const DB_TYPE_DIST                          =  1 ;
const DB_TYPE_COLLOC                        =  2 ;
const DB_TYPE_SORT                          =  3 ;
const DB_TYPE_CATQUERY                      =  4 ;
const DB_TYPE_PARLINK                       =  5 ;



/*
 * Query-postprocess type constants
 */

const POSTPROCESS_TYPE_COLL                 =  'coll';
const POSTPROCESS_TYPE_SORT                 =  'sort';
const POSTPROCESS_TYPE_THIN                 =  'thin';
const POSTPROCESS_TYPE_ITEM                 =  'item';
const POSTPROCESS_TYPE_DIST                 =  'dist';
const POSTPROCESS_TYPE_TEXT                 =  'text';
const POSTPROCESS_TYPE_RAND                 =  'rand';
const POSTPROCESS_TYPE_UNRAND               =  'unrand';
const POSTPROCESS_TYPE_CAT                  =  'cat';
const POSTPROCESS_TYPE_EXPAND               =  'expand';
const POSTPROCESS_TYPE_NARROW               =  'narrow';
const POSTPROCESS_TYPE_CUSTOM               =  'custom';
const POSTPROCESS_TYPE_OP                   =  'op';
const POSTPROCESS_TYPE_UPLOAD               =  'upload';
// pending
const POSTPROCESS_TYPE_XMLID                =  'xmlid'; //TODO POSTPROCESS_TYPE_XMLID for when we can thin by XML linked ID.  not in docs yet. Not implemented yet. 


/*
 * Collocation statistic constants
 */

const COLLSTAT_RANK_FREQ                    =  0 ;
const COLLSTAT_MI                           =  1 ;
const COLLSTAT_MI3                          =  2 ;
const COLLSTAT_ZSCORE                       =  3 ;
const COLLSTAT_TSCORE                       =  4 ;
/* const COLLSTAT_CHI_SQUARED                  =  5 ;  # because we don't allow chi squared for collocation */
const COLLSTAT_LOG_LIKELIHOOD               =  6 ;
const COLLSTAT_DICE                         =  7 ;
const COLLSTAT_LOG_RATIO                    =  8 ;
const COLLSTAT_LR_CONSERVATIVE              =  9 ;



/*
 * Keywords (and key tags) statistic constants
 */

const KEYSTAT_NONE_COMPARE                  =  0 ;
const KEYSTAT_LOGLIKELIHOOD                 =  1 ;
const KEYSTAT_LR_UNFILTERED                 =  2 ;
const KEYSTAT_LR_WITH_LL                    =  3 ;
const KEYSTAT_LR_WITH_CONFINT               =  4 ;
const KEYSTAT_LR_CONSERVATIVE               =  5 ;




/* 
 * plugin type constants 
 */

const PLUGIN_TYPE_UNKNOWN                   =  0   ;
const PLUGIN_TYPE_ANNOTATOR                 =  1   ;
const PLUGIN_TYPE_FORMATCHECKER             =  2   ;
const PLUGIN_TYPE_SCRIPTSWITCHER            =  4   ;
const PLUGIN_TYPE_CORPUSANALYSER            =  8   ;
const PLUGIN_TYPE_CORPUSINSTALLER           =  16  ;
const PLUGIN_TYPE_POSTPROCESSOR             =  32  ;
const PLUGIN_TYPE_QUERYANALYSER             =  64  ;
const PLUGIN_TYPE_QUERYDOWNLOADER           =  128 ;
const PLUGIN_TYPE_CEQLEXTENDER              =  256 ;

/* groups! */
const PLUGIN_TYPE_ALL_QUERY_ACTIONS         =  PLUGIN_TYPE_QUERYDOWNLOADER|PLUGIN_TYPE_QUERYANALYSER|PLUGIN_TYPE_POSTPROCESSOR;
const PLUGIN_TYPE_ACTIVATED_BY_CORPUS       =  PLUGIN_TYPE_CEQLEXTENDER|PLUGIN_TYPE_QUERYDOWNLOADER|PLUGIN_TYPE_QUERYANALYSER|PLUGIN_TYPE_POSTPROCESSOR;
const PLUGIN_TYPE_ANY                       =  1|2|4|8|16|32|64|128|256 ;

 
/*
 * user account state constants
 */

const USER_STATUS_UNVERIFIED                =  0 ;
const USER_STATUS_ACTIVE                    =  1 ;
const USER_STATUS_SUSPENDED                 =  2 ;
const USER_STATUS_PASSWORD_EXPIRED          =  3 ;

/** Printable descriptions of the meanings of the USER_STATUS constants. */
const DESCRIBE_USER_STATUS = [
		USER_STATUS_ACTIVE                  => 'Active',
		USER_STATUS_UNVERIFIED              => 'Unverified',
		USER_STATUS_SUSPENDED               => 'Inactive: account suspended',
		USER_STATUS_PASSWORD_EXPIRED        => 'Inactive: password expired',
	];


/*
 * colleague link constants
 */

const COLLEAGUATE_STATUS_UNDEFINED          =  0 ;
const COLLEAGUATE_STATUS_PENDING            =  1 ;
const COLLEAGUATE_STATUS_ACTIVE             =  2 ;
const COLLEAGUATE_STATUS_ANY                =  3 ;


/*
 * privilege types
 */

const PRIVILEGE_TYPE_NO_PRIVILEGE           =  0 ;	/* can be used to indicate absence of one or more privileges; not used in the DB except in error condition */
const PRIVILEGE_TYPE_CORPUS_RESTRICTED      =  1 ;
const PRIVILEGE_TYPE_CORPUS_NORMAL          =  2 ;
const PRIVILEGE_TYPE_CORPUS_FULL            =  3 ;
/* note that the above 4 definitions create a greater-than/less-than sequence. Intentionally so. */

const PRIVILEGE_TYPE_FREQLIST_CREATE        =  4 ;
const PRIVILEGE_TYPE_UPLOAD_FILE            =  5 ;

// TODO not properly sorted yet, still thinking about.......
// just one "DISK SPACE" to account for how much cache the user can use, which then applies to saved databases as well as saved queries?
// YES, but let it have scope over different cache types!!
// const PRIVILEGE_TYPE_DISK_FOR_SAVEDATA       6 ;

const PRIVILEGE_TYPE_CQP_BINARY_FILE        =  7 ;
const PRIVILEGE_TYPE_EXTRA_RUNTIME          =  8 ;

const PRIVILEGE_TYPE_DISK_FOR_UPLOADS       =  9 ;
const PRIVILEGE_TYPE_INSTALL_CORPUS         =  10 ;

//TODO not properly sorted yet
const PRIVILEGE_TYPE_DISK_FOR_CORPUS        =  11 ;

/** Printable descriptions of the meanings of the PRIVILEGE_TYPE constants. */
const DESCRIBE_PRIVILEGE_TYPE = [
		PRIVILEGE_TYPE_NO_PRIVILEGE         => '(none)',
		PRIVILEGE_TYPE_CORPUS_RESTRICTED    => 'Restricted access to corpus',
		PRIVILEGE_TYPE_CORPUS_NORMAL        => 'Normal access to corpus',
		PRIVILEGE_TYPE_CORPUS_FULL          => 'Full access to corpus',
		PRIVILEGE_TYPE_FREQLIST_CREATE      => 'Permission to build frequency list',
		PRIVILEGE_TYPE_UPLOAD_FILE          => 'Permission to upload file',
		PRIVILEGE_TYPE_CQP_BINARY_FILE      => 'Access to CQP binary files',
		PRIVILEGE_TYPE_EXTRA_RUNTIME        => 'Extension to CQPweb\'s runtime limit',
		PRIVILEGE_TYPE_DISK_FOR_UPLOADS     => 'Storage allocation in the upload area',
		PRIVILEGE_TYPE_INSTALL_CORPUS       => 'Permission to use corpus-installer plugin',
		PRIVILEGE_TYPE_DISK_FOR_CORPUS      => 'Storage allocation for user\'s corpora',
	];
	/* some functions define alternative mapper hashes for their own purposes, note! */


/*
 * corpus installer progress-status consts.
 */

const INSTALLER_STATUS_UNKNOWN              =  0 ;
const INSTALLER_STATUS_WAITING              =  1 ;
const INSTALLER_STATUS_TAGGING              =  2 ;
const INSTALLER_STATUS_ENCODING             =  3 ;
const INSTALLER_STATUS_INDEXING             =  4 ;
const INSTALLER_STATUS_SETUP                =  5 ;
const INSTALLER_STATUS_FREQLIST             =  6 ;
const INSTALLER_STATUS_DONE                 =  7 ;
const INSTALLER_STATUS_ABORTED              =  8 ;

/** Printable descriptions of the meanings of the INSTALLER_STATUS constants. */
const DESCRIBE_INSTALLER_STATUS = [ 
		INSTALLER_STATUS_UNKNOWN            => 'Unknown (??)',
		INSTALLER_STATUS_WAITING            => 'Waiting in job queue',
		INSTALLER_STATUS_TAGGING            => 'Generating input data',
		INSTALLER_STATUS_ENCODING           => 'Encoding corpus',
		INSTALLER_STATUS_INDEXING           => 'Indexing corpus',
		INSTALLER_STATUS_SETUP              => 'Finalizing setup',
		INSTALLER_STATUS_FREQLIST           => 'Building frequency lists',
		INSTALLER_STATUS_DONE               => 'Installation complete',
		INSTALLER_STATUS_ABORTED            => 'Aborted!',
	];


/* 
 * query-record save-status indicators 
 */

/** Signifies that a recorded query exists in cache but has not been saved. */
const CACHE_STATUS_UNSAVED                  =  0 ;

/** Signifies that a recorded query has been saved by a user. */
const CACHE_STATUS_SAVED_BY_USER            =  1 ;

/** Signifies that a recorded query has been saved as a "categorised" query. */
const CACHE_STATUS_CATEGORISED              =  2 ;


/*
 * handle-length constants
 */

// FIXME const increases needed before we got to 3.4
const HANDLE_MAX_CORPUS                     =  20 ; //TODO increase in 3.3.x>=1 and add - as viable handle char
const HANDLE_MAX_ANNOTATION                 =  20 ; //TODO increase in 3.3.x>=1 to 64 (column name in tables)
const HANDLE_MAX_XML                        =  64 ; //can't increase, may need to ebcome a column name.


const HANDLE_MAX_FIELD                      =  64 ;
const HANDLE_MAX_CATEGORY                   =  200 ;  // can't recall why categories are limited to 200.  (it's cos savenames were. whyever that was.) TODO increase to 255. 

const HANDLE_MAX_ITEM_ID                    =  255 ;


/* non-handle strings in UTF-8 can be up to 255, but a MariaDB/MySQL utf8mb4 index can only go up to 191 */

const NONHANDLE_MAX_USERNAME                =  64 ; //TODO increase in 3.3.x<1 to 255, with a 191 index ; use integer ids instead wherever poss.
// NOTE that groups still have to be handles, and are subjbec tot the max of 64.

/** Maximum length of database non-corpus strings that aren't handles;
 *  unlike the constants above, this is an N of UTF-8 characters, not bytes. */
const NONHANDLE_MAX_STRING                  =  255;

/** Maximum length of non-handle strings that need complete index;
 *  utf8mb4: 191 * 4 = 764, longest possible size of index = 767. */
const NONHANDLE_MAX_INDEX                   =  191;


/*
 * handle format error constants
 */

const HANDLE_BAD_NOTBAD                     =   0 ;
const HANDLE_BAD_TOOLONG                    =  -1 ;
const HANDLE_BAD_BADCHAR                    =  -2 ;
const HANDLE_BAD_EMPTY                      =  -3 ;



/*
 * Non-system SQL table roots
 */

const TABLE_BASENAME_DB                     =  '_dblb' ;
const TABLE_BASENAME_MATRIX                 =  '_fmtx' ;
const TABLE_BASENAME_TEXT_METADATA          =  '_txmd' ;
const TABLE_BASENAME_IDLINK_METADATA        =  '_idmd' ;
const TABLE_BASENAME_FREQTABLE_CORPUS       =  '_frqc' ;
const TABLE_BASENAME_FREQTABLE_SCOPE        =  '_frqs' ;
const TABLE_BASENAME_FREQCACHE_TEXT_IX      =  '_fcti' ;
const TABLE_BASENAME_TEMP                   =  '__tmp' ;

const DESCRIBE_TABLE_BASENAME = [ 
		TABLE_BASENAME_DB                   => 'Datablob from query result',
		TABLE_BASENAME_MATRIX               => 'Feature matrix',
		TABLE_BASENAME_TEXT_METADATA        => 'Corpus text metadata table',
		TABLE_BASENAME_IDLINK_METADATA      => 'ID-linked XML value metadata table',
		TABLE_BASENAME_FREQTABLE_CORPUS     => 'Frequency table component for whole corpus',
		TABLE_BASENAME_FREQTABLE_SCOPE      => 'Frequency table component for corpus subsection',
		TABLE_BASENAME_FREQCACHE_TEXT_IX    => 'Frequency cache text index',
		TABLE_BASENAME_TEMP                 => 'Temporary SQL table',
	];


/*
 * SQL system limitations (maybe increasable in future versions)
 */
const MAX_INDEXED_COLS_PER_SQL_TABLE = 64;



/*
 * HTML cache symbolic constants for the type of HTML chunk cached.
 */

const HTMLCACHE_TYPE_UNKNOWN                = 0;
/** HTML chunk: The user dropdown for the query history page. Takes no parameters. */
const HTMLCACHE_QHIST_CHOOSER               = 1;
/** HTML chunk: A row in the "View subcorpora" display for a subcorpus that takes a lot of calculation. NOT IMPLEMENTED YET. */
const HTMLCACHE_SUBCORPUS_ROW               = 2;
// others





/*
 * Query mode constants
 */

/** mapper hash from qmode strinjg constants to descriptions. */
const DESCRIBE_QUERY_MODE = [ 
		'cqp'                               => 'CQP syntax',
		'sq_nocase'                         => 'Simple query (ignore case)',
		'sq_case'                           => 'Simple query (case-sensitive)',
	];
/* keys of the array above aren't constants. maybe in future they will be. */



/*
 * CEQL-related
 */

 /* symbolic constants allowing the CQPweb terminology to be used rather than the strings that CEQL uses internally. 
  * Note, only functions that CALL the parser use these. Not the parser itself. */
const CEQL_WORD_ANNOTATION                  =  'word_attribute'       ;
const CEQL_PRIMARY_ANNOTATION               =  'pos_attribute'        ;
const CEQL_SECONDARY_ANNOTATION             =  'lemma_attribute'      ;
const CEQL_TERTIARY_ANNOTATION              =  'simple_pos_attribute' ;
const CEQL_TERTIARY_MAPPING_TABLE           =  'simple_pos'           ;
const CEQL_COMBO_ANNOTATION                 =  'combo_attribute'      ;

/* symbolic constants for the annotation-binding keys (equal to the names of the fields in corpus_info) */ 
const CEQL_BIND_PRIMARY                     = 'primary_annotation'    ;
const CEQL_BIND_SECONDARY                   = 'secondary_annotation'  ;
const CEQL_BIND_TERTIARY                    = 'tertiary_annotation'   ;
const CEQL_BIND_TERTIARY_TABLE              = 'tertiary_annotation_tablehandle';
const CEQL_BIND_COMBO                       = 'combo_annotation'      ;

const CEQL_AVAILABLE_BINDINGS = [
		CEQL_BIND_PRIMARY,
		CEQL_BIND_SECONDARY,
		CEQL_BIND_TERTIARY,
		CEQL_BIND_TERTIARY_TABLE,
		CEQL_BIND_COMBO,
	];


/*
 * misc constants
 */

/*
 * archive and/or compressed file types.
 * 4 types of compression are known - an even number represents each,
 * the odd number one more than each is the corresponding compressed tar type.
 *
 * As odd one out, ZIP is 256. (If added, RAR will be 255; etc.)
 */

const ARCHIVE_TYPE_UNKNOWN                  =  0 ;

const ARCHIVE_TYPE_ZIP                      =  256 ;

const ARCHIVE_TYPE_TAR                      =  1 ;

const ARCHIVE_TYPE_GZ                       =  2 ;
const ARCHIVE_TYPE_TAR_GZ                   =  3 ;  /* 1 | 2 */

const ARCHIVE_TYPE_BZ2                      =  4 ;
const ARCHIVE_TYPE_TAR_BZ2                  =  5 ;  /* 1 | 4 */

const ARCHIVE_TYPE_XZ                       =  6 ;
const ARCHIVE_TYPE_TAR_XZ                   =  7 ;  /* 1 | 6 */

const ARCHIVE_TYPE_7Z                       =  8 ;
const ARCHIVE_TYPE_TAR_7Z                   =  9 ;  /* 1 | 8 */

const ARCHIVE_FILE_EXT = [
		ARCHIVE_TYPE_ZIP     => 'zip',
		ARCHIVE_TYPE_TAR     => 'tar',
		ARCHIVE_TYPE_GZ      => 'gz',
		ARCHIVE_TYPE_TAR_GZ  => 'tar.gz',
		ARCHIVE_TYPE_BZ2     => 'bz2',
		ARCHIVE_TYPE_TAR_BZ2 => 'tar.bz2',
		ARCHIVE_TYPE_XZ      => 'xz',
		ARCHIVE_TYPE_TAR_XZ  => 'tar.xz',
		ARCHIVE_TYPE_7Z      => '7z',
		ARCHIVE_TYPE_TAR_7Z  => 'tar.7z',
	];

const ARCHIVE_PROG = [
		ARCHIVE_TYPE_ZIP     => 'zip',
		ARCHIVE_TYPE_TAR     => 'tar',
		ARCHIVE_TYPE_GZ      => 'gzip',
		ARCHIVE_TYPE_BZ2     => 'bzip2',
		ARCHIVE_TYPE_XZ      => 'xz',
		ARCHIVE_TYPE_7Z      => '7z',
];

const ARCHIVE_MIME_TYPE = [
		ARCHIVE_TYPE_ZIP     => 'application/zip',
		ARCHIVE_TYPE_TAR     => 'application/x-tar',
		ARCHIVE_TYPE_GZ      => 'application/gzip',
		ARCHIVE_TYPE_TAR_GZ  => 'application/gzip',
		ARCHIVE_TYPE_BZ2     => 'application/x-bzip2',
		ARCHIVE_TYPE_TAR_BZ2 => 'application/x-bzip2',
		ARCHIVE_TYPE_XZ      => 'application/x-xz',
		ARCHIVE_TYPE_TAR_XZ  => 'application/x-xz',
		ARCHIVE_TYPE_7Z      => 'application/x-7z-compressed',
		ARCHIVE_TYPE_TAR_7Z  => 'application/x-7z-compressed',
];




/* constants for physical/soft keyboard states (KBST); can be bitwise OR'd together. */
const KBST_BASE  =    0;
/* we skip 0-256, **just in case ** */
const KBST_SHIFT =  512;
const KBST_ALT   = 1024;
const KBST_ALTGR = 2048;
const KBST_META  = 4096;
const KBST_CTRL  = 8192;




/** The common date-time format string used around CQPweb. */
const CQPWEB_UI_DATE_FORMAT                 =  'Y-M-d H:i' ;


/** A string to be used as a Kwic delimiter (can thus be used with explode()) */ 
const CQP_INTERFACE_KWIC_DELIMITER          = '--%%%--';

/** CQPweb uses this as the token separator in CQP (ascii ENQ) */
const CQP_INTERFACE_TOK_SEP = "\x5";
// const CQP_INTERFACE_TOK_SEP = "555";

/** CQPweb uses this as the attribute separator in CQP (ascii ACK) */
const CQP_INTERFACE_ATT_SEP = "\x6";
// const CQP_INTERFACE_ATT_SEP = "666";

/** CQPweb uses this as the structure delimiter in CQP (ascii BEL) */
const CQP_INTERFACE_STRUC_DELIM = "\x7";
// const CQP_INTERFACE_STRUC_DELIM = "777";


/** Regular expression used in multiple places to interpret CQP concordance.
 *  Used with PREG_PATTERN_ORDER, it puts tokens in $m[4]; xml-tags-before in $m[1]; xml-tags-after in $m[5] */
const /*old_const_*/CQP_INTERFACE_WORD_REGEX              =  '|((<\S+?( [^>]*?)?>)*)([^ <]+)((</\S+?>)*) ?|' ;
// no longer used I TGHINK.
/* Note, this is prone to interference from literal < in the index. 
 * Also note that we need to allow for empty strings as annotations, hence [^>]* rather than [^>]+
 * Will be fixable when we have XML concordance output in CQP v 4.0 
 */

/** Regular expression used in multiple places to split apart words/annotations
 *  in CQP concordance format. First match = the word; second match = the annotation. */
const CQP_INTERFACE_EXTRACT_TAG_REGEX       =  '/\A(.*)\/([^\/]*)\z/' ;

/** Regular expression for extracting XML for conversion into a visualisation. */
const CQP_INTERFACE_XML_VIZ_REGEX           =  '~<(/|)(\w+)( (?:.*?)|)>~';

/** Regular expression for the libnee-break fallback for context display */
const CQP_INTERFACE_LINE_BREAKER_REGEX      = '/^([.?!\x{0964}]|\.\.\.)$/u';// TODO note that this misses final punc in many writing systems.

/** Regular expression that matches clean words only (per definition of cwb-scamn-corpuys */
const CQP_INTERFACE_CLEAN_WORD_REGEX        = '/^([\pL\pM]+|\pN+)(\p{Pd}([\pL\pM]+|\pN+))*$/u';

/** Magic number: string that should match against the first four bytes of a CQP query file. 
 *  NB. This is not the "original", pre-1995 format, but the "plus one" format introduced in 1995. */
const CQP_INTERFACE_FILE_MAGIC_NUMBER       =  "\x89\x46\x28\x02" ;


/** Regular expression FRAGMENT matching one single digit in base 36 */
const BASE36_DIGIT_REGEX                    =  '[A-Za-z0-9]';

/** Regular expression FRAGMENT defining a single byte that is allowed to occur in a handle. */
const CQPWEB_HANDLE_BYTE_REGEX              =  '[A-Za-z0-9_\-]' ;

/** Regular expression FRAGMENT defining a single byte that is allowed to occur as the first byte of a handle. */
const CQPWEB_HANDLE_INIT_BYTE_REGEX         =  '[A-Za-z0-9_]' ;
/* note that we allow initial digits, which the XML standard doesn't; they may be needed for sorting purposes */
// FIXME -- bug! this same RX, or equivs on the next couple of constants, is used for CWB IDs as well as handle IDs
// for categories etc. CWB reg DOESN'T allow a leading number, but we want to allow that for categories. 
// at the minute this will be handled by putting in a special function for handle_check_for_reg() to use if we know
// something is going to be fed to the reg parser. see general-lib for the func, admin-install for its use. 

/** Regular expression FRAGMENT defining a single byte that is NOT allowed to occur in a handle. */
const CQPWEB_NONHANDLE_BYTE_REGEX           =  '[^A-Za-z0-9_\-]' ;

/** Regular expression FRAGMENT (in capturing brackets) that catches a corpus (or attribute, or category) handle.
 *  Alolows the same set of strings as a CWB handle. */
const CQPWEB_HANDLE_STRING_REGEX            =  '([A-Za-z0-9_][A-Za-z0-9_\-]*)' ;

/** Regular expression FRAGMENT (in capturing brackets) that catches a possible text ID (or id elsewhere).
 *  Altered to match xml:id in v 3.3.12 (TODO: match IDs against this, instead of the handle rx).
 *  Note that unlike xml:id, CQPweb's IDs still have to be ASCII. */
const CQPWEB_ID_STRING_REGEX                =  '([A-Za-z_:][A-Za-z0-9_:\-.]*)' ;
// TODO maybe rethink the above, because this woould allow attributes to have names that wouldn't pass through cl_validate_id(), causing issues

/** Regular expression FRAGMENT matching one single byte that is valid in a username (NOT whitespace, straight quotes, open angle bracket). */
const CQPWEB_VALID_USERNAME_BYTE_REGEX      =  '[^\s<"\']' ;

/** Regular expression FRAGMENT matching one single byte that can't be used in a username (whitespace, straight quotes, open angle bracket). */
const CQPWEB_INVALID_USERNAME_BYTE_REGEX    =  '[\s<"\']' ;

/** String containing all the valid bytes in the cookie-token language */
const CQPWEB_COOKIE_TOKEN_LANGUAGE          =  'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_~|' ;

/** "reserved words" that can't be used for corpus ids.
 *  All reserved words are 3 lowercase letters; 
 *  any new ones we add will also be 3 lowercase letters */
const CQPWEB_RESERVED_SUBDIRS = [ 'adm', 'bin', 'css', 'doc', 'exe', 'jsc', 'lib', 'rss', 'usr' ]; 
/* nb: jsc stands for "javascript clientside";  any future "javascript serverside" will be 'jss' */





/* --------------------- *
 * Global object classes *
 * --------------------- */


/**
 * Declare variables for the sub-object that contains directory strings in CQPwebEnvConfig
 */
class CQPwebEnvConfigDirSpec
{
	public string $cache;
	public string $upload;
	public string $index;
	public string $registry;

	public function __construct(string $cache, string $upload, string $index, string $registry)
	{
		$this->cache    = $cache;
		$this->upload   = $upload;
		$this->index    = $index;
		$this->registry = $registry;	
	}
	// in PHP 8 we can do this instead of the above.
// 	public function __construct(
// 		public string $cache,
// 		public string $upload,
// 		public string $index,
// 		public string $registry,	
// 	) { }
}


/**
 * Class of which each run of CQPweb should only ever have ONE - it holds config settings as public variables
 * (sometimes hierarchically using other objects).
 * 
 * The instantiation should always be the global $Config object.
 * 
 * This class's constructor loads all the config settings from file.
 * 
 * Config settings in the database are NOT loaded by the constructor.
 */
class CQPwebEnvConfig
{
	/** The run location of this instance of CQPweb. */
	public int    $run_location;
	
	/** The output destination of this instance of CQPweb. Either 'cli' or 'browser' */
	public string $output_interface;
	
	/** 
	 * Object containing as members the config values for different directory paths;
	 * vars are : cache, upload, index, registry.
	 */ 
	public object $dir;

	/** pipe-delimited list of superuser usernames (to reduce hack risk, admin powers are only granted through here) */
	public string $superuser_username;
	// TODO - pipe is now a legal char in usernames. POss it shouldn't be. 
	// fix by making this an array of strings.

	/**
	 * Hash of keys to connection objects (mysqli/CQP).
	 * For people still running old PHP, former might be a mysql resource. 
	 */
	private array $slave_process_links = ['sql'=>null, 'cqp'=>null];
	
	/*
	 * SQL control variables: 4 compulsory, rest optional.
	 */
	public string $sql_user;
	public string $sql_password;
	public string $sql_schema;
	public string $sql_host;
	/* --------------------------------- */
	public bool   $sql_utf8_set_required;
	public int    $sql_big_process_limit;
	public bool   $sql_has_file_access;
	public bool   $sql_local_infile_disabled;

	/* these SQL control variables are not settable in config: they are deduced at startup. */
	public string $sql_LOAD_DATA_INFILE_command;
	public bool   $sql_infile_disabled;
	public array  $sql_process_limit; /* not to be confused w/ $sql_big_process_limit, supra */

// TODO     SQL process naming is shonky and should be got rid of ....
public array $sql_process_name = [
        'freqtable' => 'frequency table',
        'colloc'=> 'collocation',
        'sort' => 'query sort',
        'dist' => 'distribution',
        'catquery' => 'categorized query'
];
// and at some point the above needs to be changed into a const array. OR got shut of. 
	
	
	/** Object for use in API mode. Only instance of ApiController. */
	public ?ApiController $Api = null;
	
	
	/**
	 * CQPweb sometimes disconnects the client. If so, this variable must be
	 * set false so that the rest of the system knows not to echo stuff.
	 */
	public bool $client_is_disconnected = false;

	/**
	 * A script can flip this to true to indicate that output incl. exiterror() output should be JSON.
	 * Not needed in API mode since the ApiController takes over error handling totally. 
	 */
	public bool $client_expects_json = false;


	/** Colour scheme to use (indicator or JSON-encoded assoc array). Can be set by other objects. */
	public $colour_scheme;


	/** if this is set to true before the application meta is printed, 
	 *  the password minimum length and password blacklist data will be 
	 *  made available within the browser.
	 */
	public bool $transmit_password_choose_or_change_data = false; // TODO depracate




	/** array of names of variables that MUST be present in the configuration */
	const COMPULSORY_VARIABLES =
		[
			'superuser_username',
			'sql_user',
			'sql_password',
			'sql_schema',
			'sql_host',
			'cqpweb_tempdir',
			'cqpweb_uploaddir',
			'cwb_datadir',
			'cwb_registry'
		];


	/*
	 * Variables to be populated by defaults/config.php.
	 */

	public string $path_to_cwb;
	public string $path_to_utils;
	public string $path_to_perl;
	public string $path_to_python;
	public string $path_to_r;
	public string $path_to_7z;

	public /*string*/ $perl_extra_directories; // why no type? cos is string in config.php but array in this obj.

	public string $web_daemon_user;
	public string $web_daemon_group;

	public int    $cwb_max_ram_usage;
	public int    $cwb_max_ram_usage_cli;
	public int    $query_cache_size_limit;
	public int    $db_cache_size_limit;
	public int    $restriction_cache_size_limit;
	public int    $freqtable_cache_size_limit;

	/* datablob size control - see defaults.php, these variables are an ad-hoc and shonky way to manage this. */
	public int    $default_max_dbsize;
	public int    $colloc_db_premium;
	public int    $default_max_fullsize_dbs_in_cache;

	public int    $default_per_page;
	public int    $default_history_per_page;
	public int    $default_collocations_per_page;
	public string $dist_graph_img_path;
	public int    $dist_num_files_to_list;
	public int    $collocation_disallow_cutoff;
	public int    $collocation_warning_cutoff;
	public int    $colloc_max_comparison_length;
	public int    $sort_max_comparison_length;
	public int    $uploaded_file_bytes_to_show;
	public int    $hide_experimental_features;

// TODO see note in defaults.php on the following!
	public int    $default_colloc_calc_stat;
	public int    $default_colloc_range;
	public int    $default_colloc_minfreq;
	public int    $default_keyness_calc_stat;
	public int    $default_words_in_download_context;
// end of the TODO. 

	/*
	 * LOOK and FEEL 
	 */
	public string $colour_scheme_for_homepage;
	public string $colour_scheme_for_adminpage;
	public string $colour_scheme_for_userpage;
	public bool   $colour_scheme_rainbow_override;
	public bool   $homepage_use_corpus_categories;
	public string $homepage_welcome_message;
	public string $homepage_logo_left;
	public string $homepage_logo_right;
	public string $searchpage_corpus_name_suffix;

	public bool   $allow_account_self_registration;
	public bool   $allow_account_email_change;
	public bool   $allow_account_email_change_group_persist;
	public string $account_create_contact;
	public bool   $account_create_verify_email;
	public bool   $account_create_captcha;
	public bool   $account_create_one_per_email;
	public string $account_privacy_policy_url;
	public int    $blowfish_cost;
	public int    $password_minimum_length;
	public string $create_password_function;

	public bool   $user_corpora_enabled;
	public bool   $colleaguate_system_enabled;
	public int    $max_installer_processes;
	public int    $installer_process_wait_secs;

	public bool   $rss_feed_available;
	public string $rss_link;
	public string $rss_feed_title;
	public string $rss_description;

	public bool   $debug_on;
	public bool   $debug_for_all;
	public bool   $debug_sql;
	public bool   $debug_cqp;
	public bool   $debug_html;
	public bool   $debug_to_screen;
	public bool   $debug_to_log;
	public bool   $backtrace_on;
	public bool   $backtrace_for_all;
	public bool   $backtrace_compact;
	public bool   $backtrace_to_screen;
	public bool   $backtrace_to_log;

	public bool   $cqpweb_switched_off;
	public string $cqpweb_switched_off_extra_message;
// TODO add this to the framework and to defaults.
public string $cqpweb_root_url;
	public bool   $cqpweb_no_internet;
	public string $cqpweb_email_from_address;
	public string $server_admin_email_address;
	public string $cqpweb_cookie_name;
	public int    $cqpweb_cookie_max_persist;
	public bool   $cqpweb_running_on_windows;
	public bool   $use_unix_tools;
	public bool   $use_7z_always;
	public string $default_compression_format;
	public bool   $use_external_ceql_parser;
	public bool   $use_ceql_tab_optimisation;

	// not documented ...
	public bool   $plugin_autoscrub_tempfiles;

	/* private vars for transitioning configuration variable names. */
	private $cqpweb_tempdir;
	private $cqpweb_uploaddir;
	private $cwb_datadir;
	private $cwb_registry;

	/* Old names - config var names that are still accepted, but no longer used. */
	private $mysql_webuser;
	private $mysql_webpass;
	private $mysql_schema;
	private $mysql_server;
	private $mysql_utf8_set_required;
	private $mysql_local_infile_disabled;
	private $mysql_has_file_access;
	private $mysql_big_process_limit;
	private $print_debug_messages;
	private $debug_messages_textonly;
	private $all_users_see_backtrace;
	private $use_the_new_ceql;
	/* end of old names */



/* config varrs hacked onto $Config for now for the jobqueue system to work. */
var $jobqueue_max_workers = 4;
var $jobqueue_max_jobs_per_shift = 3;
 /* end hacky hacky hacky */





	/*
	 * ------------------------------
	 * Constructor and support method
	 * ------------------------------
	 */

	public function __construct(int $run_location)
	{
		/* import config variables from the global state of the config file */
		require('../lib/defaults.php');
		if (file_exists('../lib/config.php'))  /* TEMP: new name if present, if not, old name */
			require('../lib/config.php');
		else
			require('../lib/config.inc.php');

		/* transfer imported variables to object members */
		$variables = get_defined_vars();
		/* these MAY be defined... if so, we no want! */
		unset($variables['GLOBALS'], $variables['this']);

		/* superglobals all start in '_', which CQPweb config vars DON'T. */
		foreach ($variables as $k => $v)
			if ('_' != $k[0])
				$this->$k = $v;
		/* this also creates run_location as a member.... */

		/* now that the variables are object members, sanity check actions can be applied. */
		$this->postcheck_config_sanity();

		/* we ASSUME the output interface to be browser, but then check for 'cli'. 
		 * Other possible output destinations not thought about in detail yet: 'headless', 'ajax'. */
		$this->output_interface = 'cli' == PHP_SAPI ? 'cli' : 'browser';
		
		/* debug messages should be textonly ALWAYS in CLI, regardless of the setting in the config file. */
		if ('cli' == $this->output_interface)
		{
			$this->debug_messages_textonly = true; /* remove this eventually when it becomes far enough in the past */
			$this->debug_html = false;
		}
	
		/* perform initr actions on the run_location. */
		$this->initialise_run_location($this->run_location);
		
		/* add further system config here. */
	}
	
	
	public function initialise_run_location(int $run_location) : void
	{
		/* override stored run location with the one provided. */
		$this->run_location = $run_location;
		
		/* CSS action based on run_location */
		switch ($this->run_location)
		{
		case RUN_LOCATION_MAINHOME:     $this->colour_scheme = $this->colour_scheme_for_homepage;   break;
		case RUN_LOCATION_ADM:          $this->colour_scheme = $this->colour_scheme_for_adminpage;  break;
		case RUN_LOCATION_USR:          $this->colour_scheme = $this->colour_scheme_for_userpage;   break;
		case RUN_LOCATION_RSS:          /* no CSS path needed */                                    break;
		/* 
		 * tacit default: RUN_LOCATION_CORPUS/RUN_LOCATION_USERCORPUS, where the $Corpus object
		 * takes responsibility for setting $Config->colour_scheme appropriately. 
		 */
		}
	}


	/** 
	 * Internal method which runs after import of config & defaults as object members.
	 * It combines post-shuffling of the values supplied in the config file
	 * with some assorted sanity checks.
	 */
	private function postcheck_config_sanity() : void
	{
		/* FIRST CHECK: check compulsory config variables, after transferring oldname sql variables */
		
		foreach ( [
				'mysql_webuser'              => 'sql_user',
				'mysql_webpass'              => 'sql_password',
				'mysql_schema'               => 'sql_schema',
				'mysql_server'               => 'sql_host',
				/* ----------------------------*/
				'mysql_utf8_set_required'    => 'sql_utf8_set_required',
				'mysql_local_infile_disabled'=> 'sql_local_infile_disabled',
				'mysql_has_file_access'      => 'sql_has_file_access',
				'mysql_big_process_limit'    => 'sql_big_process_limit',
		] as $mysqlvar => $sqlvar )
			if (isset($this->$mysqlvar))             /* an old-style variable is set */
				$this->$sqlvar = $this->$mysqlvar;
		
		foreach (self::COMPULSORY_VARIABLES as $which)
			if (!isset($this->$which))
				exiterror("CRITICAL ERROR: setting \$$which is not specified in the configuration file.");
		
		
		/* SECOND CHECK: on the paths */ 
		
		/* allow "path_to_gnu" as oldname for "path_to_utils" if latter not provided */
		if (!isset($this->path_to_utils) && isset($this->path_to_gnu))
			$this->path_to_utils = $this->path_to_gnu;
		/* defaults for program paths: we add on / unless it is empty, in which case, a zero-string gets embedded before program names. */
		foreach ( ['path_to_cwb', 'path_to_utils', 'path_to_perl', 'path_to_python', 'path_to_r'] as $path)
			$this->$path = (empty($this->$path)  ?  ''  : rtrim($this->$path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
		
		/* THIRD CHECK: on the perl paths */
		
		/* $Config->perl_extra_directories is an array of directories; but the config file format is a string of pipe-
		 * delimited directories. This bit of code converts. An empty array is used if the config string vairable is not set.    */
		if (empty($this->perl_extra_directories))
			$this->perl_extra_directories = [];
		else
			$this->perl_extra_directories = array_map('rtrim',explode('|',$this->perl_extra_directories));
		
		
		/* FOURTH CHECK: let's organise the directory variables into something saner */
		$this->dir = new CQPwebEnvConfigDirSpec(
				$this->cqpweb_tempdir,
				$this->cqpweb_uploaddir,
				$this->cwb_datadir,
				$this->cwb_registry,
			);
		unset($this->cqpweb_tempdir, $this->cqpweb_uploaddir, $this->cwb_datadir, $this->cwb_registry);
		
		
		/* FIFTH CHECK: create SQL process limit array */
		$this->sql_process_limit = array(
			'freqtable' => $this->sql_big_process_limit,
			'colloc'    => $this->sql_big_process_limit,
			'sort'      => $this->sql_big_process_limit,
			'dist'      => (20 * $this->sql_big_process_limit), /* Note: dist-db is lightweight, therefore there is a multiplier here. */ 
			'catquery'  => $this->sql_big_process_limit
			);
		//TODO use integer constants instead of strings. See also defaults.php
		
		
		/* SIXTH CHECK: override captcha setting if no gd extension */
		if (!extension_loaded('gd'))
			$this->account_create_captcha = false;
		
		
		/* SEVENTH CHECK: clean up the value, so we can be sure the root URL ends in a slash. */
		if (isset($this->cqpweb_root_url))
			$this->cqpweb_root_url = rtrim($this->cqpweb_root_url, '/') . '/';
		
		
		/* EIGHTH CHECK: collocation warning cutoff must always be lower than disallow cutoff  */
		if ($this->collocation_warning_cutoff >= $this->collocation_disallow_cutoff)
			$this->collocation_warning_cutoff = $this->collocation_disallow_cutoff - 1;
		
		
		/* NINTH CHECK - a big one: deduce SQL file load behaviour. */
		
		/* -- If mysqld has file access, it is mysqld (not php-mysqli-client) which will do the opening of the file.
		 * -- But if mysqld does not have file access, then we should load all infiles locally, if we can. */
		
		/* From the two config variables, deduce whether we have ANY infile access. */
		if ($this->sql_has_file_access)
		{
			$this->sql_LOAD_DATA_INFILE_command = 'LOAD DATA INFILE';
			/* if the SERVER has file access, then lack of LOAD DATA LOCAL doesn't matter */
			$this->sql_infile_disabled = false;
		}
		else
		{
			/* otherwise, whether we have ANY infile access is dependent on whether we have local access */
			$this->sql_LOAD_DATA_INFILE_command = 'LOAD DATA LOCAL INFILE';
			$this->sql_infile_disabled = $this->sql_local_infile_disabled;
		}
		
		
		/* TENTH CHECK: interactions among the debug variables */
		
		/* including: oldstyle debug vars compatibility. */
		if ($this->print_debug_messages ?? false)
			$this->debug_on = true;
		if ($this->debug_messages_textonly ?? false)
			$this->debug_html = false;
		if ($this->all_users_see_backtrace ?? false)
			$this->backtrace_for_all = true;
		$this->print_debug_messages    = $this->debug_on;
		$this->debug_messages_textonly = !$this->debug_html;
		$this->all_users_see_backtrace = $this->backtrace_for_all;
		
		/* ELEVENTH CHECK: do we have a valid value for default_compression_format? */
		if (!(in_array($this->default_compression_format, ['gz','bz2','xz','7z',])))
			$this->default_compression_format = 'gz';
		
		
		/* EXTRA CHECK - backwards compatibility on CSS settings */
		if (isset($this->css_path_for_homepage))
			$this->colour_scheme_for_homepage  = preg_replace('~(../)*css/CQPweb-(\w+).css~', '~$2', $this->css_path_for_homepage);
		if (isset($this->css_path_for_adminpage))
			$this->colour_scheme_for_adminpage = preg_replace('~(../)*css/CQPweb-(\w+).css~', '~$2', $this->css_path_for_adminpage);
		if (isset($this->css_path_for_userpage))
			$this->colour_scheme_for_userpage  = preg_replace('~(../)*css/CQPweb-(\w+).css~', '~$2', $this->css_path_for_userpage);
		/* remove eventually */
		
		
		/* EXTRA CHECK - backwards compatibility on the undocumented "use_the_new_ceql" */	
		if (isset($this->use_the_new_ceql))
			$this->use_external_ceql_parser = !$this->use_the_new_ceql;
		/* remove eventually */	
	}
	
	
	
	
	
	/* 
	 * ----------------------------------------------------------------------------
	 * Getter methods: only for variables more complex than a straightforward read.
	 * ----------------------------------------------------------------------------
	 * 
	 * So far, just one of these.
	 */
	
	
	/**
	 * Returns an integer containing the RAM limit to be passed to CWB programs that
	 * allow a RAM limit to be set - note, the flag (-M or whatever) is not returned,
	 * just the number of megabytes as an integer.
	 */
	public function get_cwb_memory_limit() : int
	{
		return ( ('cli' == $this->output_interface) ? $this->cwb_max_ram_usage_cli : $this->cwb_max_ram_usage );
	}
	
	
	
	/* 
	 * -------------------------------------------------------------
	 * Functions managing the internal slave processes / connections
	 * -------------------------------------------------------------
	 */
	
	
	/**
	 * This method for getting the CQP or SQLDB connection is behind the get_global_(cqp|sql) functions.
	 * 
	 * @param  string          $which  'sql' or 'cqp'. Any other argument string will cause an exiterror.
	 * @return CQP|mysqli      Depending on $which.
	 */
	public function get_slave_link(string $which) : ?object /* (php8) CQP|mysqli */
	{
		static $time_sql_link_last_requested = 0;
		
		if ('sql' != $which && 'cqp' != $which)
			exiterror("Bad slave function handle $which!!");
		
		if ('sql' == $which)
		{
			/* ping mysqld iff it's been over a minute since the last time this link was used */
			$t = time();
			if ($time_sql_link_last_requested && 60 < (time() - $time_sql_link_last_requested))
			{
				if (!mysqli_ping($this->slave_process_links['sql']))
				{
					/* close lost connection and null-over the object, so it will be (re-)connected. */
					@mysqli_close($this->slave_process_links['sql']);
					$this->slave_process_links['sql'] = null;
				}
			}
		}
		
		/* is_object() handily works with both mysqli and CQP */
		if (!is_object($this->slave_process_links[$which]))
			$this->connect_slave_program($which);
		
		if ('sql' == $which)
			$time_sql_link_last_requested = $t;
		
		return $this->slave_process_links[$which];
	}


	/** Test whether a 'cqp' or 'sql' connection exists (yet). */
	public function slave_link_exists(string $which) : bool
	{
		if ('sql' != $which && 'cqp' != $which)
			exiterror("Bad slave function handle $which!!");
		return is_object($this->slave_process_links[$which]);
	}
	
	
	private function connect_slave_program(string $which) : void
	{
		global $Corpus;
		
		if (!empty($this->slave_process_links[$which]))
			return;
		
		switch ($which)
		{
		case 'sql':
			$this->slave_process_links['sql'] = create_sql_link();
			break;
			
		case 'cqp': 
			$slave_arg = 
				(
					(RUN_LOCATION_CORPUS == $this->run_location || RUN_LOCATION_USERCORPUS == $this->run_location)
				&& 
					$Corpus && $Corpus->specified
				)
				? $Corpus->cqp_name : null;
			
			$this->slave_process_links['cqp'] = create_slave_cqp($slave_arg);
			break;
			
		default:
			exiterror("Bad slave function handle $which!!");
		}
	}
	
	
	public function disconnect_slave_program(string $which) : void
	{
		if (!is_object($this->slave_process_links[$which]))
			return;
		
		switch ($which)
		{
		case 'sql':
			mysqli_close($this->slave_process_links['sql']);
			break;
		case 'cqp':
			$this->slave_process_links['cqp']->disconnect();
			break;
		default: 
			return;
		}
		$this->slave_process_links[$which] = null;
	}
	
}


/**
 * Class of which each run of CQPweb should only ever have ONE - it represents the logged-in user.
 * 
 * The instantiation should always be the global $User object.
 * 
 */
class CQPwebEnvUser 
{
	/* Dynamic properties depracated in PHP 8, so here we go... */
	/* database fields not otherwise listed below. */
	public ?string $realname;
	public ?string $email;
	public ?string $affiliation;
	public ?string $country;
	public bool $conc_kwicview;
	public bool $conc_corpus_order;
	public bool $cqp_syntax;
	public bool $context_with_tags;
	public bool $use_tooltips;
	public bool $css_monochrome;
	public int $coll_statistic;
	public int $coll_freqtogether;
	public int $coll_freqalone;
	public int $coll_from;
	public int $coll_to;
	public int $key_statistic;
	public int $max_dbsize;
	public bool $freqlist_altstyle;
	public bool $catquery_autosave;
	public bool $thin_default_reproducible;
	public ?string $linefeed;
	public ?string $passhash;
	public int $acct_status;
	public ?string $verify_key;
	public int $expiry_time;
	public bool $password_locked;
	public int $password_expiry_time;
	public ?string $last_seen_time;
	public ?string $acct_create_time;

	/** Is there a logged-in user? (bool) */
	public bool $logged_in;
	
	/** full array of privileges (db objects) available to the logged-in user (individually or via group) */
	public array $privileges;
	
	/** The logged-in user's username. */
	public string $username;
	
	/** The logged-in user's database integer ID. */
	public int $id;
	
	public function __construct(bool $check_for_logged_in_user = true)
	{
		global $Config;
		/* 
		 * Now, let us get the username ... 
		 */
		
		/* if this environment is in a CLI script, count us as being logged in as the first admin user */ 
		if ('cli' == $Config->output_interface)
		{
			list($username) = list_superusers();
			$this->logged_in = true; 
		}
		else
		{
			/* look for logged on user */
			if (!$check_for_logged_in_user  || !isset($_COOKIE[$Config->cqpweb_cookie_name]) || !($checked = check_user_cookie_token($_COOKIE[$Config->cqpweb_cookie_name])))
			{
				/* no one is logged in */
				$username = '(no user is logged in)';
				$this->logged_in = false;
			}
			else
			{
				$this->logged_in = true;
				$username = $checked->username;
				
				/* only bother setting cookies, loggin a sight of user etc. if the client browser is connected */
				if (!$Config->client_is_disconnected)
				{
					/* if the cookie token is more than half an hour old, delete and emit a new one; otherwise, touch the existing one. 
					 * (so the token should only get used within a single session, or for the first connection of a subsequent session.) */
					if (time() - 1800 > $checked->creation )
						emit_new_cookie_token($username, 
								(isset($_COOKIE[$Config->cqpweb_cookie_name . 'Persist']) && '1' === $_COOKIE[$Config->cqpweb_cookie_name . 'Persist'])
							);
					else
						touch_cookie_token($_COOKIE[$Config->cqpweb_cookie_name]);
					/* cookie tokens which don't get touched will eventually get old enough to be deleted */
	
					/* Update the last-seen date (on every hit from user's browser!) */
					touch_user($username);
				}
			}
		}

		/* now we know whether we are logged in and if so, who we are; so set up the user information */
		$this->initialise_detected_user($username);
	}
	
	// use if, at construct, we told the constructor not to check for a logged in user.
	public function set_user_by_name(string $username) : void
	{
		$this->logged_in = true;
		$this->initialise_detected_user($username);
	}
	
	public function set_user_by_id(int $user_id) : void
	{
		$this->set_user_by_name(user_id_to_name($user_id));
	}
	
	
	// actions taken after we have asceretained the username fo the lofgfwed in user. 
	private function initialise_detected_user(string $username) : void
	{
		if ($this->logged_in)
		{
			/* import database fields as object members. */
			foreach ( ((array)get_user_info($username)) as $k => $v)
			{
				/* currently, we only re-type bools. */
				switch($k)
				{
				/* BOOL */
				case 'use_tooltips':
				case 'conc_kwicview':
				case 'conc_corpus_order':
				case 'cqp_syntax':
				case 'context_with_tags':
				case 'css_monochrome':
				case 'thin_default_reproducible':
					$this->$k = (bool) $v;
					break;
				default:
					$this->$k = $v;
					break;
				}
			}
			/* the above also imports $username --> $User->username which is the canonical way to access it. */
			
			/* look for a full list of privileges that this user has. */
			$this->privileges = get_collected_user_privileges($username);
			
			/* so we can now apply privileges that require some sort of setup action. */
			php_execute_time_add($this->max_extra_runtime());
		}
		else
			$this->privileges =  array();
	}
	
	
	/**
	 * Is the currently logged-in user an administrator (aka superuser)?
	 * @return bool
	 */
	public function is_admin() : bool
	{
		return ( PHP_SAPI=='cli' || ($this->logged_in && user_is_superuser($this->username)) );
	}
	
	/**
	 * Is the currently logged-in user an owner of the given or current corpus (or, a superuser)?
	 * 
	 * @param  string  $corpus_name    If given, this corpus, instead of the active one, will be checked.
	 * @return bool                    True if the current user is an admin or the owner of the corpus. 
	 */
// TODO make this take an int argument. 
	public function is_admin_or_owner(?string $corpus_name = null) : bool
	{
		global $Corpus;
		
		if (!$this->logged_in)
			return false;
		
		if ($this->is_admin())
			return true;
		
		if (is_null($corpus_name))
		{
			if ($Corpus->specified)
				return ($Corpus->owner == $this->username);
			else 
				return false;
		}
		else 
		{
			if (!($c_info = get_corpus_info($corpus_name)))
				return false;
			return ($c_info->owner == $this->username);
		}
	}
	
	/**
	 * Is the currently logged-in user an owner of the given or current corpus (or, a superuser)?
	 * 
	 * @param  int  $corpus_id    If given, this corpus, instead of the active one, will be checked.
	 * @return bool               True if the current user is an admin or the owner of the corpus. 
	 */	
	public function is_admin_or_owner_by_id(?int $corpus_id = null) : bool
	{
		global $Corpus;
		
		if (!$this->logged_in)
			return false;
		
		if ($this->is_admin())
			return true;
		
		if (is_null($corpus_id))
		{
			if ($Corpus->specified)
				return ($Corpus->owner == $this->username);
			else 
				return false;
		}
		else 
		{
			if (!($c_info = get_corpus_info_by_id($corpus_id)))
				return false;
			return ($c_info->owner == $this->username);
		}
	}
	
	/**
	 * Checks whether logged-in user has binary-file privilege.
	 * @return bool
	 */
	public function has_cqp_binary_privilege() : bool
	{
		if ($this->is_admin())
			return true;
		foreach ($this->privileges as $p)
			if (PRIVILEGE_TYPE_CQP_BINARY_FILE == $p->type)
				return true;
		return false;
	}
	
	/** 
	 * Checks whether logged-in user is over their limit, disk-space-wise.
	 * @return bool 
	 */
	public function has_exceeded_user_corpus_disk_limit() : bool
	{
		return 1 > ( 
				max_value_of_integer_scoped_privilege($this->username, PRIVILEGE_TYPE_DISK_FOR_CORPUS) 
				- 
				sum_user_corpus_disk_usage($this->username) 
				);
	}
	
	/**
	 * Returns the size, in tokens, of the largest sub-corpus for which the logged-in user
	 * allowed to create frequency lists.
	 * @return int
	 */
	public function max_freqlist() : int
	{
		return max_value_of_integer_scoped_privilege($this->username, PRIVILEGE_TYPE_FREQLIST_CREATE);
	}
	
	/**
	 * Returns the size, in bytes, of the biggest file the logged-in user is allowed to upload into CQPweb space.
	 * @return int
	 */
	public function max_upload_file() : int
	{
		return max_value_of_integer_scoped_privilege($this->username, PRIVILEGE_TYPE_UPLOAD_FILE);
	}
	
	/**
	 * Returns the limit, in bytes, of the amount of space in the CQPweb upload area the logged-in user is allowed to use.
	 * @return int
	 */
	public function max_upload_disk_space() : int
	{
		return max_value_of_integer_scoped_privilege($this->username, PRIVILEGE_TYPE_DISK_FOR_UPLOADS);
	}
	
	/**
	 * Returns the limit, in bytes, of the amount of space the logged-in user is allowed to use for user-installed corpora.
	 * @return int
	 */
	public function max_user_corpus_disk_space() : int
	{
		return max_value_of_integer_scoped_privilege($this->username, PRIVILEGE_TYPE_DISK_FOR_CORPUS);
	}
	
	/**
	 * Returns the number of "extra seconds" of execution time the logged-in user is allowed.
	 * @return int
	 */
	public function max_extra_runtime() : int
	{
		if (!$this->logged_in)
			return 0;
		return max_value_of_integer_scoped_privilege($this->username, PRIVILEGE_TYPE_EXTRA_RUNTIME);
	}
	
	/**
	 * Returns the right EOL to use in text downloads for the logged-in user. 
	 * @return string
	 */
	public function eol() : string
	{
		if (is_null($this->linefeed) || 'au' == $this->linefeed)
			$eol = guess_user_linefeed($this->username);
		else
			$eol = $this->linefeed;
		
		return strtr($eol, "da", "\r\n");
	}
}



/**
 * Class of which each run of CQPweb should only ever have ONE - it represents the environment-corpus
 * of the currently-running instance (i.e. the corpus that we "are in"), if there is one.
 * 
 * The instantiation should always be the global $Corpus object.
 *
 */
class CQPwebEnvCorpus
{
	/*
	 * CORPUS ACCESS INFORMATION
	 * =========================
	 */
	
	/* constants that flag the reason the corpus data cannot be accessed by the current user */
	
	const NOACCESS_BUT_ACTUALLY_THERE_IS   = 0;
	const NOACCESS_USERCORPUS_SWITCHED_OFF = 1;
	const NOACCESS_USERCORPUS_NOT_OWNED    = 2;
	const NOACCESS_USERCORPUS_NO_GRANT     = 3;
	const NOACCESS_USERCORPUS_DISKBLOCKED  = 4;
	const NOACCESS_SYSTEMCORPUS_NO_PRIV    = 5;
	const NOACCESS_SYSTEMCORPUS_LOGGED_OUT = 6;
	// FIXME php8 enum??
	
	/** This is set to a privilege constant to indicate what level of privilege the currently-logged-on user has. */
	public $access_level;
	
	
	/** contains a class constant indicating why the user can't access this corpus */
	public $no_access_reason_why = self::NOACCESS_BUT_ACTUALLY_THERE_IS;
	
	
	/** are we running within a particular corpus ? */
	public $specified = false;
	
	
	/*
	 * Fields from the corpus_info database table
	 * ==========================================
	 */
	
	/** the integer ID of this corpus */
	public int $id;
	
	/** This is the way to get the corpus handle. */
	public string $handle;
	/* old depracated way top get a corpus handle, retained for backwards-comp. TO BE REMOVED. */
	public string $name; // TODO get rid oif this.
	
	/** The corpus title */
	public string $title;
	
	/** the username of the owner; if empty, it's a system corpus. @see CQPwebEnvCorpus::is_user_owned() */
	public ?string $owner;
	
	
	/* comments not added for the vars below, which are from corpus_info DB */
	
	public string $cqp_name;
	public $date_of_indexing;
	public $cwb_external;
	public string $language;
	public int    $size_tokens;
	public int    $size_types;
	public int    $size_texts;
	public int    $size_bytes_index;
	public int    $size_bytes_freq;
	public float  $sttr_1kw;
	
	public ?string $access_statement;
	public $primary_classification_field;
	public $case_sensitive;
	public $accent_sensitive;
	public $visible;
	public $corpus_cat;
	public ?string $external_url;
	public $colour_scheme;
	
	public $primary_annotation;
	public $secondary_annotation;
	public $tertiary_annotation;
	public $tertiary_annotation_tablehandle;
	public $combo_annotation;
	
	public $main_script_is_r2l;
	public $conc_s_attribute;
	public $conc_scope;
	public $initial_extended_context;
	public $max_extended_context;
	public $alt_context_word_att;
	public $alt_context_hide_empty;
	
	public $visualise_gloss_in_concordance;
	public $visualise_gloss_in_context;
	public $visualise_gloss_annotation;
	public $visualise_translate_in_concordance;
	public $visualise_translate_in_context;
	public $visualise_translate_s_att;
	public $visualise_position_labels;
	public $visualise_position_label_attribute;
	public $visualise_conc_extra_js;
	public $visualise_context_extra_js;
	public $visualise_conc_extra_css;
	public $visualise_context_extra_css;
	public $visualise_break_context_on_punc;
	
	public ?string $indexing_notes;
	
	/* variables deduced from different database values at object startup time. // TODO doc comment then, set static types */
	public $cqp_query_default_flags;
	public $sql_collation_for_word;
	public $conc_scope_is_based_on_s;
	
	
	/**
	 * The parameter allows the environment-corpus to be directly specified.
	 * If it is not given, then it will be deduced (a) on the CLI, from the working directory.
	 * (b) on the Web, from the SCRIPT_NAME value in $_SERVER (i.e. indirectly from the URL).
	 */
	public function __construct(string $parameter_corpus = null)
	{
		$m = null;
		
		global $Config;
		
		/* first, check whether we are actually in the run location required, 
		 * and leave $Corpus as just an empty object if we're not. */
		if (RUN_LOCATION_CORPUS != $Config->run_location)
			return;
		
		/* first: try to identify the corpus. 
		 * Order of checks is: (1) constructor parameter; (2) $_GET["c"]; (3) guess from the $_SERVER */
		if (!is_null($parameter_corpus))
		{
			$this->handle = $parameter_corpus;
			unset($parameter_corpus);
		}
		else 
		{
			if ('cli' == $Config->output_interface)
			{
				/* what corpus are we in? --> last element of the Unix environment variable PWD */
				$junk = explode('/', str_replace('\\', '/', rtrim($_SERVER['PWD'], '/')));
				$this->handle = end($junk);
				unset($junk);
				/* note, this will not work if "chdir" has been used before environment setup,
				 * because the PWD is the working directory AT STARTUP.
				 * On the other hand, we cannot use getcwd() as it will (on Linux at least) resolve symlinks! 
				 * Whether the above will work on Windows is to be discovered. */ 
			}
			else
			{
				/* what corpus are we in? --> last element of SCRIPT_NAME before the filename. */
				if (!preg_match('|/([\w\-]+)/[^/]+\.php$|', $_SERVER['SCRIPT_NAME'], $m))     // FIXME shouldn't be a bespoke rx here!
					exit("Core critical error: could not determine what corpus we are using.\n");
				$this->handle = $m[1];
				/* getcwd() would have worked here too BUT it is apparently disabled on some servers. */
			}
			/* if we got, for instance, "adm" from this process, then $Config's run_location being CORPUS is clearly wrong. */
			if (in_array($this->handle, CQPWEB_RESERVED_SUBDIRS))
			{
				unset($this->handle);
				return;
			}
		}
		
		if (!empty($this->handle))
			$this->specified = true;
		/* if specified is not true, then $Config->run_location will tell you where we are running from. */
		
		$this->name = $this->handle; // this is for backwards proofing; aim is to use handle (or, preferably, id) everywhere, never name.
		
		
		/* only go hunting for more info on the $Corpus if one is actually specified...... */
		if ($this->specified)
		{
			/* import database fields as object members. */
			
			$result = do_sql_query("select * from corpus_info where corpus = '{$this->handle}'");
			if (1 > mysqli_num_rows($result))
				exit("Core critical error: invalid corpus handle submitted to database.\n");
			
			foreach (mysqli_fetch_assoc($result) as $k => $v)
			{
				/* allows for special cases */
				switch ($k)
				{
				/* fallthrough list for do-nothing cases */
				case 'corpus':
				case 'handle':
					$this->handle = $v;
					break;
				/* note that here in the global object, `corpus` ==> $this->handle ,
				 * but in small objects for non-environment corpora, it stays as `corpus`.
				 * This is annoying and confusing, and is for historical reasons.
				 * The long term plan is to change it, in both cases, to "handle".
				 */
				
				/* fallthrough list for bools */
				case 'is_user_corpus':
				case 'cwb_external':
				case 'visible':
				case 'case_sensitive':
				case 'accent_sensitive':
				case 'main_script_is_r2l':
				case 'alt_context_hide_empty':
				case 'visualise_gloss_in_concordance':
				case 'visualise_gloss_in_context':
				case 'visualise_translate_in_concordance':
				case 'visualise_translate_in_context':
				case 'visualise_position_labels':
				case 'visualise_break_context_on_punc':
					$this->$k = (bool)$v;
					break;
				
				/* fallthrough list for integers */
				case 'id':
				case 'corpus_cat':
				case 'initial_extended_context':
				case 'max_extended_context':
				case 'size_tokens':
				case 'size_types':
				case 'size_texts':
				case 'conc_scope':
					$this->$k = (int)$v;
					break;
				
				/* everything else is added as a string - incl. the timestamp column date_of_indexing! */
				default:
					$this->$k = $v;
					break;
				}
			}
			
			
			/* 
			 * variables which need sanity checks/adjustment/deducing from another setting.... 
			 * ===============================================================================
			 * 
			 * (all so far about concordance/context rendering)
			 */
			
			/* deduce default regex flags for auto-generated CQP-syntax queries */
			$this->cqp_query_default_flags = get_query_flag($this->accent_sensitive, $this->case_sensitive);
			
			/* note that this applies only to words.*/
			$this->sql_collation_for_word = report_corpus_word_collate($this); 
			
			/* concordance scope deduction */
			$this->conc_scope = ($this->conc_scope < 1 ? 1 :  $this->conc_scope);
			$this->conc_scope_is_based_on_s = !empty($this->conc_s_attribute);
			
			/* sanity check for extended context width values... */
			if ($this->initial_extended_context > $this->max_extended_context)
				$this->initial_extended_context = $this->max_extended_context;
			
			/* sanity check for concordance / conteaxt glossing */
			if ($this->visualise_gloss_in_concordance || $this->visualise_gloss_in_context)
				if (!isset($this->visualise_gloss_annotation))
					$this->visualise_gloss_annotation = 'word'; 
			
			/* sanity check for translation */
			if (empty($this->visualise_translate_s_att))
			{
				/* we can't default this one: we'll have to switch off these variables */
				$this->visualise_translate_in_context = false;
				$this->visualise_translate_in_concordance = false;
			}
			else
			{
				/* we override $conc_scope etc... if the translation s-att is to be used in concordance */
				if ($this->visualise_translate_in_concordance)
				{
					$this->conc_s_attribute = $this->visualise_translate_s_att;
					$this->conc_scope_is_based_on_s = true;
					$this->conc_scope = 1;
				}
			}
			
			
			/* some settings then transfer to $Config */
			global $Config;
			if (!empty($this->owner))
			{
				$Config->run_location = RUN_LOCATION_USERCORPUS;
				if ('..' == substr($this->colour_scheme, 0, 2))
					$this->colour_scheme = '../../../' . $this->colour_scheme;
				// eventually, will not be necessary to check this
			}
			if (isset($this->colour_scheme))
			{
				$Config->colour_scheme = $this->colour_scheme;
				/* We keep it here so it can be discovered if necessary. */
			}
			
			/* finally, since we are in a corpus, we need to ascertain (a) whether the user is allowed
			 * to access this corpus; (b) at what level the access is. */
			$this->ascertain_access_level();
			
			if (PRIVILEGE_TYPE_NO_PRIVILEGE == $this->access_level)
			{
				if ($Config->Api)
					$Config->Api->raise_error(API_ERR_NO_ACCESS, "You cannot access this corpus (NOACCESS code = {$this->no_access_reason_why})");
				else
					set_next_absolute_location(($this->is_user_owned() ? '../../../' : '') . "../usr/index.php?ui=accessDenied&corpusDenied={$this->handle}&why={$this->no_access_reason_why}");
					/* redirects to a page telling them they do not have the privilege to access this corpus. */
				cqpweb_shutdown_environment();
				exit();
				/* otherwise, we know that the user has some sort of access to the corpus, and we can continue */
			}
		}
	}
	
	/**
	 * Sets up the access_level member to the privilege type indicating
	 * the HIGHEST level of access to which the currently-logged-in user
	 * is entitled for this corpus.
	 * 
	 * Assumes that the global object $User is already set up.
	 * 
	 * @return int      A PRIVILEGE_TYPE_CORPUS_ constant, indicating what
	 *                  level of access the logged-in user has. 
	 *                  This is also stored in the $access_level member.
	 *                  If the return is PRIVILEGE_TYPE_NO_PRIVILEGE,
	 *                  then the $no_access_reason_why member will be set to 
	 *                  a class constant  which indicates which of the
	 *                  various possible reasons is at fault. 
	 */ 
	private function ascertain_access_level() : int
	{
		global $Config;
		global $User;
		
		/* superusers have full access to everything. */
		if ($User->is_admin())
		{
			$this->access_level = PRIVILEGE_TYPE_CORPUS_FULL;
			return PRIVILEGE_TYPE_CORPUS_FULL;
		}
		
		/* otherwise we must dig through the privileges owned by this user. */
		
		/* start by assuming NO access. Then look for the highest privilege this user has. */
		$this->access_level = PRIVILEGE_TYPE_NO_PRIVILEGE;
		
		if (!$User->logged_in)
			$this->no_access_reason_why = self::NOACCESS_SYSTEMCORPUS_LOGGED_OUT;
		else
		{
			if ($this->is_user_owned())
			{
				/* user corpus */

				/* no one can ever get into a user corpus if the system is switched off. */
				if (!$Config->user_corpora_enabled)
					$this->no_access_reason_why = self::NOACCESS_USERCORPUS_SWITCHED_OFF;
				else
				{
					if ($this->owner == $User->username)
					{
						if ($User->has_exceeded_user_corpus_disk_limit())
							$this->no_access_reason_why = self::NOACCESS_USERCORPUS_DISKBLOCKED;
						else
							return $this->access_level = PRIVILEGE_TYPE_CORPUS_FULL;
					}
					else if ($Config->colleaguate_system_enabled)
					{
						/* check for colleaguate grant. */
						if (user_corpus_is_granted_to_user($this->id, $User->id))
							return $this->access_level = PRIVILEGE_TYPE_CORPUS_FULL;
						else
							$this->no_access_reason_why = self::NOACCESS_USERCORPUS_NO_GRANT;
					}
					else
						/* definitionally, if the C'gate system is switched off, there can be no colleaguate grant */
						$this->no_access_reason_why = self::NOACCESS_USERCORPUS_NO_GRANT;
				}
			}
			else
			{
				/* system corpus */
				$this->access_level = max_user_privilege_level_for_corpus($User->username, $this->handle);
				
				if (PRIVILEGE_TYPE_NO_PRIVILEGE == $this->access_level)
					$this->no_access_reason_why = self::NOACCESS_SYSTEMCORPUS_NO_PRIV;
			}
		}
		
		return $this->access_level;
	}
	
	/**
	 * Is this a user-owned corpus? 
	 * 
	 * @param  bool            $get_username   If true, owner's username is returned
	 *                                         to indicate boolean true.
	 * @return bool|string                     If not user-owned, then false. Otherwise,
	 *                                         either boolean true or the owner's username.
	 */
	public function is_user_owned(bool $get_username = false) /* php8 : bool|string */
	{
		if (!empty($this->owner))
			return ($get_username ? $this->owner : true);
		else
			return false;
	}
	
	/**
	 * This allows the $Corpus object itself to be embedded as shorthand for $Corpus->handle.
	 *  
	 * @return string
	 */
	public function __toString() : string
	{
		return $this->handle;
	}
}










/* ============================== *
 * Startup and shutdown functions *
 * ============================== */


// subroutine of startup_env: am,ange the flags once we know the 3 global objects are set up.
function cqpweb_check_environment_flags(int $flags = CQPWEB_STARTUP_NO_FLAGS) : void
{
	global $User;
	
	if ($flags & CQPWEB_STARTUP_CHECK_ADMIN_USER)
	{
		if (!$User->logged_in)
			exiterror("You can't access that function because you are not logged in to CQPweb.");
		if (!$User->is_admin())
			exiterror("You do not have permission to use this part of CQPweb.");
	}
	
	if ($flags & CQPWEB_STARTUP_CHECK_OWNER_OR_ADMIN)
	{
		if (!$User->logged_in)
			exiterror("You can't access that data because you are not logged in to CQPweb.");
		if (!$User->is_admin_or_owner())
			exiterror("You do not have permission to use this part of CQPweb.");
	}
}



/**
 * Function that starts up CQPweb and sets up the required environment.
 * 
 * All scripts that require the environment should call this function.
 * It does assorted setup that every script would otherwise need to repeat. 
 * 
 * It should be called *after* the inclusion of most functions, 
 * but *before* the inclusion of admin functions (if they are 
 * conditional on admin login).
 * 
 * Pass in bitwise-OR'd flags to control the behaviour. 
 */
function cqpweb_startup_environment(int $flags = CQPWEB_STARTUP_NO_FLAGS, int $run_location = RUN_LOCATION_CORPUS, ?string $specify_corpus_direct = null) : void
{
	
	/* -------------- *
	 * TRANSFROM HTTP *
	 * -------------- */
	
	/* process this flag first: it's a check on CLI status separate from the other flags. */
	if ($flags & CQPWEB_STARTUP_ENFORCE_CLI)
		if ('cli' != PHP_SAPI)
			exit("Critical error: Cannot run CLI scripts over the web!\n");

	/* the very first thing we do is set up _GET, _POST etc. .... */
	
	/* WE ALWAYS USE GET! */
	
	/* sort out our incoming variables.... */
	if (isset($_POST))
		foreach($_POST as $k => $v)
			$_GET[$k] = $v;
	/* now, we can be sure that any bits of the system that rely on $_GET being there will work. */
	
	
	/* --------------------------------- *
	 * WORKAROUNDS FOR OTHER PHP SADNESS *
	 * --------------------------------- */
	
	/* As of 5.4, PHP emits nasty warning messages if date.timezone is not set in the ini file.... */
	if (empty(ini_get('date.timezone')))
		ini_set('date.timezone', date_default_timezone_get());
	
	/* save RAM by not letting the opcache store Andrew's lengthy comments. */
	ini_set('opcache.save_comments', false);
	
	/* We DON'T want to abort if the user disconnects, because that creates an exit point we're not prepared for. */
	ignore_user_abort(true);	
	
	/* write progressively to output in case of long loading time */
	ini_set('output_buffering', false);
	ob_implicit_flush(true);
	
	
	/* ----------------------- *
	 * SETUP PLUGIN AUTOLOADER *
	 * ----------------------- */
	
	if (function_exists('autoload_plugin_class'))
		spl_autoload_register('autoload_plugin_class');
	/* we ONLY use autoload for the plugin system */
	
	
	/* -------------- *
	 * GLOBAL OBJECTS *
	 * -------------- */
	
	/** @var CQPwebEnvConfig $Config     
	 *  Global object containing information on system configuration. */
	global $Config;
	/** @var CQPwebEnvUser   $User       
	 *  Global object containing information on the current user account. */
	global $User;
	/** @var CQPwebEnvCorpus $Corpus     
	 *  Global object containing information on the current corpus. */
	global $Corpus;
	
	/* create global settings options */
	$Config = new CQPwebEnvConfig($run_location);
	
	
	/* check for "switched-off" status; if we are switched off, then just spit out the "switched off" message. */
	if ($Config->cqpweb_switched_off && 'cli' != $Config->output_interface)
	{
		include('../lib/switched-off.php');
		exit();
	}
	/* we have to do this BEFORE connecting to anything... */
	
	/* and, if we are in API mode, add the ApiController object to Config. */
	if (isset($GLOBALS['API']))
	{
		$Config->Api = new ApiController($GLOBALS['API'], $_GET);
		unset($GLOBALS['API']);
		
		/* now, scrub GET, so that when we prepare arguments, the API can shove it full of stuff. */
		$_GET = array();
		
		/* prepare API arguments; if doing so causes an error, skip straight to shutdown. */
		if (!$Config->Api->prepare_arguments())
		{
			cqpweb_shutdown_environment();
			exit;
		}
	}
	
	
	/*
	 * Now that we have the $Config object, we can connect to MySQL.
	 * 
	 * The flags (here and below) are for "dont" because we assume 
	 * the default behaviour is to need both a DB connection and a 
	 * slave CQP process.
	 * 
	 * If one or both is not required, a flag can be passed in to 
	 * save the connection (not much of a saving in the case of the DB,
	 * potentially quite a performance boost for the slave process.)
	 */
	if ( !($flags & CQPWEB_STARTUP_DONT_CONNECT_SQL) )
		$Config->get_slave_link('sql');
	
	/* now the DB is connected, we can do the other two global objects. */
	
	$User   = new CQPwebEnvUser( !($flags & CQPWEB_STARTUP_DONT_CHECK_USER) );
	
	$Corpus = new CQPwebEnvCorpus($specify_corpus_direct);
	
	
	/* now that we have the global $Corpus object, we can connect to CQP */
	if ( !($flags & CQPWEB_STARTUP_DONT_CONNECT_CQP) )
		$Config->get_slave_link('cqp');


	/* ini-set the maximum amount of RAM that should be used within PHP.
	 * TODO - make the basic amount configurable in config.php, via $php_max_ram or similar alongside the equiv value for CWB
	 * TODO - ALLOW A PRIVILEGE to increase this more. 
	 * (note that this mostly affects the construction of internal representation of complex restrictions) */
	$curr_mem_limit = ini_parse_quantity(ini_get("memory_limit"));
	/* remember that -1 means no memory limit! */
	if (1073741824 > $curr_mem_limit && 0 < $curr_mem_limit)
		ini_set("memory_limit", 1073741824); /* 1 GB */
// TODO might weant to add a similar operation on max_execution_time


	/* We can now process the remainder of the flags for sanity checks. */
	/* cqpweb-new-ui: maintenance mode (superusers only while it is on) */
	require_once(__DIR__ . '/cqpweb-new-ui/site-lib.php');
	cp_maintenance_gate();

	cqpweb_check_environment_flags($flags);
	
	/* and that's it. */
}




/**
 * Performs pre-shutdown cleanup for the CQPweb system.
 * 
 * All scripts should finish by calling this function.
 * 
 * One thing that it will not do is finish off HTML. 
 * The script should do that separately -- BEFORE calling this function.
 * 
 * Note that it DOES return - it does not actually exit.
 * 
 * Pass in true if we are coming from an error condition - in which case,
 * we do not want to allow the PHP process to work a shift
 * on the jobqueue.  
 */
function cqpweb_shutdown_environment(bool $block_jobqueue_shift = false) : void
{
	global $Config;

	/* the shutdown actions all rely on info stored in $Config. 
	 * If we are asked to shutdown before $Config is created, 
	 * we can't really do any of them. */
	if (!is_object($Config))
		return;
	
	/* shutdown task: if we're in API mode, send off the data object to the API client */
	if ($Config->Api ?? false)
	{
		$Config->Api->dispatch();
		unset($Config->Api);
	}
			
	/* shutdown task: become a jobworker & work a shift if need be */
//	if (!$block_jobqueue_shift)
//		work_jobqueue_shift();
// nb, the aboveneeds to be after API dispatch, but before shutdown of connections.

	/* shutdown task: disconnect from the cqp process and the sql daemon. */
	/* these methods on $Config have their own "if" clauses so can be called here unconditionally... */
	$Config->disconnect_slave_program('cqp');
	$Config->disconnect_slave_program('sql');
}

