<?php
#CMS - CMS Made Simple
#(c)2004 by Ted Kulp (wishy@users.sf.net)
#This project's homepage is: http://cmsmadesimple.sf.net
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program; if not, write to the Free Software
#Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

# This plugin tag by richard <richard@the-place.net>.  It is intended to
# extract and display a table presented in a simple wiki-like markup in either 
# a page or a global block.  Each line is interpreted as a table row and can  
# have an optional '<br />' at the end to allow easy viewing for admin 
# purposes. Additionally, lines can be selected for display based on the 
# values in particular cells.  This allows, for example, the discarding of 
# already passed dates. 

# USAGE: {tableshow block="{bn}"|page="{pn}" [start="{s-crit}"] [end="{e-crit}"]}
# 
# where
#   {bn} or {pn} is global content block name, or page alias where the 
#                    table data are given. Defaults to page alias = showtable.
#                    It is recommended to enclose the data in <pre></pre> tags
#                    to allow viewing.  These tags are automatically stripped.
#
#   {s-crit} is the criterion for the starting row of the table given as
#                    a column number (first column is '1') and
#                    optional comparison eg: "3 > 1945". s-crit cell number
#                    with no comparison defaults to >= today's date.
#
#   {e-crit} is the criterion for the end row, similarly.
#
# I have not implemented paging because inserting <thead> and <tfoot> 
# segments should result in the browser dealing with paging.  These can be 
# inserted directly into the source page or the three segments divided by
# lines beginning with three or more hyphens ('---').  The segments must be 
# in the order thead, tfoot, tbody.
#
# Formatting of the table is controlled by css.
#

function smarty_cms_function_tableshow($params, &$smarty) {
    global $gCms;

	$display = "";
	$table = array();
	$cells = array();
	$celltyprgx = "/[|^]/";
	$critrgx = "/^(\d+)([=<>&+-])(.*)$/";
	$dataerror = "<div class=\"tblcontnr\"><b>input data error</b></div>\n";
	$top = "<div class=\"tblcontnr\">\n\t<table class=\"tblshow\">\n";
	$tail = "\t</table>\n</div>";
	# we use UTC to avoid summertime errors. 
	date_default_timezone_set('UTC');
	$nowdate = date('d-m-Y');

	if (isset($params['block'])) {
		# get content from a global content block:
		$sourceblock = $params['block'];
		$modules = array_keys($gCms->modules);
		$modObj = $gCms->modules[$modules[0]]['object'];
		$tabledata = $modObj->ProcessTemplateFromData("{global_content name='{$sourceblock}'}");
	} else {
		# so get content from a page (default is 'table-data'):
		if (isset($params['page'])) {
			$sourcepage = $params['page'] ;
		} else {
			$sourcepage = 'table-data';
		}
		$cntops = $gCms->getContentOperations();
		$content = $cntops->LoadContentFromAlias("$sourcepage");
		$tabledata = $content->Show();
	}

	# confirm input data exists
	if (strlen($tabledata) < 10) {
		return $dataerror;
	}
	# form an array of the data
	$table = explode("\n", $tabledata);
	$rowcount = count($table);
	# top and tail
	$text = $top;
	$table = preg_replace( "/^ *<\/?pre> *$/", "", $table) ;

	# break into segments (thead, tfoot, tbody)
	foreach ($table as $row) {
		$row = trim($row);
		if ($row != "") {
			if (substr($row, 0, 3) == "---") {
				if (!isset($thead)) {
					$thead = $segment;
					unset($segment);
				} elseif (!isset($tfoot)) {
					$tfoot = $segment;
					unset($segment);
				}
			} else {
				$segment[] = $row;
			}
		}
	}
	$tbody = $segment;

	# parse thead
	if (isset($thead)) {
		$text .= "\t\t<thead>\n";
		$thead = preg_replace( "/^\s*\_([^_]+)\_\s*$/", '<caption>\1</caption>', $thead ); 
		$text .= parse_segment($thead, 0, 0, -1);
		$text .= "\n\t\t</thead>\n";
	}
	# parse tfoot
	if (isset($tfoot)) {
		$text .= "\t\t<tfoot>\n";
		$text .= parse_segment($tfoot, 0, 0, -1);
		$text .= "\n\t\t</tfoot>\n";
	}

	# range of tbody to display
	# is the start defined? Also pick up the last subhead row before the start
	if (isset($params['start'])) {
		if(preg_match("$critrgx", $params['start'], $scrit)) {
		} else {
			$scrit = array($params['start']."+".$nowdate, $params['start'], "+", $nowdate);
		}
		# so find first row
		foreach ($tbody as $r=>$row) {
			if (preg_match("$celltyprgx", substr($row,0,1))) {
				if (preg_match("/^\*(.*)$/", substr($row,1,1))) {
					$lastsubhd = $r;
				}
				$cells[$r] = preg_split("$celltyprgx", $row);
				$chkcontent = trim($cells[$r][$scrit[1]]);
				if (strlen($chkcontent)) {
					if (check_match($chkcontent, $scrit[2], $scrit[3])) {
						$first = $r;
						break;
					}
				}
			}
		}
	} else {
		$first = 0;
	}
	# is the end defined?
	if (isset($params['end'])) {
		preg_match("$critrgx", $params['end'], $ecrit);
		# so find the last row
		for ($r=$first; $r < count($tbody); $r++) {
			if (preg_match("$celltyprgx", substr($tbody[$r],0,1))) {
				$cells[$r] = preg_split("$celltyprgx", $tbody[$r]);
				if (strlen($cells[$r][$ecrit[1]])) {
					if (check_match(trim($cells[$r][$ecrit[1]]), $ecrit[2], $ecrit[3])) {
						$last = $r;
						break;
					}
				}
			}
		}
	} else {
		$last = count($tbody);
	}

	# parse tbody
	$text .= "\t\t<tbody>\n";
	if (!isset($first)) {
		$span = count(preg_split("$celltyprgx", $table[0]));
		$text .= "<tr><td span=\"$span\" align=\"centre\" class=\"warning\">no data in range<br />no start row found</td></tr>";
	} else {
		$text .= parse_segment($tbody, $first, $last, $lastsubhd);
	}
	$text .= "\n\t\t</tbody>\n";
	
	# and close off
	$text .= $tail;

	return $text;

}

