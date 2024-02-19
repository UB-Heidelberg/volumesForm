<?php
/**
 * @file VolumesFormSettingsForm.php
 *
 * Copyright (c) 2017-2020 Simon Fraser University
 * Copyright (c) 2017-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CitationStyleLanguageSettingsForm.inc
 *
 * @ingroup plugins_generic_citationStyleLanguage
 *
 * @brief Form for site admins to modify Volumes Form settings.
 */

namespace APP\plugins\generic\volumesForm;

use APP\core\Application;
use APP\facades\Repo;
use APP\notification\NotificationManager;
use APP\template\TemplateManager;
use PKP\form\Form;
use PKP\form\validation\FormValidatorCSRF;
use PKP\form\validation\FormValidatorPost;
use PKP\notification\PKPNotification;
use PKP\security\Role;

class VolumesFormSettingsForm extends Form
{
    public VolumesFormPlugin $plugin;

    /**
     * Constructor
     *
     * @param VolumesFormPlugin $plugin object
     */
    public function __construct(VolumesFormPlugin $plugin)
    {
        parent::__construct($plugin->getTemplateResource('settings.tpl'));
        $this->plugin = $plugin;
        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
    }

    /**
    * @copydoc Form::init
    */
    public function initData(): void
    {
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        $contextId = $context->getId();
        $this->setData('groupAuthor', $this->plugin->getAuthorGroups($contextId));
        $this->setData('groupEditor', $this->plugin->getEditorGroups($contextId));
    }

    /**
     * Assign form data to user-submitted data.
     */
    public function readInputData(): void
    {
        $this->readUserVars([
            'groupAuthor',
            'groupEditor'
        ]);
    }

    /**
     * @copydoc Form::fetch()
     *
     * @param null|mixed $template
     */
    public function fetch($request, $template = null, $display = false): ?string
    {
        $context = $request->getContext();
        $contextId = $context->getId();

        $allUserGroups = [];
        $userGroups = Repo::userGroup()->getByRoleIds([Role::ROLE_ID_AUTHOR], $contextId);
        $userGroups = $userGroups->toArray();
        foreach ($userGroups as $userGroup) {
            $allUserGroups[(int) $userGroup->getId()] = $userGroup->getLocalizedName();
        }
        asort($allUserGroups);

        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign([
            'pluginName' => $this->plugin->getName(),
            'groupAuthor' => $this->getData('groupAuthor'),
            'groupEditor' => $this->getData('groupEditor'),
            'allUserGroups' => $allUserGroups,
        ]);

        return parent::fetch($request, $template, $display);
    }

    /**
     * @copydoc Form::execute()
     */
    public function execute(...$functionArgs)
    {
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        $contextId = $context->getId();
        $this->plugin->updateSetting($contextId, 'groupAuthor', $this->getData('groupAuthor'));
        $this->plugin->updateSetting($contextId, 'groupEditor', $this->getData('groupEditor'));

        $notificationMgr = new NotificationManager();
        $user = $request->getUser();
        $notificationMgr->createTrivialNotification($user->getId(), PKPNotification::NOTIFICATION_TYPE_SUCCESS, ['contents' => __('common.changesSaved')]);

        return parent::execute(...$functionArgs);
    }
}
