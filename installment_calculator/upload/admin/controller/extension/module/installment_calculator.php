<?php
class ControllerExtensionModuleInstallmentCalculator extends Controller {
    private $error = [];
    
    public function index() {
        $this->load->language('extension/module/installment_calculator');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');
        
        if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validate()) {
            $this->model_setting_setting->editSetting('module_installment_calculator', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
        }
        
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_edit'] = $this->language->get('text_edit');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_months'] = $this->language->get('entry_months');
        $data['entry_email'] = $this->language->get('entry_email');
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');
        
        $data['action'] = $this->url->link('extension/module/installment_calculator', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
        
        $data['module_installment_calculator_status'] = isset($this->request->post['module_installment_calculator_status']) ? $this->request->post['module_installment_calculator_status'] : $this->config->get('module_installment_calculator_status');
        $data['module_installment_calculator_months'] = isset($this->request->post['module_installment_calculator_months']) ? $this->request->post['module_installment_calculator_months'] : $this->config->get('module_installment_calculator_months');
        $data['module_installment_calculator_email'] = isset($this->request->post['module_installment_calculator_email']) ? $this->request->post['module_installment_calculator_email'] : $this->config->get('module_installment_calculator_email');
        
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