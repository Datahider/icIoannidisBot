<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of icIoannidisBot
 *
 * @author drweb_000
 */
class icIoannidisBot extends lhSelfTestingClass {
    protected $aiml;
    protected $csml;
    protected $dbconn;
    protected $message;
    protected $reply;
    protected $session;
    protected $chatterbox;

    public function __construct($dbconn, $csml, $aiml) {
        $this->logFunction(__FUNCTION__, 10, true);
        (new lhTest(lhTest::IS_A, 'PDO'))->test($dbconn);
        (new lhTest(lhTest::IS_A, 'lhCSML'))->test($csml);
        (new lhTest(lhTest::IS_A, 'lhAIML'))->test($aiml);
        
        $this->dbconn = $dbconn;
        $this->csml = $csml;
        $this->aiml = $aiml;
    }
    
    public function processMessage(lhSimpleMessage $message) {
        $this->prepareProcessorData($message);
        
        if ($message->serviceId()) {
            $this->processServiceMessage();
        } else {
            $this->processCommonMessage();
        }
        return $this->reply;
    }
    
    protected function processAttachments() {
        foreach ($this->message->attachments() as $attachment) {
            $this->processAttachment($attachment);
        }
    }

    protected function processServiceMessage() {
        $this->reply->setServiceId($this->message->serviceId());
        $this->reply->setServicePointer('answerCallbackQuery');
        switch ($this->message->serviceData()) {
            case 'test service data':
                $this->reply->setText("an answer to test service data");
                break;
        }
    }
    
    protected function processCommonMessage() {
        if ($this->hasAttachments()) {
            $this->processAttachments();
        } else {
            $this->processText($this->message->text());
        }
    }    

    protected function setContact($contact) {
        $name = $contact->first_name;
        if (!empty($contact->last_name)) {
            $name .= " $contact->last_name";
        }
        $this->session->set('name', $name);
        $this->session->set('phone', $contact->phone_number);
    }
    
    protected function processText($text) {
        $answer = $this->chatterbox->process($text);
        $this->answer2reply($answer);
    }
    
    protected function answer2reply($answer) {
        if (!empty($answer['commands'])) {
            foreach ($answer['commands'] as $command) {
                try {
                    $cmd = new $command($this->message);
                    $answer = $cmd->run();
                } catch (Exception $exc) {
                    $this->log($exc->getTraceAsString());
                    $this->reply->setText('Ошибка выполнения команды. Свяжитесь с администратором.');
                }
            }
        } else {
            $this->reply->setText($answer['text']);
            if ($answer['hints']) {
                foreach ($answer['hints'] as $hint) {
                    $hint_data = explode('|', $hint, 2);
                    if (count($hint_data) == 1) {
                        $this->reply->addHint(new lhSimpleMessageHint($hint_data[0]));
                    } else {
                        $this->reply->addHint(new lhSimpleMessageHint($hint_data[1], $hint_data[0]));
                    }
                }
            }
        }
    }
    
    protected function prepareProcessorData($message) {
        $this->message = $message;
        $this->reply = new lhSimpleMessage();
        $this->reply->setBuddy($this->message->buddy());
        
        $this->session = new lhDBSession($this->message->buddy(), $this->dbconn, DB_TABLE_PREFIX);
        if (!$this->session->get('status', false)) {
            $this->session->set('status', 'babbler');
        }
        
        if (!$this->session->get('name', false)) {
            $this->session->set('tags', '#noname');
        }
        
        $this->chatterbox = new lhChatterBox($this->session, $this->aiml, $this->csml);
        return $this;
    }

    protected function processAttachment($attachment) {
        if ($attachment->name() == '__CONTACT__' && $this->session->get('tags') == '#noname') { // Это регистрация
            $contact = json_decode($attachment->data());
            $this->setContact($contact);
            $this->session->set('tags', '#meet');
            $text = 'meet';
        } else {
            $this->session->set('tags', '#attachment');
            $text = $this->message->text();
        }
        $this->processText($text);
    }
    
    protected function hasAttachments() {
        return count($this->message->attachments());
    }
    
    protected function _test_data() {
        $attachment = new lhSimpleMessageAttachment();
        $attachment->setName('an_attachment.txt');
        $attachment->setData('0123456789');
        
        $contact = new lhSimpleMessageAttachment();
        $contact->setName('__CONTACT__');
        $contact->setData('{"first_name": "Тест", "last_name": "Тестович", "phone_number": "79262261818"}');
        
        $service_message = new lhSimpleMessage();
        $service_message->setServiceId('test');
        $service_message->setServiceData('test service data');
        $service_message->setServicePointer('0000000');
        $service_message->setBuddy(11);
        $service_message->addAttachment($attachment);
        
        $start_message = new lhSimpleMessage();
        $start_message->setText('/start');
        $start_message->setBuddy(11);
        
        $error_message = new lhSimpleMessage();
        $error_message->setText('/error');
        $error_message->setBuddy(11);
        
        $attachment_message = new lhSimpleMessage();
        $attachment_message->addAttachment($attachment);
        $attachment_message->setText('Text with attachment');
        $attachment_message->setBuddy(11);
        
        $contact_message = new lhSimpleMessage();
        $contact_message->addAttachment($contact);
        $contact_message->setBuddy(11);
        
        return [
            'processServiceMessage' => '_test_skip_',
            'processCommonMessage' => '_test_skip_',
            'prepareProcessorData' => [
                [$start_message, new lhTest(lhTest::FIELD_IS_A, 'reply', 'lhSimpleMessage')],
                [$start_message, new lhTest(lhTest::FIELD_IS_A, 'session', 'lhDBSession')],
                [$start_message, new lhTest(lhTest::FIELD_IS_A, 'chatterbox', 'lhChatterBox')],
            ],
            'processMessage' => [
                [$service_message, new lhTest(lhTest::FUNC, 'serviceId', 'test')],
                [$service_message, new lhTest(lhTest::FUNC, 'servicePointer', 'answerCallbackQuery')],
                [$error_message, new lhTest(lhTest::FIELD, 'text', 'Привет, давай знакомиться!')],
                [$start_message, new lhTest(lhTest::FIELD, 'text', 'Привет, давай знакомиться!')],
                [$contact_message, new lhTest(lhTest::FIELD, 'text', "Вы зарегистрированы под именем Тест Тестович с номером телефона 79262261818.\n\nДля изменения имени в любой момент введите /name")],
                [$start_message, new lhTest(lhTest::FIELD, 'text', 'Привет, я тебя знаю')],
                [$error_message, new lhTest(lhTest::FIELD, 'text', 'Ошибка выполнения команды. Свяжитесь с администратором.')],
                [$attachment_message, new lhTest(lhTest::FIELD, 'text', "Не знаю, что с этим делать")],
                [$service_message, new lhTest(lhTest::FUNC, 'text', 'an answer to test service data')],
            ],
            'hasAttachments' => [
                [true]
            ],
            'processText' => '_test_skip_',
            'processAttachments' => '_test_skip_',
            'processAttachment' => '_test_skip_',
            'setContact' => '_test_skip_',
            'answer2reply' => '_test_skip_',
        ];
    }
}
