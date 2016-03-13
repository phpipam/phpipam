<?php
/**
*  Class for parsing Excel formulas
*
*  License Information:
*
*    Spreadsheet_Excel_Writer:  A library for generating Excel Spreadsheets
*    Copyright (c) 2002-2003 Xavier Noguer xnoguer@rezebra.com
*
*    This library is free software; you can redistribute it and/or
*    modify it under the terms of the GNU Lesser General Public
*    License as published by the Free Software Foundation; either
*    version 2.1 of the License, or (at your option) any later version.
*
*    This library is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
*    Lesser General Public License for more details.
*
*    You should have received a copy of the GNU Lesser General Public
*    License along with this library; if not, write to the Free Software
*    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/**
* @const SPREADSHEET_EXCEL_WRITER_ADD token identifier for character "+"
*/
define('SPREADSHEET_EXCEL_WRITER_ADD', "+");

/**
* @const SPREADSHEET_EXCEL_WRITER_SUB token identifier for character "-"
*/
define('SPREADSHEET_EXCEL_WRITER_SUB', "-");

/**
* @const SPREADSHEET_EXCEL_WRITER_MUL token identifier for character "*"
*/
define('SPREADSHEET_EXCEL_WRITER_MUL', "*");

/**
* @const SPREADSHEET_EXCEL_WRITER_DIV token identifier for character "/"
*/
define('SPREADSHEET_EXCEL_WRITER_DIV', "/");

/**
* @const SPREADSHEET_EXCEL_WRITER_OPEN token identifier for character "("
*/
define('SPREADSHEET_EXCEL_WRITER_OPEN', "(");

/**
* @const SPREADSHEET_EXCEL_WRITER_CLOSE token identifier for character ")"
*/
define('SPREADSHEET_EXCEL_WRITER_CLOSE', ")");

/**
* @const SPREADSHEET_EXCEL_WRITER_COMA token identifier for character ","
*/
define('SPREADSHEET_EXCEL_WRITER_COMA', ",");

/**
* @const SPREADSHEET_EXCEL_WRITER_SEMICOLON token identifier for character ";"
*/
define('SPREADSHEET_EXCEL_WRITER_SEMICOLON', ";");

/**
* @const SPREADSHEET_EXCEL_WRITER_GT token identifier for character ">"
*/
define('SPREADSHEET_EXCEL_WRITER_GT', ">");

/**
* @const SPREADSHEET_EXCEL_WRITER_LT token identifier for character "<"
*/
define('SPREADSHEET_EXCEL_WRITER_LT', "<");

/**
* @const SPREADSHEET_EXCEL_WRITER_LE token identifier for character "<="
*/
define('SPREADSHEET_EXCEL_WRITER_LE', "<=");

/**
* @const SPREADSHEET_EXCEL_WRITER_GE token identifier for character ">="
*/
define('SPREADSHEET_EXCEL_WRITER_GE', ">=");

/**
* @const SPREADSHEET_EXCEL_WRITER_EQ token identifier for character "="
*/
define('SPREADSHEET_EXCEL_WRITER_EQ', "=");

/**
* @const SPREADSHEET_EXCEL_WRITER_NE token identifier for character "<>"
*/
define('SPREADSHEET_EXCEL_WRITER_NE', "<>");

/**
* * @const SPREADSHEET_EXCEL_WRITER_CONCAT token identifier for character "&"
*/
define('SPREADSHEET_EXCEL_WRITER_CONCAT', "&");

require_once 'PEAR.php';

/**
* Class for parsing Excel formulas
*
* @author   Xavier Noguer <xnoguer@rezebra.com>
* @category FileFormats
* @package  Spreadsheet_Excel_Writer
*/

class Spreadsheet_Excel_Writer_Parser extends PEAR
{
    /**
    * The index of the character we are currently looking at
    * @var integer
    */
    var $_current_char;

    /**
    * The token we are working on.
    * @var string
    */
    var $_current_token;

    /**
    * The formula to parse
    * @var string
    */
    var $_formula;

    /**
    * The character ahead of the current char
    * @var string
    */
    var $_lookahead;

    /**
    * The parse tree to be generated
    * @var string
    */
    var $_parse_tree;

    /**
    * The byte order. 1 => big endian, 0 => little endian.
    * @var integer
    */
    var $_byte_order;

    /**
    * Array of external sheets
    * @var array
    */
    var $_ext_sheets;

    /**
    * Array of sheet references in the form of REF structures
    * @var array
    */
    var $_references;

    /**
    * The BIFF version for the workbook
    * @var integer
    */
    var $_BIFF_version;

    /**
    * The class constructor
    *
    * @param integer $byte_order The byte order (Little endian or Big endian) of the architecture
                                 (optional). 1 => big endian, 0 (default) little endian.
    */
    function Spreadsheet_Excel_Writer_Parser($byte_order, $biff_version)
    {
        $this->_current_char  = 0;
        $this->_BIFF_version  = $biff_version;
        $this->_current_token = '';       // The token we are working on.
        $this->_formula       = '';       // The formula to parse.
        $this->_lookahead     = '';       // The character ahead of the current char.
        $this->_parse_tree    = '';       // The parse tree to be generated.
        $this->_initializeHashes();      // Initialize the hashes: ptg's and function's ptg's
        $this->_byte_order = $byte_order; // Little Endian or Big Endian
        $this->_ext_sheets = array();
        $this->_references = array();
    }

