<?php
/*
 * The MIT License
 *
 * Copyright 2015 Ezra.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace dsLive\Controllers;

use dbScribe\Table,
    Json,
    stdClass,
    Util;

/**
 * Description of DataTableController
 *
 * @author Ezra
 */
class DataTableController extends SuperController {

    public function indexAction() {
	return parent::indexAction()->variables(array(
		    'script' => $this->getDataTableScript(),
	));
    }

    /**
     * The action called by ajax for loading data
     * @param string $columns
     * @param string $orderColumn
     */
    public function dataTableAction($columns, $orderColumn = null) {
	$repo = $this->service->getRepository()->targetColumns($columns);
	//Paging
	if (isset($_GET['start']) && $_GET['limit'] != '')
	    $repo->limit(intval($_GET['limit']), intval($_GET['start']));
	// Ordering
	if (isset($_GET['order']))
	    $repo->orderBy($_GET['order']['column'], $_GET['order']['dir']);
	else if ($orderColumn) { // default ordering
	    $columns = explode(',', $orderColumn);
	    foreach ($columns as $column) {
		$order = explode(':', $column);
		$repo->orderBy($order[0], count($order) > 1 ? $order[1] : Table::ORDER_ASC);
	    }
	}
	// Filtering
	// NOTE this does not match the built-in DataTables filtering which does it
	// word by word on any field. It's possible to do here, but concerned about efficiency
	// on very large tables, and MySQL's regex functionality is very limited
	if ($_GET['search']['value'] != "") {
	    $repo->startGroup();
	    foreach (explode(',', $_GET['search']['columns']) as $column)
		$repo->like($column, '%' . strip_tags($_GET['search']['value']) . '%', FALSE);
	    $repo->endGroup();
	}
	// Found data
	$data = $repo->fetchAll()->getArrayCopy();
	// Total data set length
	$total = $repo->count();
	// Output
	$json = new Json(array(
	    "total" => intval($total),
	    "data" => $data,
	));
	$json->toScreen(true);
    }

    /**
     * Creates the required script to fetch the data an place on the table
     * @param string $columns Comma-separated list of columns to fetch
     * @param string $order Comma-separated list of columns to sort with.
     * Direction may be appended to each column separated by :. e.g column:direction
     * @param int $outputColumns The number of columns to show on the table
     * @param string $tableSelector The table selector to append results
     * @return string
     */
    protected function getDataTableScript($columns = null, $order = null, $tableSelector = 'table') {
	if (!$columns)
	    $columns = join(',', $this->service->getRepository()->getColumns(true));
	$dColumns = array();
	foreach (explode(',', $columns) as $col) {
	    $obj = new stdClass();
	    $obj->data = Util::camelTo_($col);
	    $obj->sortable = true;
	    $dColumns[] = $obj;
	}
	ob_start();
	?>
	<script>
	    $(document).ready(function () {
		$('<?= $tableSelector ?>').prepTable({
		    columns: $.parseJSON('<?= json_encode($dColumns) ?>'),
		    ajax: "<?=
	$this->view->url($this->getModule(), $this->getClassName(), 'dataTable', array($columns, $order))
	?><?= $this->request->queryString ? '?' . $this->request->queryString : null ?>",
		    append: false,
		    pagination: {
			firstLast: false,
			nextPrevious: true,
			numbers: true
		    },
		    alert_errors: false,
		    log_data: false,
		    display_errors: true
		});
	    });
	</script>
	<?php
	return ob_get_clean();
    }

}
