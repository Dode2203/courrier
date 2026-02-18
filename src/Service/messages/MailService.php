<?php

namespace App\Service\messages;

use App\Service\courriers\CourriersService;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Exception;

class MailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly CourriersService $courriersService,
        private readonly string $mailFrom
    ) {
    }

    /**
     * Envoie un mail de notification et clôture le dossier
     */
    public function envoyerMail(int $courrierId): void
    {
        $courrier = $this->courriersService->getCourrierById($courrierId);

        if (!$courrier) {
            throw new Exception("Courrier introuvable.");
        }

        $recipientMail = $courrier->getMail();
        if ($recipientMail && !empty(trim($recipientMail))) {
            $email = (new Email())
                ->from(new Address($this->mailFrom, $this->mailName)) // Utilise les paramètres injectés
                ->to($courrier->getMail())
                ->subject('Suivi de votre dossier : ' . $courrier->getReference())
                ->html("<p>Bonjour, votre dossier est maintenant clôturé. Référence : <b>{$courrier->getReference()}</b></p>");

            $this->mailer->send($email);

            // Clôture automatique du dossier après l'envoi du mail
            $this->courriersService->cloturerCourrier($courrierId);
        }
    }
}