    /**
    * Initialize the ptg and function hashes.
    *
    * @access private
    */
    function _initializeHashes()
    {
        // The Excel ptg indices
        $this->ptg = array(
            'ptgExp'       => 0x01,
            'ptgTbl'       => 0x02,
            'ptgAdd'       => 0x03,
            'ptgSub'       => 0x04,
            'ptgMul'       => 0x05,
            'ptgDiv'       => 0x06,
            'ptgPower'     => 0x07,
            'ptgConcat'    => 0x08,
            'ptgLT'        => 0x09,
            'ptgLE'        => 0x0A,
            'ptgEQ'        => 0x0B,
            'ptgGE'        => 0x0C,
            'ptgGT'        => 0x0D,
            'ptgNE'        => 0x0E,
            'ptgIsect'     => 0x0F,
            'ptgUnion'     => 0x10,
            'ptgRange'     => 0x11,
            'ptgUplus'     => 0x12,
            'ptgUminus'    => 0x13,
            'ptgPercent'   => 0x14,
            'ptgParen'     => 0x15,
            'ptgMissArg'   => 0x16,
            'ptgStr'       => 0x17,
            'ptgAttr'      => 0x19,
            'ptgSheet'     => 0x1A,
            'ptgEndSheet'  => 0x1B,
            'ptgErr'       => 0x1C,
            'ptgBool'      => 0x1D,
            'ptgInt'       => 0x1E,
            'ptgNum'       => 0x1F,
            'ptgArray'     => 0x20,
            'ptgFunc'      => 0x21,
            'ptgFuncVar'   => 0x22,
            'ptgName'      => 0x23,
            'ptgRef'       => 0x24,
            'ptgArea'      => 0x25,
            'ptgMemArea'   => 0x26,
            'ptgMemErr'    => 0x27,
            'ptgMemNoMem'  => 0x28,
            'ptgMemFunc'   => 0x29,
            'ptgRefErr'    => 0x2A,
            'ptgAreaErr'   => 0x2B,
            'ptgRefN'      => 0x2C,
            'ptgAreaN'     => 0x2D,
            'ptgMemAreaN'  => 0x2E,
            'ptgMemNoMemN' => 0x2F,
            'ptgNameX'     => 0x39,
            'ptgRef3d'     => 0x3A,
            'ptgArea3d'    => 0x3B,
            'ptgRefErr3d'  => 0x3C,
            'ptgAreaErr3d' => 0x3D,
            'ptgArrayV'    => 0x40,
            'ptgFuncV'     => 0x41,
            'ptgFuncVarV'  => 0x42,
            'ptgNameV'     => 0x43,
            'ptgRefV'      => 0x44,
            'ptgAreaV'     => 0x45,
            'ptgMemAreaV'  => 0x46,
            'ptgMemErrV'   => 0x47,
            'ptgMemNoMemV' => 0x48,
            'ptgMemFuncV'  => 0x49,
            'ptgRefErrV'   => 0x4A,
            'ptgAreaErrV'  => 0x4B,
            'ptgRefNV'     => 0x4C,
            'ptgAreaNV'    => 0x4D,
            'ptgMemAreaNV' => 0x4E,
            'ptgMemNoMemN' => 0x4F,
            'ptgFuncCEV'   => 0x58,
            'ptgNameXV'    => 0x59,
            'ptgRef3dV'    => 0x5A,
            'ptgArea3dV'   => 0x5B,
            'ptgRefErr3dV' => 0x5C,
            'ptgAreaErr3d' => 0x5D,
            'ptgArrayA'    => 0x60,
            'ptgFuncA'     => 0x61,
            'ptgFuncVarA'  => 0x62,
            'ptgNameA'     => 0x63,
            'ptgRefA'      => 0x64,
            'ptgAreaA'     => 0x65,
            'ptgMemAreaA'  => 0x66,
            'ptgMemErrA'   => 0x67,
            'ptgMemNoMemA' => 0x68,
            'ptgMemFuncA'  => 0x69,
            'ptgRefErrA'   => 0x6A,
            'ptgAreaErrA'  => 0x6B,
            'ptgRefNA'     => 0x6C,
            'ptgAreaNA'    => 0x6D,
            'ptgMemAreaNA' => 0x6E,
            'ptgMemNoMemN' => 0x6F,
            'ptgFuncCEA'   => 0x78,
            'ptgNameXA'    => 0x79,
            'ptgRef3dA'    => 0x7A,
            'ptgArea3dA'   => 0x7B,
            'ptgRefErr3dA' => 0x7C,
            'ptgAreaErr3d' => 0x7D
            );

        // Thanks to Michael Meeks and Gnumeric for the initial arg values.
        //
        // The following hash was generated by "function_locale.pl" in the distro.
        // Refer to function_locale.pl for non-English function names.
        //
        // The array elements are as follow:
        // ptg:   The Excel function ptg code.
        // args:  The number of arguments that the function takes:
        //           >=0 is a fixed number of arguments.
        //           -1  is a variable  number of arguments.
        // class: The reference, value or array class of the function args.
        // vol:   The function is volatile.
        //
        $this->_functions = array(
              // function                  ptg  args  class  vol
              'COUNT'           => array(   0,   -1,    0,    0 ),
              'IF'              => array(   1,   -1,    1,    0 ),
              'ISNA'            => array(   2,    1,    1,    0 ),
              'ISERROR'         => array(   3,    1,    1,    0 ),
              'SUM'             => array(   4,   -1,    0,    0 ),
              'AVERAGE'         => array(   5,   -1,    0,    0 ),
              'MIN'             => array(   6,   -1,    0,    0 ),
              'MAX'             => array(   7,   -1,    0,    0 ),
              'ROW'             => array(   8,   -1,    0,    0 ),
              'COLUMN'          => array(   9,   -1,    0,    0 ),
              'NA'              => array(  10,    0,    0,    0 ),
              'NPV'             => array(  11,   -1,    1,    0 ),
              'STDEV'           => array(  12,   -1,    0,    0 ),
              'DOLLAR'          => array(  13,   -1,    1,    0 ),
              'FIXED'           => array(  14,   -1,    1,    0 ),
              'SIN'             => array(  15,    1,    1,    0 ),
              'COS'             => array(  16,    1,    1,    0 ),
              'TAN'             => array(  17,    1,    1,    0 ),
              'ATAN'            => array(  18,    1,    1,    0 ),
              'PI'              => array(  19,    0,    1,    0 ),
              'SQRT'            => array(  20,    1,    1,    0 ),
              'EXP'             => array(  21,    1,    1,    0 ),
              'LN'              => array(  22,    1,    1,    0 ),
              'LOG10'           => array(  23,    1,    1,    0 ),
              'ABS'             => array(  24,    1,    1,    0 ),
              'INT'             => array(  25,    1,    1,    0 ),
              'SIGN'            => array(  26,    1,    1,    0 ),
              'ROUND'           => array(  27,    2,    1,    0 ),
              'LOOKUP'          => array(  28,   -1,    0,    0 ),
              'INDEX'           => array(  29,   -1,    0,    1 ),
              'REPT'            => array(  30,    2,    1,    0 ),
              'MID'             => array(  31,    3,    1,    0 ),
              'LEN'             => array(  32,    1,    1,    0 ),
              'VALUE'           => array(  33,    1,    1,    0 ),
              'TRUE'            => array(  34,    0,    1,    0 ),
              'FALSE'           => array(  35,    0,    1,    0 ),
              'AND'             => array(  36,   -1,    0,    0 ),
              'OR'              => array(  37,   -1,    0,    0 ),
              'NOT'             => array(  38,    1,    1,    0 ),
              'MOD'             => array(  39,    2,    1,    0 ),
              'DCOUNT'          => array(  40,    3,    0,    0 ),
              'DSUM'            => array(  41,    3,    0,    0 ),
              'DAVERAGE'        => array(  42,    3,    0,    0 ),
              'DMIN'            => array(  43,    3,    0,    0 ),
              'DMAX'            => array(  44,    3,    0,    0 ),
              'DSTDEV'          => array(  45,    3,    0,    0 ),
              'VAR'             => array(  46,   -1,    0,    0 ),
              'DVAR'            => array(  47,    3,    0,    0 ),
              'TEXT'            => array(  48,    2,    1,    0 ),
              'LINEST'          => array(  49,   -1,    0,    0 ),
              'TREND'           => array(  50,   -1,    0,    0 ),
              'LOGEST'          => array(  51,   -1,    0,    0 ),
              'GROWTH'          => array(  52,   -1,    0,    0 ),
              'PV'              => array(  56,   -1,    1,    0 ),
              'FV'              => array(  57,   -1,    1,    0 ),
              'NPER'            => array(  58,   -1,    1,    0 ),
              'PMT'             => array(  59,   -1,    1,    0 ),
              'RATE'            => array(  60,   -1,    1,    0 ),
              'MIRR'            => array(  61,    3,    0,    0 ),
              'IRR'             => array(  62,   -1,    0,    0 ),
              'RAND'            => array(  63,    0,    1,    1 ),
              'MATCH'           => array(  64,   -1,    0,    0 ),
              'DATE'            => array(  65,    3,    1,    0 ),
              'TIME'            => array(  66,    3,    1,    0 ),
              'DAY'             => array(  67,    1,    1,    0 ),
              'MONTH'           => array(  68,    1,    1,    0 ),
              'YEAR'            => array(  69,    1,    1,    0 ),
              'WEEKDAY'         => array(  70,   -1,    1,    0 ),
              'HOUR'            => array(  71,    1,    1,    0 ),
              'MINUTE'          => array(  72,    1,    1,    0 ),
              'SECOND'          => array(  73,    1,    1,    0 ),
              'NOW'             => array(  74,    0,    1,    1 ),
              'AREAS'           => array(  75,    1,    0,    1 ),
              'ROWS'            => array(  76,    1,    0,    1 ),
              'COLUMNS'         => array(  77,    1,    0,    1 ),
              'OFFSET'          => array(  78,   -1,    0,    1 ),
              'SEARCH'          => array(  82,   -1,    1,    0 ),
              'TRANSPOSE'       => array(  83,    1,    1,    0 ),
              'TYPE'            => array(  86,    1,    1,    0 ),
              'ATAN2'           => array(  97,    2,    1,    0 ),
              'ASIN'            => array(  98,    1,    1,    0 ),
              'ACOS'            => array(  99,    1,    1,    0 ),
              'CHOOSE'          => array( 100,   -1,    1,    0 ),
              'HLOOKUP'         => array( 101,   -1,    0,    0 ),
              'VLOOKUP'         => array( 102,   -1,    0,    0 ),
              'ISREF'           => array( 105,    1,    0,    0 ),
              'LOG'             => array( 109,   -1,    1,    0 ),
              'CHAR'            => array( 111,    1,    1,    0 ),
              'LOWER'           => array( 112,    1,    1,    0 ),
              'UPPER'           => array( 113,    1,    1,    0 ),
              'PROPER'          => array( 114,    1,    1,    0 ),
              'LEFT'            => array( 115,   -1,    1,    0 ),
              'RIGHT'           => array( 116,   -1,    1,    0 ),
              'EXACT'           => array( 117,    2,    1,    0 ),
              'TRIM'            => array( 118,    1,    1,    0 ),
              'REPLACE'         => array( 119,    4,    1,    0 ),
              'SUBSTITUTE'      => array( 120,   -1,    1,    0 ),
              'CODE'            => array( 121,    1,    1,    0 ),
              'FIND'            => array( 124,   -1,    1,    0 ),
              'CELL'            => array( 125,   -1,    0,    1 ),
              'ISERR'           => array( 126,    1,    1,    0 ),
              'ISTEXT'          => array( 127,    1,    1,    0 ),
              'ISNUMBER'        => array( 128,    1,    1,    0 ),
              'ISBLANK'         => array( 129,    1,    1,    0 ),
              'T'               => array( 130,    1,    0,    0 ),
              'N'               => array( 131,    1,    0,    0 ),
              'DATEVALUE'       => array( 140,    1,    1,    0 ),
              'TIMEVALUE'       => array( 141,    1,    1,    0 ),
              'SLN'             => array( 142,    3,    1,    0 ),
              'SYD'             => array( 143,    4,    1,    0 ),
              'DDB'             => array( 144,   -1,    1,    0 ),
              'INDIRECT'        => array( 148,   -1,    1,    1 ),
              'CALL'            => array( 150,   -1,    1,    0 ),
              'CLEAN'           => array( 162,    1,    1,    0 ),
              'MDETERM'         => array( 163,    1,    2,    0 ),
              'MINVERSE'        => array( 164,    1,    2,    0 ),
              'MMULT'           => array( 165,    2,    2,    0 ),
              'IPMT'            => array( 167,   -1,    1,    0 ),
              'PPMT'            => array( 168,   -1,    1,    0 ),
              'COUNTA'          => array( 169,   -1,    0,    0 ),
              'PRODUCT'         => array( 183,   -1,    0,    0 ),
              'FACT'            => array( 184,    1,    1,    0 ),
              'DPRODUCT'        => array( 189,    3,    0,    0 ),
              'ISNONTEXT'       => array( 190,    1,    1,    0 ),
              'STDEVP'          => array( 193,   -1,    0,    0 ),
              'VARP'            => array( 194,   -1,    0,    0 ),
              'DSTDEVP'         => array( 195,    3,    0,    0 ),
              'DVARP'           => array( 196,    3,    0,    0 ),
              'TRUNC'           => array( 197,   -1,    1,    0 ),
              'ISLOGICAL'       => array( 198,    1,    1,    0 ),
              'DCOUNTA'         => array( 199,    3,    0,    0 ),
              'ROUNDUP'         => array( 212,    2,    1,    0 ),
              'ROUNDDOWN'       => array( 213,    2,    1,    0 ),
              'RANK'            => array( 216,   -1,    0,    0 ),
              'ADDRESS'         => array( 219,   -1,    1,    0 ),
              'DAYS360'         => array( 220,   -1,    1,    0 ),
              'TODAY'           => array( 221,    0,    1,    1 ),
              'VDB'             => array( 222,   -1,    1,    0 ),
              'MEDIAN'          => array( 227,   -1,    0,    0 ),
              'SUMPRODUCT'      => array( 228,   -1,    2,    0 ),
              'SINH'            => array( 229,    1,    1,    0 ),
              'COSH'            => array( 230,    1,    1,    0 ),
              'TANH'            => array( 231,    1,    1,    0 ),
              'ASINH'           => array( 232,    1,    1,    0 ),
              'ACOSH'           => array( 233,    1,    1,    0 ),
              'ATANH'           => array( 234,    1,    1,    0 ),
              'DGET'            => array( 235,    3,    0,    0 ),
              'INFO'            => array( 244,    1,    1,    1 ),
              'DB'              => array( 247,   -1,    1,    0 ),
              'FREQUENCY'       => array( 252,    2,    0,    0 ),
              'ERROR.TYPE'      => array( 261,    1,    1,    0 ),
              'REGISTER.ID'     => array( 267,   -1,    1,    0 ),
              'AVEDEV'          => array( 269,   -1,    0,    0 ),
              'BETADIST'        => array( 270,   -1,    1,    0 ),
              'GAMMALN'         => array( 271,    1,    1,    0 ),
              'BETAINV'         => array( 272,   -1,    1,    0 ),
              'BINOMDIST'       => array( 273,    4,    1,    0 ),
              'CHIDIST'         => array( 274,    2,    1,    0 ),
              'CHIINV'          => array( 275,    2,    1,    0 ),
              'COMBIN'          => array( 276,    2,    1,    0 ),
              'CONFIDENCE'      => array( 277,    3,    1,    0 ),
              'CRITBINOM'       => array( 278,    3,    1,    0 ),
              'EVEN'            => array( 279,    1,    1,    0 ),
              'EXPONDIST'       => array( 280,    3,    1,    0 ),
              'FDIST'           => array( 281,    3,    1,    0 ),
              'FINV'            => array( 282,    3,    1,    0 ),
              'FISHER'          => array( 283,    1,    1,    0 ),
              'FISHERINV'       => array( 284,    1,    1,    0 ),
              'FLOOR'           => array( 285,    2,    1,    0 ),
              'GAMMADIST'       => array( 286,    4,    1,    0 ),
              'GAMMAINV'        => array( 287,    3,    1,    0 ),
              'CEILING'         => array( 288,    2,    1,    0 ),
              'HYPGEOMDIST'     => array( 289,    4,    1,    0 ),
              'LOGNORMDIST'     => array( 290,    3,    1,    0 ),
              'LOGINV'          => array( 291,    3,    1,    0 ),
              'NEGBINOMDIST'    => array( 292,    3,    1,    0 ),
              'NORMDIST'        => array( 293,    4,    1,    0 ),
              'NORMSDIST'       => array( 294,    1,    1,    0 ),
              'NORMINV'         => array( 295,    3,    1,    0 ),
              'NORMSINV'        => array( 296,    1,    1,    0 ),
              'STANDARDIZE'     => array( 297,    3,    1,    0 ),
              'ODD'             => array( 298,    1,    1,    0 ),
              'PERMUT'          => array( 299,    2,    1,    0 ),
              'POISSON'         => array( 300,    3,    1,    0 ),
              'TDIST'           => array( 301,    3,    1,    0 ),
              'WEIBULL'         => array( 302,    4,    1,    0 ),
              'SUMXMY2'         => array( 303,    2,    2,    0 ),
              'SUMX2MY2'        => array( 304,    2,    2,    0 ),
              'SUMX2PY2'        => array( 305,    2,    2,    0 ),
              'CHITEST'         => array( 306,    2,    2,    0 ),
              'CORREL'          => array( 307,    2,    2,    0 ),
              'COVAR'           => array( 308,    2,    2,    0 ),
              'FORECAST'        => array( 309,    3,    2,    0 ),
              'FTEST'           => array( 310,    2,    2,    0 ),
              'INTERCEPT'       => array( 311,    2,    2,    0 ),
              'PEARSON'         => array( 312,    2,    2,    0 ),
              'RSQ'             => array( 313,    2,    2,    0 ),
              'STEYX'           => array( 314,    2,    2,    0 ),
              'SLOPE'           => array( 315,    2,    2,    0 ),
              'TTEST'           => array( 316,    4,    2,    0 ),
              'PROB'            => array( 317,   -1,    2,    0 ),
              'DEVSQ'           => array( 318,   -1,    0,    0 ),
              'GEOMEAN'         => array( 319,   -1,    0,    0 ),
              'HARMEAN'         => array( 320,   -1,    0,    0 ),
              'SUMSQ'           => array( 321,   -1,    0,    0 ),
              'KURT'            => array( 322,   -1,    0,    0 ),
              'SKEW'            => array( 323,   -1,    0,    0 ),
              'ZTEST'           => array( 324,   -1,    0,    0 ),
              'LARGE'           => array( 325,    2,    0,    0 ),
              'SMALL'           => array( 326,    2,    0,    0 ),
              'QUARTILE'        => array( 327,    2,    0,    0 ),
              'PERCENTILE'      => array( 328,    2,    0,    0 ),
              'PERCENTRANK'     => array( 329,   -1,    0,    0 ),
              'MODE'            => array( 330,   -1,    2,    0 ),
              'TRIMMEAN'        => array( 331,    2,    0,    0 ),
              'TINV'            => array( 332,    2,    1,    0 ),
              'CONCATENATE'     => array( 336,   -1,    1,    0 ),
              'POWER'           => array( 337,    2,    1,    0 ),
              'RADIANS'         => array( 342,    1,    1,    0 ),
              'DEGREES'         => array( 343,    1,    1,    0 ),
              'SUBTOTAL'        => array( 344,   -1,    0,    0 ),
              'SUMIF'           => array( 345,   -1,    0,    0 ),
              'COUNTIF'         => array( 346,    2,    0,    0 ),
              'COUNTBLANK'      => array( 347,    1,    0,    0 ),
              'ROMAN'           => array( 354,   -1,    1,    0 )
              );
    }

