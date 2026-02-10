<?php

namespace App\Controller;

use App\DTO\ContactData;
use App\Form\ContactFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request, MailerInterface $mailer): Response
    {
        $contactData = new ContactData();
        $form = $this->createForm(ContactFormType::class, $contactData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $email = (new Email())
                ->from('noreply@vite-et-gourmand.fr')
                ->replyTo($data->email)
                ->to('admin@vite-et-gourmand.fr')
                ->subject('Contact : ' . $data->sujet)
                ->text(sprintf(
                    "Nouveau message de contact\n\nDe : %s (%s)\nCode postal : %s\nSujet : %s\n\nMessage :\n%s",
                    $data->nom,
                    $data->email,
                    $data->code_postal,
                    $data->sujet,
                    $data->message
                ))
                ->html(sprintf(
                    '<h2>Nouveau message de contact</h2>
                    <p><strong>De :</strong> %s (%s)</p>
                    <p><strong>Code postal :</strong> %s</p>
                    <p><strong>Sujet :</strong> %s</p>
                    <h3>Message :</h3>
                    <p>%s</p>',
                    htmlspecialchars($data->nom),
                    htmlspecialchars($data->email),
                    htmlspecialchars($data->code_postal),
                    htmlspecialchars($data->sujet),
                    nl2br(htmlspecialchars($data->message))
                ));

            $mailer->send($email);

            $this->addFlash('success', 'Votre message a bien été envoyé !');

            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact/index.html.twig', [
            'contactForm' => $form,
        ]);
    }
}
