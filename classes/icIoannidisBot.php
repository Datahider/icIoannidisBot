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
        $this->message = $message;
        if ($message->serviceId()) {
            return $this->processServiceMessage();
        } else {
            return $this->processCommonMessage();
        }
    }
    
    protected function processServiceMessage() {
        $reply = new lhSimpleMessage();
        $reply->setServiceId($this->message->serviceId());
        $reply->setServicePointer('answerCallbackQuery');
        switch ($this->message->serviceData()) {
            case 'test service data':
                $reply->setText("an answer to test service data");
                break;
        }
        return $reply;
    }
    
    protected function processCommonMessage() {
        $reply = new lhSimpleMessage();
        $session = new lhDBSession($this->message->buddy(), $this->dbconn, DB_TABLE_PREFIX);
        $session->set('status', 'babbler');
        
        if (!$session->get('name', false)) {
            $session->set('tags', '#noname');
        }
        
        $chatterbox = new lhChatterBox($session, $this->aiml, $this->csml);
        $answer = $chatterbox->process($this->message->text());
        $reply->setText($answer['text']);
        foreach ($reply->hints() as $hint) {
            $hint_data = explode('|', $hint, 2);
            if (count($hint_data) == 1) {
                $reply->addHint(new lhSimpleMessageHint($hint_data[0]));
            } else {
                $reply->addHint(new lhSimpleMessageHint($hint_data[1], $hint_data[0]));
            }
        }
        return $reply;
    }
    
    protected function _test_data() {
        $service_message = new lhSimpleMessage();
        $service_message->setServiceId('test');
        $service_message->setServiceData('test service data');
        $service_message->setServicePointer('0000000');
        $service_message->setBuddy(11);
        
        $start_message = new lhSimpleMessage();
        $start_message->setText('/start');
        $start_message->setBuddy(11);
        
        return [
            'processServiceMessage' => '_test_skip_',
            'processCommonMessage' => '_test_skip_',
            'processMessage' => [
                [$service_message, new lhTest(lhTest::FUNC, 'serviceId', 'test')],
                [$service_message, new lhTest(lhTest::FUNC, 'servicePointer', 'answerCallbackQuery')],
                [$service_message, new lhTest(lhTest::FUNC, 'text', 'an answer to test service data')],
                [$start_message, new lhTest(lhTest::FUNC, 'text', 'Привет, давай знакомиться!')],
            ]
        ];
    }
}