    /**
    * Convert a token to the proper ptg value.
    *
    * @access private
    * @param mixed $token The token to convert.
    * @return mixed the converted token on success. PEAR_Error if the token
    *               is not recognized
    */
    function _convert($token)
    {
        if (preg_match("/^\"[^\"]{0,255}\"$/", $token)) {
            return $this->_convertString($token);

        } elseif (is_numeric($token)) {
            return $this->_convertNumber($token);

        // match references like A1 or $A$1
        } elseif (preg_match('/^\$?([A-Ia-i]?[A-Za-z])\$?(\d+)$/',$token)) {
            return $this->_convertRef2d($token);

        // match external references like Sheet1!A1 or Sheet1:Sheet2!A1
        } elseif (preg_match("/^\w+(\:\w+)?\![A-Ia-i]?[A-Za-z](\d+)$/u",$token)) {
            return $this->_convertRef3d($token);

        // match external references like 'Sheet1'!A1 or 'Sheet1:Sheet2'!A1
        } elseif (preg_match("/^'[\w -]+(\:[\w -]+)?'\![A-Ia-i]?[A-Za-z](\d+)$/u",$token)) {
            return $this->_convertRef3d($token);

        // match ranges like A1:B2
        } elseif (preg_match("/^(\$)?[A-Ia-i]?[A-Za-z](\$)?(\d+)\:(\$)?[A-Ia-i]?[A-Za-z](\$)?(\d+)$/",$token)) {
            return $this->_convertRange2d($token);

        // match ranges like A1..B2
        } elseif (preg_match("/^(\$)?[A-Ia-i]?[A-Za-z](\$)?(\d+)\.\.(\$)?[A-Ia-i]?[A-Za-z](\$)?(\d+)$/",$token)) {
            return $this->_convertRange2d($token);

        // match external ranges like Sheet1!A1 or Sheet1:Sheet2!A1:B2
        } elseif (preg_match("/^\w+(\:\w+)?\!([A-Ia-i]?[A-Za-z])?(\d+)\:([A-Ia-i]?[A-Za-z])?(\d+)$/u",$token)) {
            return $this->_convertRange3d($token);

        // match external ranges like 'Sheet1'!A1 or 'Sheet1:Sheet2'!A1:B2
        } elseif (preg_match("/^'[\w -]+(\:[\w -]+)?'\!([A-Ia-i]?[A-Za-z])?(\d+)\:([A-Ia-i]?[A-Za-z])?(\d+)$/u",$token)) {
            return $this->_convertRange3d($token);

        // operators (including parentheses)
        } elseif (isset($this->ptg[$token])) {
            return pack("C", $this->ptg[$token]);

        // commented so argument number can be processed correctly. See toReversePolish().
        /*elseif (preg_match("/[A-Z0-9\xc0-\xdc\.]+/",$token))
        {
            return($this->_convertFunction($token,$this->_func_args));
        }*/

        // if it's an argument, ignore the token (the argument remains)
        } elseif ($token == 'arg') {
            return '';
        }
        // TODO: use real error codes
        return $this->raiseError("Unknown token $token");
    }

