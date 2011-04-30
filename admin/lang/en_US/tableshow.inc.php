<?php
$lang['admin']['help_function_tableshow'] = <<<EOT
  <h3>What does this do?</h3>
  <p> Extracts a table from data stored in wiki-like text form in either a page or a global 
content block and displays it using css to control the output format.  The stored data can include
tablehead, tablefoot and tablebody sections.  Criteria can be given in the tag to select only a certain range of the table, and this range can be dynamic -- depending on the current date.  Cells containing dates can be reformatted using date format strings.
</p>

  <h3>How do I use it?</h3>
  <p> There are two parts to using tableshow: storing the data, and inserting a tag in the place you wish it to be displayed.

  <h4>storing the data</h4>
<p>Generate the table of data using any plain text editor (windows users: use wordpad rather 
than a word processor). Each line will be interpreted as a table row or other command as follows:
</p>
<ul>
  <li>If there is a line containing only three or more hyphens (&quot;---&quot;), the section 
above will become the &lt;thead&gt;.  If a second such dividing line is found the intermediate 
part will be the &lt;tbody&gt;, and lines following it will become &lt;tfoot&gt;. </li>
  <li>If the first line is enclosed in underscore characters, it will become the &lt;caption&gt;</li>
  <li>Generally cells should be divided by pipes (&quot;|&quot;) or carets (&quot;^&quot;).  Cells
preceded by a &quot;|&quot; will be &lt;td&gt; and those preceded by &quot;^&quot; become &lt;th&gt;.</li>
  <li>two or more spaces both before and after the cell contents cause it to be centred.</li>
  <li>two or more spaces leading the cell contents cause a right-aligned cell.</li>
  <li>two or more spaces trailing the cell contents cause the cell to be left-aligned.</li>
  <li>To make a cell span several columns, simply place the corresponding number of dividers 
after the cell.  Thus to make <tt>&lt;td colspan=&quot;3&quot;&gt;example&lt;/td&gt;</tt>, enter 
<tt>| example |||</tt>.</li> 
  <li>Note that the single space between the separator character and the text is optional.</li>
  <li>If the first cell of a row begins with an asterisk (&quot;*&quot;), this will be stripped, but
rows marked in this way are subheadings, and when the range of rows being displayed begins between two
subheadings, the last subheading will be displayed even though all aother previous rows have been 
discarded.</li>
</ul>
<p>Decide whether to store it in a global content block or in a normal page.  
If you use a page, this can be viewed by anybody so you will generally not want to include it in 
the indexed namespace of your site.  Tableshow will look by default for the page 'table-data'.  If 
you  put it in a global content block, it will only be visible via the admin interface, which is 
usually better.</p>
<p> Create the new page or global content block, turn off wysiwyg (if it is active) and paste the 
data table into it. Add &quot;&lt;pre&gt;&quot; in a line at the top of the data, and  
&quot;&lt;/pre&gt;&quot; in a line at the bottom &ndash; this protects the table from reformatting 
by wysiwyg editors if it is erroneously opened using one.  Save the block or page.</p> 
  <h4>displaying the table</h4>
  <p> On the page you want the table to show, insert<br />
<code>{tableshow [block="{bn}"|page="{pn}"] [start="{s-crit}"] [end="{e-crit}"] [dateformat="{df}]}</code><br />
where: </p>
  <ul>
    <li><tt>{bn}</tt> - the global content block name, or </li>
	<li><tt>{pn}</tt> - the page alias</li>
	<li><tt>{s-crit}</tt> - the criterion for the starting row of the table given as a column number (first column is '1') and optional comparison eg: <tt>&quot;3 > 1945&quot;</tt>. If you give a cell number with no comparison it defaults to &gt;= today's date. The possible comparisons are:
	<ul>
		<li> = matches if the cell contents are the same as the value given</li>
		<li> > matches if the cell contents are greater than the value given.  This can be used for alphabetical matching too.</li>
		<li> < matches if the cell contents are less than the value given.  This can be used for alphabetical matching too.</li>
		<li> + matches if the cell contents are a date greater than or equal to the date given</li>
		<li> - matches if the cell contents are a date earlier than the date given</li>
		<li> & matches if the cell contents are a date later than the number of days given from today.  Use a negative number of days to specify a period earlier than today's date.</li>
	</ul>
 </li>
	<li><tt>{e-crit}</tt> - the criterion for the end row, similarly.</li>
	<li><tt>{df}</tt> - should contain a strftime format string (Google 'strftime' if in doubt) and a list of column numbers to which the format should be applied, all separated by pipe symbols (&quot;|&quot;). An example: <tt>dateformat=&quot;d M Y|2|3|6&quot;</tt> would convert dates given in columns 2, 3 and 6 to format similar to &quot;14 Mar 2011&quot;. Many different date formats are accepted in the input data, but beware that if your input date is slash-separated, it will be interpreted at out-of-sequence American-style date (m/d/Y) so use hyphens if you mean d-m-Y.</li>
  </ul>
<p>Only one of <tt>block</tt> or <tt>page</tt> can be used: the default is <code>page='table-data'</code>.</p>

<p> Formatting of the table is controlled by css.</p>
EOT;
?>
