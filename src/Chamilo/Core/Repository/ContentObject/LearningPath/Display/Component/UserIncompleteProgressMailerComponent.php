<?php

namespace Chamilo\Core\Repository\ContentObject\LearningPath\Display\Component;

use Chamilo\Core\Repository\ContentObject\LearningPath\Display\Manager;
use Chamilo\Core\User\Storage\DataClass\User;
use Chamilo\Libraries\Architecture\Exceptions\NotAllowedException;
use Chamilo\Libraries\Mail\Mailer\MailerFactory;
use Chamilo\Libraries\Mail\ValueObject\Mail;
use Chamilo\Libraries\Platform\Translation;

/**
 * Mails the users that do not have completed the given LearningPathTreeNode
 *
 * @author Sven Vanpoucke - Hogeschool Gent
 */
class UserIncompleteProgressMailerComponent extends Manager
{
    /**
     * Runs this component and returns its output
     *
     * @throws NotAllowedException
     */
    function run()
    {
        if (!$this->canEditLearningPathTreeNode($this->getCurrentLearningPathTreeNode()))
        {
            throw new NotAllowedException();
        }

        $currentLearningPathTreeNode = $this->getCurrentLearningPathTreeNode();
        $learningPathTrackingService = $this->getLearningPathTrackingService();

        $usersNotYetStarted = $learningPathTrackingService->findTargetUsersWithoutLearningPathAttempts(
            $this->get_root_content_object(), $currentLearningPathTreeNode
        );

        $usersPartiallyStarted = $learningPathTrackingService->findTargetUsersWithPartialLearningPathAttempts(
            $this->get_root_content_object(), $currentLearningPathTreeNode
        );

        $emailAddresses = array();

        foreach ($usersNotYetStarted as $userNotYetStarted)
        {
            $emailAddresses[] = $userNotYetStarted[User::PROPERTY_EMAIL];
        }

        foreach ($usersPartiallyStarted as $userPartiallyStarted)
        {
            $emailAddresses[] = $userPartiallyStarted[User::PROPERTY_EMAIL];
        }

        $mailContent = $this->getMailContent();

        $translator = Translation::getInstance();

        try
        {
            $mailerFactory = new MailerFactory();
            $mail = new Mail(
                $translator->getTranslation('IncompleteProgressMailTitle', $this->getParameters()),
                $mailContent, $emailAddresses
            );

            $mailer = $mailerFactory->getActiveMailer();
            $mailer->sendMail($mail);

            $success = true;
            $message = 'IncompleteProgressMailSent';
        }
        catch (\Exception $ex)
        {
            $success = false;
            $message = 'IncompleteProgressMailNotSent';
        }

        $this->redirect(
            $translator->getTranslation($message), !$success,
            array(self::PARAM_ACTION => self::ACTION_VIEW_USER_PROGRESS)
        );
    }

    /**
     * @return string
     */
    protected function getMailContent()
    {
        $translator = Translation::getInstance();
        $language = $translator->getLanguageIsocode();

        $variables = $this->getParameters();

        $contents = file_get_contents(
            $this->getPathBuilder()->getResourcesPath('Chamilo\Core\Repository\ContentObject\LearningPath\Display') .
            'Templates/Mail/IncompleteProgressMail.' . $language . '.html'
        );

        foreach ($variables as $variable => $value)
        {
            $contents = str_replace('{' . $variable . '}', $value, $contents);
        }

        return $contents;
    }

    /**
     * @return array
     */
    protected function getParameters(): array
    {
        $automaticNumberingService = $this->getAutomaticNumberingService();
        $currentNodeTitle = $automaticNumberingService->getAutomaticNumberedTitleForLearningPathTreeNode(
            $this->getCurrentLearningPathTreeNode()
        );

        $variables = array(
            'LEARNING_PATH' => $this->get_root_content_object()->get_title(),
            'STEP_NAME' => $currentNodeTitle,
            'USER' => $this->getUser()->get_fullname(),
            'URL' => $this->get_url(array(self::PARAM_ACTION => self::ACTION_VIEW_COMPLEX_CONTENT_OBJECT))
        );

        return $variables;
    }

}