<?php
class ControllerExtensionModuleInstallmentCalculator extends Controller {
    
    public function index() {
        $this->load->language('extension/module/installment_calculator');
        $this->load->model('setting/setting');
        
        $settings = $this->model_setting_setting->getSetting('module_installment_calculator');
        
        if (empty($settings['module_installment_calculator_status'])) {
            return '';
        }
        
        $months_string = !empty($settings['module_installment_calculator_months']) 
            ? $settings['module_installment_calculator_months'] 
            : '4,6,10,12';
        
        $months = array_map('intval', array_map('trim', explode(',', $months_string)));
        sort($months);
        
        $data['months'] = $months;
        $data['months_json'] = json_encode($months);
        $data['default_month'] = end($months);
        $data['price'] = '0';
        $data['language'] = $this->config->get('config_language');
        
        // Передаём языковые переменные
        $data['text_installment_period'] = $this->language->get('text_installment_period');
        $data['text_months'] = $this->language->get('text_months');
        $data['text_per_month'] = $this->language->get('text_per_month');
        $data['text_buy_installment'] = $this->language->get('text_buy_installment');
        $data['text_no_overpayment'] = $this->language->get('text_no_overpayment');
        $data['text_no_down_payment'] = $this->language->get('text_no_down_payment');
        $data['text_instant_approval'] = $this->language->get('text_instant_approval');
        
        return $this->load->view('extension/module/installment_calculator', $data);
    }
    
    public function popup() {
        $this->load->language('extension/module/installment_calculator');
        
        $data['heading_popup'] = $this->language->get('heading_popup');
        $data['text_installment_amount'] = $this->language->get('text_installment_amount');
        $data['text_installment_period_months'] = $this->language->get('text_installment_period_months');
        $data['text_monthly_payment'] = $this->language->get('text_monthly_payment');
        $data['text_leave_contacts'] = $this->language->get('text_leave_contacts');
        $data['text_manager_contact'] = $this->language->get('text_manager_contact');
        $data['text_months'] = $this->language->get('text_months');
        $data['entry_name'] = $this->language->get('entry_name');
        $data['entry_phone'] = $this->language->get('entry_phone');
        $data['placeholder_name'] = $this->language->get('placeholder_name');
        $data['placeholder_phone'] = $this->language->get('placeholder_phone');
        $data['button_send'] = $this->language->get('button_send');
        
        return $this->load->view('extension/module/installment_popup', $data);
    }
    
    public function send() {
        $this->load->language('extension/module/installment_calculator');
        $json = [];
        
        if (empty($this->request->post['name'])) {
            $json['error'] = $this->language->get('error_name');
        }
        
        if (empty($this->request->post['phone'])) {
            $json['error'] = $this->language->get('error_phone');
        }
        
        if (empty($this->request->post['product_name'])) {
            $json['error'] = 'Не указан товар';
        }
        
        if (empty($this->request->post['months']) || !is_numeric($this->request->post['months'])) {
            $json['error'] = 'Не указан период рассрочки';
        }
        
        if (!isset($json['error'])) {
            $this->load->model('setting/setting');
            $settings = $this->model_setting_setting->getSetting('module_installment_calculator');
            
            $mail_to = !empty($settings['module_installment_calculator_email']) 
                ? $settings['module_installment_calculator_email'] 
                : $this->config->get('config_email');
            
            try {
                $mail = new Mail($this->config->get('config_mail_engine'));
                $mail->parameter = $this->config->get('config_mail_parameter');
                $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
                $mail->smtp_username = $this->config->get('config_mail_smtp_username');
                $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
                $mail->smtp_port = $this->config->get('config_mail_smtp_port');
                $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
                
                $mail->setTo($mail_to);
                $mail->setFrom($this->config->get('config_email'));
                $mail->setSender($this->config->get('config_name'));
                $mail->setSubject('Новая заявка на рассрочку - ' . $this->request->post['product_name']);
                
                $message = "=== ЗАЯВКА НА РАССРОЧКУ ===\n\n";
                $message .= "Клиент:\n";
                $message .= "  Имя: " . $this->request->post['name'] . "\n";
                $message .= "  Телефон: " . $this->request->post['phone'] . "\n\n";
                $message .= "Товар:\n";
                $message .= "  Название: " . $this->request->post['product_name'] . "\n";
                $message .= "  Цена: " . $this->request->post['price'] . "\n";
                $message .= "  Ссылка: " . html_entity_decode($this->request->post['product_url']) . "\n\n";
                $message .= "Условия рассрочки:\n";
                $message .= "  Период: " . $this->request->post['months'] . " месяцев\n";
                $message .= "  Ежемесячный платёж: " . $this->request->post['monthly'] . "\n\n";
                $message .= "Дата заявки: " . date('d.m.Y H:i:s') . "\n";
                $message .= "IP адрес: " . $this->request->server['REMOTE_ADDR'] . "\n";
                
                $mail->setText($message);
                $mail->send();
                
                $json['success'] = $this->language->get('text_success');
                
            } catch (Exception $e) {
                $json['error'] = $this->language->get('error_send');
                $this->log->write('Installment Calculator Mail Error: ' . $e->getMessage());
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}