    /**
    * Convert a number token to ptgInt or ptgNum
    *
    * @access private
    * @param mixed $num an integer or double for conversion to its ptg value
    */
    function _convertNumber($num)
    {
        // Integer in the range 0..2**16-1
        if ((preg_match("/^\d+$/", $num)) and ($num <= 65535)) {
            return pack("Cv", $this->ptg['ptgInt'], $num);
        } else { // A float
            if ($this->_byte_order) { // if it's Big Endian
                $num = strrev($num);
            }
            return pack("Cd", $this->ptg['ptgNum'], $num);
        }
    }

    /**
    * Convert a string token to ptgStr
    *
    * @access private
    * @param string $string A string for conversion to its ptg value.
    * @return mixed the converted token on success. PEAR_Error if the string
    *               is longer than 255 characters.
    */
    function _convertString($string)
    {
        // chop away beggining and ending quotes
        $string = substr($string, 1, strlen($string) - 2);
        if (strlen($string) > 255) {
            return $this->raiseError("String is too long");
        }

        if ($this->_BIFF_version == 0x0500) {
            return pack("CC", $this->ptg['ptgStr'], strlen($string)).$string;
        } elseif ($this->_BIFF_version == 0x0600) {
            $encoding = 0;   // TODO: Unicode support
            return pack("CCC", $this->ptg['ptgStr'], strlen($string), $encoding).$string;
        }
    }

    /**
    * Convert a function to a ptgFunc or ptgFuncVarV depending on the number of
    * args that it takes.
    *
    * @access private
    * @param string  $token    The name of the function for convertion to ptg value.
    * @param integer $num_args The number of arguments the function receives.
    * @return string The packed ptg for the function
    */
    function _convertFunction($token, $num_args)
    {
        $args     = $this->_functions[$token][1];
        $volatile = $this->_functions[$token][3];

        // Fixed number of args eg. TIME($i,$j,$k).
        if ($args >= 0) {
            return pack("Cv", $this->ptg['ptgFuncV'], $this->_functions[$token][0]);
        }
        // Variable number of args eg. SUM($i,$j,$k, ..).
        if ($args == -1) {
            return pack("CCv", $this->ptg['ptgFuncVarV'], $num_args, $this->_functions[$token][0]);
        }
    }

    /**
    * Convert an Excel range such as A1:D4 to a ptgRefV.
    *
    * @access private
    * @param string $range An Excel range in the A1:A2 or A1..A2 format.
    */
    function _convertRange2d($range, $class=0)
    {

        // TODO: possible class value 0,1,2 check Formula.pm
        // Split the range into 2 cell refs
        if (preg_match("/^([A-Ia-i]?[A-Za-z])(\d+)\:([A-Ia-i]?[A-Za-z])(\d+)$/", $range)) {
            list($cell1, $cell2) = split(':', $range);
        } elseif (preg_match("/^([A-Ia-i]?[A-Za-z])(\d+)\.\.([A-Ia-i]?[A-Za-z])(\d+)$/", $range)) {
            list($cell1, $cell2) = split('\.\.', $range);

        } else {
            // TODO: use real error codes
            return $this->raiseError("Unknown range separator", 0, PEAR_ERROR_DIE);
        }

        // Convert the cell references
        $cell_array1 = $this->_cellToPackedRowcol($cell1);
        if (PEAR::isError($cell_array1)) {
            return $cell_array1;
        }
        list($row1, $col1) = $cell_array1;
        $cell_array2 = $this->_cellToPackedRowcol($cell2);
        if (PEAR::isError($cell_array2)) {
            return $cell_array2;
        }
        list($row2, $col2) = $cell_array2;

        // The ptg value depends on the class of the ptg.
        if ($class == 0) {
            $ptgArea = pack("C", $this->ptg['ptgArea']);
        } elseif ($class == 1) {
            $ptgArea = pack("C", $this->ptg['ptgAreaV']);
        } elseif ($class == 2) {
            $ptgArea = pack("C", $this->ptg['ptgAreaA']);
        } else {
            // TODO: use real error codes
            return $this->raiseError("Unknown class $class", 0, PEAR_ERROR_DIE);
        }
        return $ptgArea . $row1 . $row2 . $col1. $col2;
    }

