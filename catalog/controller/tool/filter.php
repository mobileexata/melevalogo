<?php
class ControllerToolFilter extends Controller {

	public function index() {
		$this->load->model('tool/filter');

		$this->model_tool_filter->createManufacturerFilters();
    $this->model_tool_filter->createOptionFilters();
    $this->model_tool_filter->createCategoryFilters();
    $this->model_tool_filter->createProductsFilters();
		
		$this->response->setOutput(json_encode([
			'success'
		]));
	}

}