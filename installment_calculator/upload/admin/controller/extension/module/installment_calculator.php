<?php
class ControllerExtensionModuleInstallmentCalculator extends Controller {
    private $error = [];
    
    public function index() {
        $this->load->language('extension/module/installment_calculator');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('module_installment_calculator', $this->request->post);
            $this->session->data['success'] = 'Настройки сохранены!';
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
            'text' => 'Главная',
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];
        
        $data['breadcrumbs'][] = [
            'text' => 'Расширения',
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        ];
        
        $data['breadcrumbs'][] = [
            'text' => 'Калькулятор рассрочки',
            'href' => $this->url->link('extension/module/installment_calculator', 'user_token=' . $this->session->data['user_token'], true)
        ];
        
        $data['action'] = $this->url->link('extension/module/installment_calculator', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
        
        $data['heading_title'] = 'Калькулятор рассрочки';
        $data['text_edit'] = 'Редактирование модуля';
        $data['text_enabled'] = 'Включено';
        $data['text_disabled'] = 'Выключено';
        $data['text_home'] = 'Главная';
        $data['text_extension'] = 'Расширения';
        
        $data['entry_status'] = 'Статус';
        $data['entry_months'] = 'Периоды рассрочки';
        $data['entry_email'] = 'Email для заявок';
        
        $data['help_months'] = 'Укажите доступные периоды через запятую: 4,6,10,12';
        $data['help_email'] = 'Email для заявок. Если не указан - используется email магазина';
        
        $data['button_save'] = 'Сохранить';
        $data['button_cancel'] = 'Отмена';
        
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
            $this->error['warning'] = 'У вас нет прав!';
        }
        return !$this->error;
    }
    
    // УДАЛЕНЫ методы install() и uninstall() - они не нужны для OCMOD
}