    /**
    * Convert an Excel 3d range such as "Sheet1!A1:D4" or "Sheet1:Sheet2!A1:D4" to
    * a ptgArea3d.
    *
    * @access private
    * @param string $token An Excel range in the Sheet1!A1:A2 format.
    * @return mixed The packed ptgArea3d token on success, PEAR_Error on failure.
    */
    function _convertRange3d($token)
    {
        $class = 2; // as far as I know, this is magick.

        // Split the ref at the ! symbol
        list($ext_ref, $range) = split('!', $token);

        // Convert the external reference part (different for BIFF8)
        if ($this->_BIFF_version == 0x0500) {
            $ext_ref = $this->_packExtRef($ext_ref);
            if (PEAR::isError($ext_ref)) {
                return $ext_ref;
            }
        } elseif ($this->_BIFF_version == 0x0600) {
             $ext_ref = $this->_getRefIndex($ext_ref);
             if (PEAR::isError($ext_ref)) {
                 return $ext_ref;
             }
        }

        // Split the range into 2 cell refs
        list($cell1, $cell2) = split(':', $range);

        // Convert the cell references
        if (preg_match("/^(\$)?[A-Ia-i]?[A-Za-z](\$)?(\d+)$/", $cell1)) {
            $cell_array1 = $this->_cellToPackedRowcol($cell1);
            if (PEAR::isError($cell_array1)) {
                return $cell_array1;
            }
            list($row1, $col1) = $cell_array1;
            $cell_array2 = $this->_cellToPackedRowcol($cell2);
            if (PEAR::isError($cell_array2)) {
                return $cell_array2;
            }
            list($row2, $col2) = $cell_array2;
        } else { // It's a rows range (like 26:27)
             $cells_array = $this->_rangeToPackedRange($cell1.':'.$cell2);
             if (PEAR::isError($cells_array)) {
                 return $cells_array;
             }
             list($row1, $col1, $row2, $col2) = $cells_array;
        }

        // The ptg value depends on the class of the ptg.
        if ($class == 0) {
            $ptgArea = pack("C", $this->ptg['ptgArea3d']);
        } elseif ($class == 1) {
            $ptgArea = pack("C", $this->ptg['ptgArea3dV']);
        } elseif ($class == 2) {
            $ptgArea = pack("C", $this->ptg['ptgArea3dA']);
        } else {
            return $this->raiseError("Unknown class $class", 0, PEAR_ERROR_DIE);
        }

        return $ptgArea . $ext_ref . $row1 . $row2 . $col1. $col2;
    }

    /**
    * Convert an Excel reference such as A1, $B2, C$3 or $D$4 to a ptgRefV.
    *
    * @access private
    * @param string $cell An Excel cell reference
    * @return string The cell in packed() format with the corresponding ptg
    */
    function _convertRef2d($cell)
    {
        $class = 2; // as far as I know, this is magick.

        // Convert the cell reference
        $cell_array = $this->_cellToPackedRowcol($cell);
        if (PEAR::isError($cell_array)) {
            return $cell_array;
        }
        list($row, $col) = $cell_array;

        // The ptg value depends on the class of the ptg.
        if ($class == 0) {
            $ptgRef = pack("C", $this->ptg['ptgRef']);
        } elseif ($class == 1) {
            $ptgRef = pack("C", $this->ptg['ptgRefV']);
        } elseif ($class == 2) {
            $ptgRef = pack("C", $this->ptg['ptgRefA']);
        } else {
            // TODO: use real error codes
            return $this->raiseError("Unknown class $class");
        }
        return $ptgRef.$row.$col;
    }

    /**
    * Convert an Excel 3d reference such as "Sheet1!A1" or "Sheet1:Sheet2!A1" to a
    * ptgRef3d.
    *
    * @access private
    * @param string $cell An Excel cell reference
    * @return mixed The packed ptgRef3d token on success, PEAR_Error on failure.
    */
    function _convertRef3d($cell)
    {
        $class = 2; // as far as I know, this is magick.

        // Split the ref at the ! symbol
        list($ext_ref, $cell) = split('!', $cell);

        // Convert the external reference part (different for BIFF8)
        if ($this->_BIFF_version == 0x0500) {
            $ext_ref = $this->_packExtRef($ext_ref);
            if (PEAR::isError($ext_ref)) {
                return $ext_ref;
            }
        } elseif ($this->_BIFF_version == 0x0600) {
            $ext_ref = $this->_getRefIndex($ext_ref);
            if (PEAR::isError($ext_ref)) {
                return $ext_ref;
            }
        }

        // Convert the cell reference part
        list($row, $col) = $this->_cellToPackedRowcol($cell);

        // The ptg value depends on the class of the ptg.
        if ($class == 0) {
            $ptgRef = pack("C", $this->ptg['ptgRef3d']);
        } elseif ($class == 1) {
            $ptgRef = pack("C", $this->ptg['ptgRef3dV']);
        } elseif ($class == 2) {
            $ptgRef = pack("C", $this->ptg['ptgRef3dA']);
        } else {
            return $this->raiseError("Unknown class $class", 0, PEAR_ERROR_DIE);
        }

        return $ptgRef . $ext_ref. $row . $col;
    }

    /**
    * Convert the sheet name part of an external reference, for example "Sheet1" or
    * "Sheet1:Sheet2", to a packed structure.
    *
    * @access private
    * @param string $ext_ref The name of the external reference
    * @return string The reference index in packed() format
    */
    function _packExtRef($ext_ref)
    {
        $ext_ref = preg_replace("/^'/", '', $ext_ref); // Remove leading  ' if any.
        $ext_ref = preg_replace("/'$/", '', $ext_ref); // Remove trailing ' if any.

        // Check if there is a sheet range eg., Sheet1:Sheet2.
        if (preg_match("/:/", $ext_ref)) {
            list($sheet_name1, $sheet_name2) = split(':', $ext_ref);

            $sheet1 = $this->_getSheetIndex($sheet_name1);
            if ($sheet1 == -1) {
                return $this->raiseError("Unknown sheet name $sheet_name1 in formula");
            }
            $sheet2 = $this->_getSheetIndex($sheet_name2);
            if ($sheet2 == -1) {
                return $this->raiseError("Unknown sheet name $sheet_name2 in formula");
            }

            // Reverse max and min sheet numbers if necessary
            if ($sheet1 > $sheet2) {
                list($sheet1, $sheet2) = array($sheet2, $sheet1);
            }
        } else { // Single sheet name only.
            $sheet1 = $this->_getSheetIndex($ext_ref);
            if ($sheet1 == -1) {
                return $this->raiseError("Unknown sheet name $ext_ref in formula");
            }
            $sheet2 = $sheet1;
        }

        // References are stored relative to 0xFFFF.
        $offset = -1 - $sheet1;

        return pack('vdvv', $offset, 0x00, $sheet1, $sheet2);
    }

    /**
    * Look up the REF index that corresponds to an external sheet name
    * (or range). If it doesn't exist yet add it to the workbook's references
    * array. It assumes all sheet names given must exist.
    *
    * @access private
    * @param string $ext_ref The name of the external reference
    * @return mixed The reference index in packed() format on success,
    *               PEAR_Error on failure
    */
    function _getRefIndex($ext_ref)
    {
        $ext_ref = preg_replace("/^'/", '', $ext_ref); // Remove leading  ' if any.
        $ext_ref = preg_replace("/'$/", '', $ext_ref); // Remove trailing ' if any.

        // Check if there is a sheet range eg., Sheet1:Sheet2.
        if (preg_match("/:/", $ext_ref)) {
            list($sheet_name1, $sheet_name2) = split(':', $ext_ref);

            $sheet1 = $this->_getSheetIndex($sheet_name1);
            if ($sheet1 == -1) {
                return $this->raiseError("Unknown sheet name $sheet_name1 in formula");
            }
            $sheet2 = $this->_getSheetIndex($sheet_name2);
            if ($sheet2 == -1) {
                return $this->raiseError("Unknown sheet name $sheet_name2 in formula");
            }

            // Reverse max and min sheet numbers if necessary
            if ($sheet1 > $sheet2) {
                list($sheet1, $sheet2) = array($sheet2, $sheet1);
            }
        } else { // Single sheet name only.
            $sheet1 = $this->_getSheetIndex($ext_ref);
            if ($sheet1 == -1) {
                return $this->raiseError("Unknown sheet name $ext_ref in formula");
            }
            $sheet2 = $sheet1;
        }

        // assume all references belong to this document
        $supbook_index = 0x00;
        $ref = pack('vvv', $supbook_index, $sheet1, $sheet2);
        $total_references = count($this->_references);
        $index = -1;
        for ($i = 0; $i < $total_references; $i++) {
            if ($ref == $this->_references[$i]) {
                $index = $i;
                break;
            }
        }
        // if REF was not found add it to references array
        if ($index == -1) {
            $this->_references[$total_references] = $ref;
            $index = $total_references;
        }

        return pack('v', $index);
    }

    /**
    * Look up the index that corresponds to an external sheet name. The hash of
    * sheet names is updated by the addworksheet() method of the
    * Spreadsheet_Excel_Writer_Workbook class.
    *
    * @access private
    * @return integer The sheet index, -1 if the sheet was not found
    */
    function _getSheetIndex($sheet_name)
    {
        if (!isset($this->_ext_sheets[$sheet_name])) {
            return -1;
        } else {
            return $this->_ext_sheets[$sheet_name];
        }
    }

