<?php

class ControllerExtensionShippingJoseanMatiasFaixaCEP extends Controller {
    private $error = array();

    public function index() {
        $this->language->load('extension/shipping/joseanmatias_faixa_cep');

        $this->document->setTitle($this->language->get('heading_title_inner'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

            $this->request->post['shipping_joseanmatias_faixa_cep_interval'] = json_encode($this->request->post['shipping_joseanmatias_faixa_cep_interval']);

            $this->model_setting_setting->editSetting('shipping_joseanmatias_faixa_cep', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true));
        }

        $data['heading_title'] = $this->language->get('heading_title_inner');

        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['text_all_zones'] = $this->language->get('text_all_zones');
        $data['text_none'] = $this->language->get('text_none');
        $data['text_interval'] = $this->language->get('text_interval');
        $data['text_interval_info'] = $this->language->get('text_interval_info');
        $data['text_edit'] = $this->language->get('text_edit');

        $data['entry_group_title'] = $this->language->get('entry_group_title');
        $data['entry_title'] = $this->language->get('entry_title');
        $data['entry_cep'] = $this->language->get('entry_cep');
        $data['entry_total'] = $this->language->get('entry_total');
        $data['entry_cost'] = $this->language->get('entry_cost');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');

        $data['button_add'] = $this->language->get('button_add');
        $data['button_remove'] = $this->language->get('button_remove');
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['shipping_joseanmatias_faixa_cep_interval'])) {
            $data['error_faixa_cep_interval'] = $this->error['shipping_joseanmatias_faixa_cep_interval'];
        } else {
            $data['error_faixa_cep_interval'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_shipping'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title_inner'),
            'href' => $this->url->link('extension/shipping/joseanmatias_faixa_cep', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/shipping/joseanmatias_faixa_cep', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true);

        if (isset($this->request->post['shipping_joseanmatias_faixa_cep_group_title'])) {
            $data['shipping_joseanmatias_faixa_cep_group_title'] = $this->request->post['shipping_joseanmatias_faixa_cep_group_title'];
        } else {
            $data['shipping_joseanmatias_faixa_cep_group_title'] = $this->config->get('shipping_joseanmatias_faixa_cep_group_title');
        }
        
        if (isset($this->request->post['shipping_joseanmatias_faixa_cep_status'])) {
            $data['shipping_joseanmatias_faixa_cep_status'] = $this->request->post['shipping_joseanmatias_faixa_cep_status'];
        } else {
            $data['shipping_joseanmatias_faixa_cep_status'] = $this->config->get('shipping_joseanmatias_faixa_cep_status');
        }

        if (isset($this->request->post['shipping_joseanmatias_faixa_cep_sort_order'])) {
            $data['shipping_joseanmatias_faixa_cep_sort_order'] = $this->request->post['shipping_joseanmatias_faixa_cep_sort_order'];
        } else {
            $data['shipping_joseanmatias_faixa_cep_sort_order'] = $this->config->get('shipping_joseanmatias_faixa_cep_sort_order');
        }

        if (isset($this->request->post['shipping_joseanmatias_faixa_cep_interval'])) {
            $intervals = $this->request->post['shipping_joseanmatias_faixa_cep_interval'];
        } elseif ($this->config->has('shipping_joseanmatias_faixa_cep_interval')) {
            $intervals = json_decode($this->config->get('shipping_joseanmatias_faixa_cep_interval'), true);
        } else {
            $intervals = array();
        }

        $data['shipping_joseanmatias_faixa_cep_interval'] = array();

        foreach ($intervals as $key => $interval) {
            $data['shipping_joseanmatias_faixa_cep_interval'][] = array(
                'key' => $key,
                'title' => $interval['title'],
                'order_total' => $interval['order_total'],
                'cep' => $interval['cep'],
                'cost' => $interval['cost']
            );
        }

        $data['currency_simbol'] = $this->currency->getSymbolLeft($this->config->get('config_currency'));
        if (!$data['currency_simbol']) {
            $data['currency_simbol'] = $this->currency->getSymbolRight($this->config->get('config_currency'));
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/shipping/joseanmatias_faixa_cep', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/shipping/joseanmatias_faixa_cep')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['shipping_joseanmatias_faixa_cep_interval'])) {
            $this->error['shipping_joseanmatias_faixa_cep_interval'] = $this->language->get('error_interval');
        }

        $max_input_vars = ini_get('max_input_vars');
        if(isset($this->request->post['shipping_joseanmatias_faixa_cep_interval'])) {
            $total_ceps = count($this->request->post['shipping_joseanmatias_faixa_cep_interval']);
        } else {
            $total_ceps = 0;
        }        
        $ceps_limit = ($max_input_vars / 5);

        if($total_ceps > $ceps_limit) {
            $this->error['warning'] = sprintf($this->language->get('error_max_input_vars'), $max_input_vars, $ceps_limit, $total_ceps);
        }

        return !$this->error;
    }
}
?>