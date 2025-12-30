<?php
class ControllerExtensionModuleInstallmentCalculator extends Controller {
    private $error = [];
    
    public function index() {
        $this->load->language('extension/module/installment_calculator');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('module_installment_calculator', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
        }
        
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }
        
        $data['breadcrumbs'] = [];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        ];
        
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/installment_calculator', 'user_token=' . $this->session->data['user_token'], true)
        ];
        
        $data['action'] = $this->url->link('extension/module/installment_calculator', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
        
        // Языковые переменные
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_edit'] = $this->language->get('text_edit');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['text_home'] = $this->language->get('text_home');
        $data['text_extension'] = $this->language->get('text_extension');
        
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_months'] = $this->language->get('entry_months');
        $data['entry_email'] = $this->language->get('entry_email');
        
        $data['help_months'] = $this->language->get('help_months');
        $data['help_email'] = $this->language->get('help_email');
        
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');
        
        // Значения из POST или из конфига
        if (isset($this->request->post['module_installment_calculator_status'])) {
            $data['module_installment_calculator_status'] = $this->request->post['module_installment_calculator_status'];
        } else {
            $data['module_installment_calculator_status'] = $this->config->get('module_installment_calculator_status');
        }
        
        if (isset($this->request->post['module_installment_calculator_months'])) {
            $data['module_installment_calculator_months'] = $this->request->post['module_installment_calculator_months'];
        } elseif ($this->config->get('module_installment_calculator_months')) {
            $data['module_installment_calculator_months'] = $this->config->get('module_installment_calculator_months');
        } else {
            $data['module_installment_calculator_months'] = '4,6,10,12';
        }
        
        if (isset($this->request->post['module_installment_calculator_email'])) {
            $data['module_installment_calculator_email'] = $this->request->post['module_installment_calculator_email'];
        } elseif ($this->config->get('module_installment_calculator_email')) {
            $data['module_installment_calculator_email'] = $this->config->get('module_installment_calculator_email');
        } else {
            $data['module_installment_calculator_email'] = $this->config->get('config_email');
        }
        
        $data['home'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);
        $data['extension'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
        $data['config_email'] = $this->config->get('config_email');
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('extension/module/installment_calculator', $data));
    }
    
    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/installment_calculator')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        return !$this->error;
    }
    
    public function install() {
        $this->load->model('setting/event');
        $this->model_setting_event->addEvent('installment_calculator', 'catalog/view/product/product/before', 'extension/module/installment_calculator/addCalculator');
    }
    
    public function uninstall() {
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode('installment_calculator');
    }
}