    /**
    * This method is used to update the array of sheet names. It is
    * called by the addWorksheet() method of the
    * Spreadsheet_Excel_Writer_Workbook class.
    *
    * @access public
    * @see Spreadsheet_Excel_Writer_Workbook::addWorksheet()
    * @param string  $name  The name of the worksheet being added
    * @param integer $index The index of the worksheet being added
    */
    function setExtSheet($name, $index)
    {
        $this->_ext_sheets[$name] = $index;
    }

    /**
    * pack() row and column into the required 3 or 4 byte format.
    *
    * @access private
    * @param string $cell The Excel cell reference to be packed
    * @return array Array containing the row and column in packed() format
    */
    function _cellToPackedRowcol($cell)
    {
        $cell = strtoupper($cell);
        list($row, $col, $row_rel, $col_rel) = $this->_cellToRowcol($cell);
        if ($col >= 256) {
            return $this->raiseError("Column in: $cell greater than 255");
        }
        // FIXME: change for BIFF8
        if ($row >= 16384) {
            return $this->raiseError("Row in: $cell greater than 16384 ");
        }

        // Set the high bits to indicate if row or col are relative.
        if ($this->_BIFF_version == 0x0500) {
            $row    |= $col_rel << 14;
            $row    |= $row_rel << 15;
            $col     = pack('C', $col);
        } elseif ($this->_BIFF_version == 0x0600) {
            $col    |= $col_rel << 14;
            $col    |= $row_rel << 15;
            $col     = pack('v', $col);
        }
        $row     = pack('v', $row);

        return array($row, $col);
    }

    /**
    * pack() row range into the required 3 or 4 byte format.
    * Just using maximum col/rows, which is probably not the correct solution
    *
    * @access private
    * @param string $range The Excel range to be packed
    * @return array Array containing (row1,col1,row2,col2) in packed() format
    */
    function _rangeToPackedRange($range)
    {
        preg_match('/(\$)?(\d+)\:(\$)?(\d+)/', $range, $match);
        // return absolute rows if there is a $ in the ref
        $row1_rel = empty($match[1]) ? 1 : 0;
        $row1     = $match[2];
        $row2_rel = empty($match[3]) ? 1 : 0;
        $row2     = $match[4];
        // Convert 1-index to zero-index
        $row1--;
        $row2--;
        // Trick poor inocent Excel
        $col1 = 0;
        $col2 = 16383; // FIXME: maximum possible value for Excel 5 (change this!!!)

        // FIXME: this changes for BIFF8
        if (($row1 >= 16384) or ($row2 >= 16384)) {
            return $this->raiseError("Row in: $range greater than 16384 ");
        }

        // Set the high bits to indicate if rows are relative.
        if ($this->_BIFF_version == 0x0500) {
            $row1    |= $row1_rel << 14; // FIXME: probably a bug
            $row2    |= $row2_rel << 15;
            $col1     = pack('C', $col1);
            $col2     = pack('C', $col2);
        } elseif ($this->_BIFF_version == 0x0600) {
            $col1    |= $row1_rel << 15;
            $col2    |= $row2_rel << 15;
            $col1     = pack('v', $col1);
            $col2     = pack('v', $col2);
        }
        $row1     = pack('v', $row1);
        $row2     = pack('v', $row2);

        return array($row1, $col1, $row2, $col2);
    }

    /**
    * Convert an Excel cell reference such as A1 or $B2 or C$3 or $D$4 to a zero
    * indexed row and column number. Also returns two (0,1) values to indicate
    * whether the row or column are relative references.
    *
    * @access private
    * @param string $cell The Excel cell reference in A1 format.
    * @return array
    */
    function _cellToRowcol($cell)
    {
        preg_match('/(\$)?([A-I]?[A-Z])(\$)?(\d+)/',$cell,$match);
        // return absolute column if there is a $ in the ref
        $col_rel = empty($match[1]) ? 1 : 0;
        $col_ref = $match[2];
        $row_rel = empty($match[3]) ? 1 : 0;
        $row     = $match[4];

        // Convert base26 column string to a number.
        $expn   = strlen($col_ref) - 1;
        $col    = 0;
        $col_ref_length = strlen($col_ref);
        for ($i = 0; $i < $col_ref_length; $i++) {
            $col += (ord($col_ref{$i}) - ord('A') + 1) * pow(26, $expn);
            $expn--;
        }

        // Convert 1-index to zero-index
        $row--;
        $col--;

        return array($row, $col, $row_rel, $col_rel);
    }

    /**
    * Advance to the next valid token.
    *
    * @access private
    */
    function _advance()
    {
        $i = $this->_current_char;
        $formula_length = strlen($this->_formula);
        // eat up white spaces
        if ($i < $formula_length) {
            while ($this->_formula{$i} == " ") {
                $i++;
            }

            if ($i < ($formula_length - 1)) {
                $this->_lookahead = $this->_formula{$i+1};
            }
            $token = '';
        }

        while ($i < $formula_length) {
            $token .= $this->_formula{$i};
            if ($i < ($formula_length - 1)) {
                $this->_lookahead = $this->_formula{$i+1};
            } else {
                $this->_lookahead = '';
            }

            if ($this->_match($token) != '') {
                //if ($i < strlen($this->_formula) - 1) {
                //    $this->_lookahead = $this->_formula{$i+1};
                //}
                $this->_current_char = $i + 1;
                $this->_current_token = $token;
                return 1;
            }

            if ($i < ($formula_length - 2)) {
                $this->_lookahead = $this->_formula{$i+2};
            } else { // if we run out of characters _lookahead becomes empty
                $this->_lookahead = '';
            }
            $i++;
        }
        //die("Lexical error ".$this->_current_char);
    }

    /**
    * Checks if it's a valid token.
    *
    * @access private
    * @param mixed $token The token to check.
    * @return mixed       The checked token or false on failure
    */
    function _match($token)
    {
        switch($token) {
            case SPREADSHEET_EXCEL_WRITER_ADD:
                return $token;
                break;
            case SPREADSHEET_EXCEL_WRITER_SUB:
                return $token;
                break;
            case SPREADSHEET_EXCEL_WRITER_MUL:
                return $token;
                break;
            case SPREADSHEET_EXCEL_WRITER_DIV:
                return $token;
                break;
            case SPREADSHEET_EXCEL_WRITER_OPEN:
                return $token;
                break;
            case SPREADSHEET_EXCEL_WRITER_CLOSE:
                return $token;
                break;
            case SPREADSHEET_EXCEL_WRITER_COMA:
                return $token;
                break;
            case SPREADSHEET_EXCEL_WRITER_SEMICOLON:
                return $token;
                break;
            case SPREADSHEET_EXCEL_WRITER_GT:
                if ($this->_lookahead == '=') { // it's a GE token
                    break;
                }
                return $token;
                break;
            case SPREADSHEET_EXCEL_WRITER_LT:
                // it's a LE or a NE token
                if (($this->_lookahead == '=') or ($this->_lookahead == '>')) {
                    break;
                }
                return $token;
                break;
            case SPREADSHEET_EXCEL_WRITER_GE:
                return $token;
                break;
            case SPREADSHEET_EXCEL_WRITER_LE:
                return $token;
                break;
            case SPREADSHEET_EXCEL_WRITER_EQ:
                return $token;
                break;
            case SPREADSHEET_EXCEL_WRITER_NE:
                return $token;
                break;
            case SPREADSHEET_EXCEL_WRITER_CONCAT:
                return $token;
                break;
            default:
                // if it's a reference
                if (preg_match('/^\$?[A-Ia-i]?[A-Za-z]\$?[0-9]+$/',$token) and
                   !preg_match("/[0-9]/",$this->_lookahead) and 
                   ($this->_lookahead != ':') and ($this->_lookahead != '.') and
                   ($this->_lookahead != '!'))
                {
                    return $token;
                }
                // If it's an external reference (Sheet1!A1 or Sheet1:Sheet2!A1)
                elseif (preg_match("/^\w+(\:\w+)?\![A-Ia-i]?[A-Za-z][0-9]+$/u",$token) and
                       !preg_match("/[0-9]/",$this->_lookahead) and
                       ($this->_lookahead != ':') and ($this->_lookahead != '.'))
                {
                    return $token;
                }
                // If it's an external reference ('Sheet1'!A1 or 'Sheet1:Sheet2'!A1)
                elseif (preg_match("/^'[\w -]+(\:[\w -]+)?'\![A-Ia-i]?[A-Za-z][0-9]+$/u",$token) and
                       !preg_match("/[0-9]/",$this->_lookahead) and
                       ($this->_lookahead != ':') and ($this->_lookahead != '.'))
                {
                    return $token;
                }
                // if it's a range (A1:A2)
                elseif (preg_match("/^(\$)?[A-Ia-i]?[A-Za-z](\$)?[0-9]+:(\$)?[A-Ia-i]?[A-Za-z](\$)?[0-9]+$/",$token) and 
                       !preg_match("/[0-9]/",$this->_lookahead))
                {
                    return $token;
                }
                // if it's a range (A1..A2)
                elseif (preg_match("/^(\$)?[A-Ia-i]?[A-Za-z](\$)?[0-9]+\.\.(\$)?[A-Ia-i]?[A-Za-z](\$)?[0-9]+$/",$token) and 
                       !preg_match("/[0-9]/",$this->_lookahead))
                {
                    return $token;
                }
                // If it's an external range like Sheet1!A1 or Sheet1:Sheet2!A1:B2
                elseif (preg_match("/^\w+(\:\w+)?\!([A-Ia-i]?[A-Za-z])?[0-9]+:([A-Ia-i]?[A-Za-z])?[0-9]+$/u",$token) and
                       !preg_match("/[0-9]/",$this->_lookahead))
                {
                    return $token;
                }
                // If it's an external range like 'Sheet1'!A1 or 'Sheet1:Sheet2'!A1:B2
                elseif (preg_match("/^'[\w -]+(\:[\w -]+)?'\!([A-Ia-i]?[A-Za-z])?[0-9]+:([A-Ia-i]?[A-Za-z])?[0-9]+$/u",$token) and
                       !preg_match("/[0-9]/",$this->_lookahead))
                {
                    return $token;
                }
                // If it's a number (check that it's not a sheet name or range)
                elseif (is_numeric($token) and 
                        (!is_numeric($token.$this->_lookahead) or ($this->_lookahead == '')) and
                        ($this->_lookahead != '!') and ($this->_lookahead != ':'))
                {
                    return $token;
                }
                // If it's a string (of maximum 255 characters)
                elseif (preg_match("/^\"[^\"]{0,255}\"$/",$token))
                {
                    return $token;
                }
                // if it's a function call
                elseif (preg_match("/^[A-Z0-9\xc0-\xdc\.]+$/i",$token) and ($this->_lookahead == "("))
                {
                    return $token;
                }
                return '';
        }
    }

