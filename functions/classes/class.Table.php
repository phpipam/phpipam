<?php

/**
*
*    Table class to print out table paginations
*
*/

class Table extends Common_functions {

    private $limits = array(25, 50, 100, 250, 500, "all");

    public $limit = 50;

    public $pagination = false;

    public $subpage = 1;

    public $filters = array();



	/**
	 * __construct function
	 *
	 * @access public
	 */
	public function __construct () {
		# initialize Result
		$this->Result = new Result ();
		# set default print limit from cookie
        $this->set_display_limit ();
    }

    private function set_display_limit () {
        $this->limit = isset($_COOKIE['table-page-size']) ? $_COOKIE['table-page-size'] : 50;
    }

    private function set_display_limit_cookie () {
         setcookie("table-page-size", $this->limit, time()+2592000, "/", null, null, true);
    }







	public function paginate_table ($subnets) {
    	// set filters for table
    	$filters = $this->set_table_filter_data ($subnets);
    	// top dropdowns
    	$dropdown = $this->get_table_dropdown ();
    	// set pagination
    	$pagination = $this->get_table_pagination ($subnets);
    	// result
    	return array(
	            "dropdown"   => $dropdown,
	            "filters"    => $this->filters,
	            "pagination" => $pagination
	            );
	}

	private function set_table_filter_data ($subnets) {
		// page
		if(isset($_GET['subpage'])) {
    		if (!is_numeric($_GET['subpage'])) {
                $this->Result->show("danger", _("Invalid subpage"), true);
    		}
    		$this->subpage = $_GET['subpage'];
		}
		// limit
		if(isset($_GET['limit'])) {
    		if ($_GET['limit']=="all") { $_GET['limit'] = 1000000; }
    		if (!is_numeric($_GET['limit'])) {
                $this->Result->show("danger", _("Invalid limit"), true);
    		}
    		$this->limit = $_GET['limit'];
		}

		//calculate start / stop
		$start = ($this->subpage * $this->limit) - $this->limit;
		$stop  = ($this->subpage * $this->limit) - 1;

        // number of pages
		$pagenum = (int) ceil(sizeof($subnets)/$this->limit);

        // save
        $this->filters = array(
                "subpage" => $this->subpage,
                "limit"   => $this->limit,
                "pagenum" => $pagenum,
                "start"   => $start,
                "stop"    => $stop
                );
	}

	private function get_table_dropdown () {
    	// print
    	if($this->filters['pagenum']>1) {
        	$html = array();
        	$html[] = "<form id='table-dropdown-select' class='pull-right'>";
        	$html[] = "<select name='limit' class='form-control input-sm input-w-auto table-limit'>";
        	foreach ($this->limits as $l) {
                $selected = $l==$this->subpage ? "selected" : "";
                $html[] = "<option value='$l' $selected>$l</option>";
        	}
        	$html[] = "</select>";
        	$html[] = "</form>";
        	// return
        	return $html;
    	}
    	else {
        	return false;
    	}
	}

	private function get_table_pagination ($subnets) {
		// pagination html start
		$html   = array();
		$html[] = "<ul class='pagination input-sm'>";

		// previous
		if($this->filters['subpage']==1)
		$html[] = " <li class='disabled'><a><span aria-hidden='true'>«</span><span class='sr-only'>Previous</span></a></li>";
		else
		$html[] = " <li><a href='".create_link("subnets",$_GET['section'])."?subpage=".($this->filters['subpage']-1)."&limit=".$this->filters['limit']."'><span aria-hidden='true'>«</span><span class='sr-only'>Previous</span></a></li> ";

 		// one page
		if ($this->filters['pagenum']==1) {
    		return false;
		}
		// 1 to 5
		else {
    		for ($m=1; $m<=$this->filters['pagenum']; $m++) {
        		// pages
        		if($m==$this->filters['subpage'])
        		$html[] = " <li class='active disabled'><a>$m</a></li>";
        		else
        		$html[] = " <li><a href='".create_link("subnets",$_GET['section'])."?subpage=$m&limit=".$this->filters['limit']."'>$m</a></li>";
    		}
		}


		// next
		if($this->filters['pagenum']==$this->filters['subpage'])
		$html[] = " <li class='disabled'><a><span aria-hidden='true'>»</span><span class='sr-only'>Next</span></a></li>";
		else
		$html[] = " <li><a href='".create_link("subnets",$_GET['section'])."?subpage=".($this->filters['subpage']+1)."&limit=".$this->filters['limit']."'><span aria-hidden='true'>»</span><span class='sr-only'>Next</span></a></li> ";

        // finish
        $html[] = "</ul>";

        // result
        return $html;
    }



}

?>