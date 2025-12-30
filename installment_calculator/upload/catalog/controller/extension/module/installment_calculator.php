<?php
class ControllerExtensionModuleInstallmentCalculator extends Controller {
    
    public function index() {
        $this->load->language('extension/module/installment_calculator');
        $this->load->model('setting/setting');
        
        $settings = $this->model_setting_setting->getSetting('module_installment_calculator');
        
        if (empty($settings['module_installment_calculator_status'])) {
            return '';
        }
        
        $data['months'] = !empty($settings['module_installment_calculator_months']) 
            ? explode(',', $settings['module_installment_calculator_months']) 
            : [4, 6, 10, 12];
        
        $data['default_month'] = 12;
        
        return $this->load->view('extension/module/installment_calculator', $data);
    }
    
    public function popup() {
        $this->load->language('extension/module/installment_calculator');
        
        return $this->load->view('extension/module/installment_popup');
    }
    
    public function send() {
        $this->load->language('extension/module/installment_calculator');
        $json = [];
        
        // Validation
        if (empty($this->request->post['name'])) {
            $json['error'] = $this->language->get('error_name');
        }
        
        if (empty($this->request->post['phone'])) {
            $json['error'] = $this->language->get('error_phone');
        }
        
        if (!isset($json['error'])) {
            $this->load->model('setting/setting');
            $settings = $this->model_setting_setting->getSetting('module_installment_calculator');
            
            $mail_to = !empty($settings['module_installment_calculator_email']) 
                ? $settings['module_installment_calculator_email'] 
                : $this->config->get('config_email');
            
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
            $mail->setSubject('Заявка на рассрочку');
            
            $message = "Новая заявка на рассрочку:\n\n";
            $message .= "Имя: " . $this->request->post['name'] . "\n";
            $message .= "Телефон: " . $this->request->post['phone'] . "\n";
            $message .= "Товар: " . $this->request->post['product_name'] . "\n";
            $message .= "Цена: " . $this->request->post['price'] . "\n";
            $message .= "Период: " . $this->request->post['months'] . " мес.\n";
            $message .= "Платёж: " . $this->request->post['monthly'] . "/мес.\n";
            $message .= "Ссылка: " . $this->request->post['product_url'] . "\n";
            
            $mail->setText($message);
            $mail->send();
            
            $json['success'] = $this->language->get('text_success');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}