    /**
    * The parsing method. It parses a formula.
    *
    * @access public
    * @param string $formula The formula to parse, without the initial equal
    *                        sign (=).
    * @return mixed true on success, PEAR_Error on failure
    */
    function parse($formula)
    {
        $this->_current_char = 0;
        $this->_formula      = $formula;
        $this->_lookahead    = $formula{1};
        $this->_advance();
        $this->_parse_tree   = $this->_condition();
        if (PEAR::isError($this->_parse_tree)) {
            return $this->_parse_tree;
        }
        return true;
    }

    /**
    * It parses a condition. It assumes the following rule:
    * Cond -> Expr [(">" | "<") Expr]
    *
    * @access private
    * @return mixed The parsed ptg'd tree on success, PEAR_Error on failure
    */
    function _condition()
    {
        $result = $this->_expression();
        if (PEAR::isError($result)) {
            return $result;
        }
        if ($this->_current_token == SPREADSHEET_EXCEL_WRITER_LT) {
            $this->_advance();
            $result2 = $this->_expression();
            if (PEAR::isError($result2)) {
                return $result2;
            }
            $result = $this->_createTree('ptgLT', $result, $result2);
        } elseif ($this->_current_token == SPREADSHEET_EXCEL_WRITER_GT) {
            $this->_advance();
            $result2 = $this->_expression();
            if (PEAR::isError($result2)) {
                return $result2;
            }
            $result = $this->_createTree('ptgGT', $result, $result2);
        } elseif ($this->_current_token == SPREADSHEET_EXCEL_WRITER_LE) {
            $this->_advance();
            $result2 = $this->_expression();
            if (PEAR::isError($result2)) {
                return $result2;
            }
            $result = $this->_createTree('ptgLE', $result, $result2);
        } elseif ($this->_current_token == SPREADSHEET_EXCEL_WRITER_GE) {
            $this->_advance();
            $result2 = $this->_expression();
            if (PEAR::isError($result2)) {
                return $result2;
            }
            $result = $this->_createTree('ptgGE', $result, $result2);
        } elseif ($this->_current_token == SPREADSHEET_EXCEL_WRITER_EQ) {
            $this->_advance();
            $result2 = $this->_expression();
            if (PEAR::isError($result2)) {
                return $result2;
            }
            $result = $this->_createTree('ptgEQ', $result, $result2);
        } elseif ($this->_current_token == SPREADSHEET_EXCEL_WRITER_NE) {
            $this->_advance();
            $result2 = $this->_expression();
            if (PEAR::isError($result2)) {
                return $result2;
            }
            $result = $this->_createTree('ptgNE', $result, $result2);
        } elseif ($this->_current_token == SPREADSHEET_EXCEL_WRITER_CONCAT) {
            $this->_advance();
            $result2 = $this->_expression();
            if (PEAR::isError($result2)) {
                return $result2;
        }
            $result = $this->_createTree('ptgConcat', $result, $result2);
        }
        return $result;
    }

    /**
    * It parses a expression. It assumes the following rule:
    * Expr -> Term [("+" | "-") Term]
    *      -> "string"
    *      -> "-" Term
    *
    * @access private
    * @return mixed The parsed ptg'd tree on success, PEAR_Error on failure
    */
    function _expression()
    {
        // If it's a string return a string node
        if (preg_match("/^\"[^\"]{0,255}\"$/", $this->_current_token)) {
            $result = $this->_createTree($this->_current_token, '', '');
            $this->_advance();
            return $result;
        } elseif ($this->_current_token == SPREADSHEET_EXCEL_WRITER_SUB) {
            // catch "-" Term
            $this->_advance();
            $result2 = $this->_expression();
            $result = $this->_createTree('ptgUminus', $result2, '');
            return $result;
        }
        $result = $this->_term();
        if (PEAR::isError($result)) {
            return $result;
        }
        while (($this->_current_token == SPREADSHEET_EXCEL_WRITER_ADD) or
               ($this->_current_token == SPREADSHEET_EXCEL_WRITER_SUB)) {
        /**/
            if ($this->_current_token == SPREADSHEET_EXCEL_WRITER_ADD) {
                $this->_advance();
                $result2 = $this->_term();
                if (PEAR::isError($result2)) {
                    return $result2;
                }
                $result = $this->_createTree('ptgAdd', $result, $result2);
            } else {
                $this->_advance();
                $result2 = $this->_term();
                if (PEAR::isError($result2)) {
                    return $result2;
                }
                $result = $this->_createTree('ptgSub', $result, $result2);
            }
        }
        return $result;
    }

    /**
    * This function just introduces a ptgParen element in the tree, so that Excel
    * doesn't get confused when working with a parenthesized formula afterwards.
    *
    * @access private
    * @see _fact()
    * @return array The parsed ptg'd tree
    */
    function _parenthesizedExpression()
    {
        $result = $this->_createTree('ptgParen', $this->_expression(), '');
        return $result;
    }

    /**
    * It parses a term. It assumes the following rule:
    * Term -> Fact [("*" | "/") Fact]
    *
    * @access private
    * @return mixed The parsed ptg'd tree on success, PEAR_Error on failure
    */
    function _term()
    {
        $result = $this->_fact();
        if (PEAR::isError($result)) {
            return $result;
        }
        while (($this->_current_token == SPREADSHEET_EXCEL_WRITER_MUL) or
               ($this->_current_token == SPREADSHEET_EXCEL_WRITER_DIV)) {
        /**/
            if ($this->_current_token == SPREADSHEET_EXCEL_WRITER_MUL) {
                $this->_advance();
                $result2 = $this->_fact();
                if (PEAR::isError($result2)) {
                    return $result2;
                }
                $result = $this->_createTree('ptgMul', $result, $result2);
            } else {
                $this->_advance();
                $result2 = $this->_fact();
                if (PEAR::isError($result2)) {
                    return $result2;
                }
                $result = $this->_createTree('ptgDiv', $result, $result2);
            }
        }
        return $result;
    }

