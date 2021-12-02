<?php

class ControllerApiIntegracao extends Controller
{

	private $request_data;

	public function index()
	{
		return $this->output(['code' => 200, 'message' => 'Connected']);
	}

	public function product()
	{
		try {
			$this->getRequestData();

			foreach ($this->request_data as $product) {
				if (!isset($product->product_id) || !isset($product->product_name) || !isset($product->price) || !isset($product->quantity))
					return $this->errorInvalidRequest();

				$this->model_tool_integracao->product($product);
			}
			return $this->output();
		} catch (Exception $e) {
			return $this->returnErrorRequestData();
		}
	}

	public function category()
	{
		try {
			$this->getRequestData();

			foreach ($this->request_data as $category) {
				if (!isset($category->category_id) || !isset($category->category_name))
					return $this->errorInvalidRequest();

				$this->model_tool_integracao->category($category);
			}
			return $this->output();
		} catch (Exception $e) {
			return $this->returnErrorRequestData();
		}
	}

	public function manufacturer()
	{
		try {
			$this->getRequestData();

			foreach ($this->request_data as $manufacturer) {
				if (!isset($manufacturer->manufacturer_id) || !isset($manufacturer->manufacturer_name))
					return $this->errorInvalidRequest();

				$this->model_tool_integracao->manufacturer($manufacturer);
			}
			return $this->output();
		} catch (Exception $e) {
			return $this->returnErrorRequestData();
		}
	}

	public function option()
	{
		try {
			$this->getRequestData();

			foreach ($this->request_data as $option) {
				if (!isset($option->option_id) || !isset($option->option_name) || !isset($option->option_values))
					return $this->errorInvalidRequest();

				$this->model_tool_integracao->option($option);
			}
			return $this->output();
		} catch (Exception $e) {
			return $this->returnErrorRequestData();
		}
	}

	public function checkManufacturer()
	{
		$this->load->model('tool/util');
		return $this->model_tool_util->disableManufacturerWithoutActiveProdutcs();
	}

	public function loadCategoryNews()
	{
        $this->load->model('tool/integracao');
        return $this->model_tool_integracao->produtosCategoriaNovidade();
	}

	private function output($json = [
		'code' => 200,
		'message' => 'Successfull'
	])
	{
		$this->response->addHeader('Content-Type: application/json');
		return $this->response->setOutput(json_encode($json));
	}

	private function getRequestData()
	{
		$this->load->model('tool/integracao');
		//print_r(getallheaders());exit; pega todos os headers inclusive se houve o get all headers
		$this->request_data = json_decode(file_get_contents('php://input'));

		if (!is_array($this->request_data) && !is_object($this->request_data))
			throw new Exception('error json');
	}

	private function returnErrorRequestData($data = [
		'code' => 500,
		'message' => 'Something wrong'
	])
	{
		return $this->output($data);
	}

	private function errorInvalidRequest($data = [
		'code' => 422,
		'message' => 'Invalid params for the request'
	])
	{
		return $this->output($data);
	}
	
	public function inativaAll()
	{
	    $this->load->model('tool/integracao');
        return $this->model_tool_integracao->inativaProdutosSemEstoque();
	}
}