function parse_segment($table, $first, $last, $lastsubhd) {
	$text = "";
	if ($last == 0) {
		$last = count($table);
	}
	if ($lastsubhd > -1 ) {
		$text .= parse_row($table[$lastsubhd]);
	}
	for ($r = $first; $r <= $last; $r++) {
		$row = $table[$r];
		if (preg_match("/[|^]/", substr($row,0,1))) {
		$text .= parse_row($row);
		} else {
			$text .= $row ;
		}
	}
	return $text;
}

function parse_row ($row) {
	$text .= "\t\t\t<tr>\n";
		while (strlen($row) > 1 ) {
			if (preg_match("/^([|^])([^|^]+)([|^]*)/", $row, $cell)){
				$type = substr($row, 0, 1);
				$span = strlen($cell[3]);
				$content = $cell[2];
				if (substr($content,0,1) == "*") {
					$content = substr($content,1);
				}
				$shorten = strlen($cell[2]) + $span ;
				$text .= write_cell($type, $span, $content);
				$row = substr($row, $shorten);
			}
		}
		$text .= "\n\t\t\t</tr>\n";
	return $text;
}

function check_match($a_cell, $a_comp, $a_val) {
	$sperday = 86400;
	switch ($a_comp) {
		case '=':
			return ($a_cell == $a_val); 
		case '>':
			return ($a_cell > $a_val);
		case '<':
			return ($a_cell < $a_val);
		case '+':
			$a_cell = preg_replace("/\//", '-', $a_cell);
			$a_val = preg_replace("/\//", '-', $a_val);
			return (strtotime($a_cell) > strtotime($a_val));
		case '-':
			$a_cell = preg_replace("/\//", '-', $a_cell);
			$a_val = preg_replace("/\//", '-', $a_val);
			return (strtotime($a_cell) < strtotime($a_val));
		case '&':
			$a_cell = preg_replace("/\//", '-', $a_cell);
			$a_val_u = time() + ($a_val*$sperday);
			return 	(strtotime($a_cell) > $a_val_u);
	}
	return TRUE;
}

function write_cell($type, $span, $content) {
	if ($type == "^" ) {
		$ctype = "h";
	} else {
		$ctype = "d";
	}
	if (substr($content, 0 , 2) == "  ") {
		if (substr($content, -2 , 2) == "  ") {
			$calign = " align=\"center\"";
		} else {
			$calign = " align=\"right\"";
		}
	} else {
		$calign = " align=\"left\"";
	}
	if ($span > 1 ) {
		$cspan = " colspan=\"$span\"";
	} else {
		$cspan = "";
	}
	$ccontent = trim($content);
	$celltext = "<t$ctype$cspan$calign>$ccontent</t$ctype>";
	return $celltext;
}

function smarty_cms_help_function_tableshow()
{
  echo lang('help_function_tableshow');
}


function smarty_cms_about_function_tableshow()
{
?>
  <p>Author:  richard Lyons &lt;richard@the-place.net&gt; </p>
  <p>Version 0.1</p>
  <p>Change History<br />
    0.1.1 - beta release<br />
    0.1 - test version<br />
  </p>
<?php
}

?>