    /**
    * It parses a factor. It assumes the following rule:
    * Fact -> ( Expr )
    *       | CellRef
    *       | CellRange
    *       | Number
    *       | Function
    *
    * @access private
    * @return mixed The parsed ptg'd tree on success, PEAR_Error on failure
    */
    function _fact()
    {
        if ($this->_current_token == SPREADSHEET_EXCEL_WRITER_OPEN) {
            $this->_advance();         // eat the "("
            $result = $this->_parenthesizedExpression();
            if ($this->_current_token != SPREADSHEET_EXCEL_WRITER_CLOSE) {
                return $this->raiseError("')' token expected.");
            }
            $this->_advance();         // eat the ")"
            return $result;
        }
        // if it's a reference
        if (preg_match('/^\$?[A-Ia-i]?[A-Za-z]\$?[0-9]+$/',$this->_current_token))
        {
            $result = $this->_createTree($this->_current_token, '', '');
            $this->_advance();
            return $result;
        }
        // If it's an external reference (Sheet1!A1 or Sheet1:Sheet2!A1)
        elseif (preg_match("/^\w+(\:\w+)?\![A-Ia-i]?[A-Za-z][0-9]+$/u",$this->_current_token))
        {
            $result = $this->_createTree($this->_current_token, '', '');
            $this->_advance();
            return $result;
        }
        // If it's an external reference ('Sheet1'!A1 or 'Sheet1:Sheet2'!A1)
        elseif (preg_match("/^'[\w -]+(\:[\w -]+)?'\![A-Ia-i]?[A-Za-z][0-9]+$/u",$this->_current_token))
        {
            $result = $this->_createTree($this->_current_token, '', '');
            $this->_advance();
            return $result;
        }
        // if it's a range
        elseif (preg_match("/^(\$)?[A-Ia-i]?[A-Za-z](\$)?[0-9]+:(\$)?[A-Ia-i]?[A-Za-z](\$)?[0-9]+$/",$this->_current_token) or 
                preg_match("/^(\$)?[A-Ia-i]?[A-Za-z](\$)?[0-9]+\.\.(\$)?[A-Ia-i]?[A-Za-z](\$)?[0-9]+$/",$this->_current_token))
        {
            $result = $this->_current_token;
            $this->_advance();
            return $result;
        }
        // If it's an external range (Sheet1!A1 or Sheet1!A1:B2)
        elseif (preg_match("/^\w+(\:\w+)?\!([A-Ia-i]?[A-Za-z])?[0-9]+:([A-Ia-i]?[A-Za-z])?[0-9]+$/u",$this->_current_token))
        {
            $result = $this->_current_token;
            $this->_advance();
            return $result;
        }
        // If it's an external range ('Sheet1'!A1 or 'Sheet1'!A1:B2)
        elseif (preg_match("/^'[\w -]+(\:[\w -]+)?'\!([A-Ia-i]?[A-Za-z])?[0-9]+:([A-Ia-i]?[A-Za-z])?[0-9]+$/u",$this->_current_token))
        {
            $result = $this->_current_token;
            $this->_advance();
            return $result;
        }
        elseif (is_numeric($this->_current_token))
        {
            $result = $this->_createTree($this->_current_token, '', '');
            $this->_advance();
            return $result;
        }
        // if it's a function call
        elseif (preg_match("/^[A-Z0-9\xc0-\xdc\.]+$/i",$this->_current_token))
        {
            $result = $this->_func();
            return $result;
        }
        return $this->raiseError("Syntax error: ".$this->_current_token.
                                 ", lookahead: ".$this->_lookahead.
                                 ", current char: ".$this->_current_char);
    }

    /**
    * It parses a function call. It assumes the following rule:
    * Func -> ( Expr [,Expr]* )
    *
    * @access private
    * @return mixed The parsed ptg'd tree on success, PEAR_Error on failure
    */
    function _func()
    {
        $num_args = 0; // number of arguments received
        $function = strtoupper($this->_current_token);
        $result   = ''; // initialize result
        $this->_advance();
        $this->_advance();         // eat the "("
        while ($this->_current_token != ')') {
        /**/
            if ($num_args > 0) {
                if ($this->_current_token == SPREADSHEET_EXCEL_WRITER_COMA or
                    $this->_current_token == SPREADSHEET_EXCEL_WRITER_SEMICOLON)
                {
                    $this->_advance();  // eat the "," or ";"
                } else {
                    return $this->raiseError("Syntax error: comma expected in ".
                                      "function $function, arg #{$num_args}");
                }
                $result2 = $this->_condition();
                if (PEAR::isError($result2)) {
                    return $result2;
                }
                $result = $this->_createTree('arg', $result, $result2);
            } else { // first argument
                $result2 = $this->_condition();
                if (PEAR::isError($result2)) {
                    return $result2;
                }
                $result = $this->_createTree('arg', '', $result2);
            }
            $num_args++;
        }
        if (!isset($this->_functions[$function])) {
            return $this->raiseError("Function $function() doesn't exist");
        }
        $args = $this->_functions[$function][1];
        // If fixed number of args eg. TIME($i,$j,$k). Check that the number of args is valid.
        if (($args >= 0) and ($args != $num_args)) {
            return $this->raiseError("Incorrect number of arguments in function $function() ");
        }

        $result = $this->_createTree($function, $result, $num_args);
        $this->_advance();         // eat the ")"
        return $result;
    }

    /**
    * Creates a tree. In fact an array which may have one or two arrays (sub-trees)
    * as elements.
    *
    * @access private
    * @param mixed $value The value of this node.
    * @param mixed $left  The left array (sub-tree) or a final node.
    * @param mixed $right The right array (sub-tree) or a final node.
    * @return array A tree
    */
    function _createTree($value, $left, $right)
    {
        return array('value' => $value, 'left' => $left, 'right' => $right);
    }

    /**
    * Builds a string containing the tree in reverse polish notation (What you
    * would use in a HP calculator stack).
    * The following tree:
    *
    *    +
    *   / \
    *  2   3
    *
    * produces: "23+"
    *
    * The following tree:
    *
    *    +
    *   / \
    *  3   *
    *     / \
    *    6   A1
    *
    * produces: "36A1*+"
    *
    * In fact all operands, functions, references, etc... are written as ptg's
    *
    * @access public
    * @param array $tree The optional tree to convert.
    * @return string The tree in reverse polish notation
    */
    function toReversePolish($tree = array())
    {
        $polish = ""; // the string we are going to return
        if (empty($tree)) { // If it's the first call use _parse_tree
            $tree = $this->_parse_tree;
        }
        if (is_array($tree['left'])) {
            $converted_tree = $this->toReversePolish($tree['left']);
            if (PEAR::isError($converted_tree)) {
                return $converted_tree;
            }
            $polish .= $converted_tree;
        } elseif ($tree['left'] != '') { // It's a final node
            $converted_tree = $this->_convert($tree['left']);
            if (PEAR::isError($converted_tree)) {
                return $converted_tree;
            }
            $polish .= $converted_tree;
        }
        if (is_array($tree['right'])) {
            $converted_tree = $this->toReversePolish($tree['right']);
            if (PEAR::isError($converted_tree)) {
                return $converted_tree;
            }
            $polish .= $converted_tree;
        } elseif ($tree['right'] != '') { // It's a final node
            $converted_tree = $this->_convert($tree['right']);
            if (PEAR::isError($converted_tree)) {
                return $converted_tree;
            }
            $polish .= $converted_tree;
        }
        // if it's a function convert it here (so we can set it's arguments)
        if (preg_match("/^[A-Z0-9\xc0-\xdc\.]+$/",$tree['value']) and
            !preg_match('/^([A-Ia-i]?[A-Za-z])(\d+)$/',$tree['value']) and
            !preg_match("/^[A-Ia-i]?[A-Za-z](\d+)\.\.[A-Ia-i]?[A-Za-z](\d+)$/",$tree['value']) and
            !is_numeric($tree['value']) and
            !isset($this->ptg[$tree['value']]))
        {
            // left subtree for a function is always an array.
            if ($tree['left'] != '') {
                $left_tree = $this->toReversePolish($tree['left']);
            } else {
                $left_tree = '';
            }
            if (PEAR::isError($left_tree)) {
                return $left_tree;
            }
            // add it's left subtree and return.
            return $left_tree.$this->_convertFunction($tree['value'], $tree['right']);
        } else {
            $converted_tree = $this->_convert($tree['value']);
            if (PEAR::isError($converted_tree)) {
                return $converted_tree;
            }
        }
        $polish .= $converted_tree;
        return $polish;
    }
}
?>
