<?php

class CM_Mail_SendJob extends CM_Jobdistribution_Job_Abstract {

    protected function _execute(CM_Params $params) {
        $message = $params->getMailMessage('message');
        $recipient = $params->has('recipient') ? $params->getUser('recipient') : null;
        $mailType = $params->has('mailType') ? $params->getInt('mailType') : null;
        if (!$recipient || !$recipient->getEmailVerified()) {
            $serviceManager = CM_Service_Manager::getInstance();
            /** @var CM_Service_EmailVerification_ClientInterface $emailVerificationClient */
            $emailVerificationClient = $serviceManager->get('email-verification', CM_Service_EmailVerification_ClientInterface::class);
            foreach ($message->getTo() as $address => $name) {
                if (!$emailVerificationClient->isValid($address)) {
                    $logger = $serviceManager->getLogger();
                    $context = new CM_Log_Context();
                    $context->setExtra([
                        'email'     => $address,
                        'mailType'  => $mailType,
                        'recipient' => $recipient,
                    ]);
                    $logger->info('mail.send.rejected', $context);
                    return;
                }
            }
        }
        CM_Service_Manager::getInstance()->getMailer()->send($message);
        if ($recipient && isset($mailType)) {
            $action = new CM_Action_Email(CM_Action_Abstract::SEND, $recipient, $mailType);
            $action->prepare($recipient);
            $action->notify($recipient);
        }
    }
}
