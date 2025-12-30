<?php
class ControllerExtensionModuleInstallmentCalculator extends Controller {
    
    public function index() {
        $this->load->language('extension/module/installment_calculator');
        $this->load->model('setting/setting');
        
        $settings = $this->model_setting_setting->getSetting('module_installment_calculator');
        
        // Проверка статуса модуля
        if (empty($settings['module_installment_calculator_status'])) {
            return '';
        }
        
        // Получение настроенных периодов
        $months_string = !empty($settings['module_installment_calculator_months']) 
            ? $settings['module_installment_calculator_months'] 
            : '4,6,10,12';
        
        // Преобразование в массив целых чисел
        $months = array_map('intval', array_map('trim', explode(',', $months_string)));
        
        // Сортировка по возрастанию
        sort($months);
        
        $data['months'] = $months;
        $data['default_month'] = end($months); // Последний (максимальный) период
        $data['price'] = '0'; // Будет взято из JS на клиенте
        
        return $this->load->view('extension/module/installment_calculator', $data);
    }
    
    public function popup() {
        $this->load->language('extension/module/installment_calculator');
        return $this->load->view('extension/module/installment_popup');
    }
    
    public function send() {
        $this->load->language('extension/module/installment_calculator');
        $json = [];
        
        // Валидация
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
        
        // Если ошибок нет, отправляем письмо
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
                $message .= "  Ссылка: " . $this->request->post['product_url'] . "\n\n";
                $message .= "Условия рассрочки:\n";
                $message .= "  Период: " . $this->request->post['months'] . " месяцев\n";
                $message .= "  Ежемесячный платёж: " . $this->request->post['monthly'] . "\n\n";
                $message .= "Дата заявки: " . date('d.m.Y H:i:s') . "\n";
                $message .= "IP адрес: " . $this->request->server['REMOTE_ADDR'] . "\n";
                
                $mail->setText($message);
                $mail->send();
                
                $json['success'] = $this->language->get('text_success');
                
            } catch (Exception $e) {
                $json['error'] = 'Ошибка отправки письма. Попробуйте позже.';
                $this->log->write('Installment Calculator Mail Error: ' . $e->getMessage());
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}