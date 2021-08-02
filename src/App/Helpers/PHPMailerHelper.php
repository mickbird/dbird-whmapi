<?php
declare(strict_types = 1);

namespace App\Helpers;

use Core\Application;
use PHPMailer\PHPMailer\PHPMailer;

class PHPMailerHelper
{
    /*
     * FIELDS
     */

    protected array $bccAddresses;
    protected PHPMailer $phpMailer;

    /*
     * GETTERS / SETTERS
     */

    /*
     * CONSTRUCTOR / INITIALIZER
     */

    public function __construct()
    {
        $config = Application::current()->getConfig();

        $this->bccAddresses = $config->getBCCAddresses();

        $this->phpMailer = new PHPMailer(false);

        $this->phpMailer->isSMTP();
        $this->phpMailer->Host  = $config->getSmtpHost();
        $this->phpMailer->Port = $config->getSmtpPort();
        $this->phpMailer->SMTPAuth = $config->getSmtpAuth();
        $this->phpMailer->Username = $config->getSmtpUsername();
        $this->phpMailer->Password = $config->getSmtpPassword();
        $this->phpMailer->SMTPSecure = $config->getSmtpSecure();
        $this->phpMailer->SMTPDebug = $config->getSmtpDebugLevel();

        $this->phpMailer->CharSet = 'UTF-8';
        $this->phpMailer->Encoding = 'base64';
        $this->phpMailer->setFrom(
            $config->getSmtpFromAddress(),
            $config->getSmtpFromName(),
        );
        $this->phpMailer->AddReplyTo(
            $config->getSmtpFromAddress(),
            $config->getSmtpFromName()
        );
    }

    /*
     * PUBLIC METHODS
     */

    public function send(string $templateFile, array $context, array $toAddresses, array $ccAddresses = [], array $bccAddresses = []) : bool
    {
        $content = Application::current()->getViewBuilder()->build($templateFile, $context);

        $matches = null;
        if (!preg_match('/<title>\s*(?<title>.+?)\s*<\/title>/imx', $content, $matches)) {
            return false;
        }

        $subject = $matches['title'];

        $this->phpMailer->Subject = $subject;
        $this->phpMailer->msgHTML($content);

        foreach ($toAddresses as $address) {
            $this->phpMailer->addAddress($address);
        }

        foreach ($ccAddresses as $address) {
            $this->phpMailer->addCC($address);
        }

        foreach (array_merge($bccAddresses, $this->bccAddresses) as $address) {
            $this->phpMailer->addBCC($address);
        }

        $success = $this->phpMailer->send();

        $this->phpMailer->clearAllRecipients();
        $this->phpMailer->ClearAttachments();

        return $success;
    }

    /*
     * PRIVATE / PROTECTED METHODS
     */

    /*
     * STATIC METHODS
     */
}
