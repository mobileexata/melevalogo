<?php
class ControllerExtensionModuleCotacaoFrete extends Controller {
    public function index() {
        if ($this->config->get('module_cotacao_frete_status')) {
            if (is_array($this->config->get('module_cotacao_frete_languages'))) {
                if (in_array($this->session->data['language'], $this->config->get('module_cotacao_frete_languages'))) {
                    if (is_array($this->config->get('module_cotacao_frete_stores'))) {
                        if (in_array($this->config->get('config_store_id'), $this->config->get('module_cotacao_frete_stores'))) {
                            $data = $this->load->language('extension/module/cotacao_frete');

                            $this->load->model('localisation/country');
                            $data['countries'] = $this->model_localisation_country->getCountries();

                            $data['exibir_pais'] = $this->config->get('module_cotacao_frete_exibir_pais');
                            $data['country_id'] = $this->config->get('module_cotacao_frete_country_id');
                            $data['exibir_estado'] = $this->config->get('module_cotacao_frete_exibir_estado');
                            $data['zone_id'] = $this->config->get('module_cotacao_frete_zone_id');
                            $data['exibir_numero'] = $this->config->get('module_cotacao_frete_exibir_numero');
                            $data['exibir_documento'] = $this->config->get('module_cotacao_frete_exibir_documento');
                            $data['container_opcoes'] = $this->config->get('module_cotacao_frete_container_opcoes');
                            $data['codigo_css'] = $this->config->get('module_cotacao_frete_codigo_css');

                            return $this->load->view('extension/module/cotacao_frete', $data);
                        }
                    }
                }
            }
        }
    }
}